<?php

namespace Tests\Feature\Fleet;

use App\Jobs\Fleet\SendFleetNotification;
use App\Models\Fleet\Assignment;
use App\Models\Fleet\Beneficiary;
use App\Models\Fleet\Evaluation;
use App\Models\Fleet\EvaluationPeriod;
use App\Models\Fleet\Notification;
use App\Models\Fleet\Vehicle;
use App\Services\Fleet\EvaluationSchedule;
use App\Services\Fleet\WhatsAppClient;
use Mockery;

class ReviewAndRemindersTest extends FleetTestCase
{
    /** @var array<int, array{phone: string, template: string, parameters: array}> */
    private array $sent = [];

    protected function setUp(): void
    {
        parent::setUp();

        $whatsApp = Mockery::mock(WhatsAppClient::class);
        $whatsApp->shouldReceive('sendTemplate')->andReturnUsing(function ($phone, $template, $components) {
            $this->sent[] = ['phone' => $phone, 'template' => $template, 'parameters' => array_column($components[0]['parameters'], 'text')];

            return 'wamid.test';
        });
        $this->app->instance(WhatsAppClient::class, $whatsApp);
    }

    private function assignment(array $beneficiary = [], int $daysAgo = 3): Assignment
    {
        $assignment = Assignment::create([
            'vehicle_id' => Vehicle::factory()->create(['plate_number' => 'KJA'.random_int(100, 999).'XY', 'status' => 'assigned'])->id,
            'beneficiary_id' => Beneficiary::factory()->create($beneficiary + ['full_name' => 'Ada Obi'])->id,
            'status' => 'active',
            'handed_over_at' => now()->subDays($daysAgo),
        ]);
        app(EvaluationSchedule::class)->sync($assignment);

        return $assignment;
    }

    private function evaluation(Assignment $assignment, array $overrides = []): Evaluation
    {
        $period = $assignment->evaluationPeriods()->orderByDesc('sequence')->first();
        $period->update(['status' => 'submitted']);

        return Evaluation::create(array_merge([
            'period_id' => $period->id, 'assignment_id' => $assignment->id, 'beneficiary_id' => $assignment->beneficiary_id,
            'vehicle_id' => $assignment->vehicle_id, 'source' => 'live', 'trust_level' => 'verified_live', 'trust_flags' => [],
            'original_filename' => 'a.webm', 'original_mime' => 'video/webm', 'original_size' => 1000, 'file_hash' => hash('sha256', uniqid()),
            'processing_status' => 'ready', 'mileage_km' => 10500, 'fuel_level' => 'half', 'has_damage' => false, 'branding_intact' => true,
            'review_status' => 'pending', 'submitted_at' => now(),
        ], $overrides));
    }

    public function test_approving_an_evaluation_approves_its_window(): void
    {
        $reviewer = $this->actingAsFleetAdmin('reviewer');
        $evaluation = $this->evaluation($this->assignment());

        $this->fleetApi('POST', "admin/evaluations/{$evaluation->id}/review", ['decision' => 'approve'])
            ->assertOk()
            ->assertJsonPath('evaluation.review_status', 'approved')
            ->assertJsonPath('evaluation.reviewed_by', $reviewer->name);

        $this->assertSame('approved', $evaluation->period->fresh()->status);
        $this->assertSame([], $this->sent);

        // Only once
        $this->fleetApi('POST', "admin/evaluations/{$evaluation->id}/review", ['decision' => 'reject', 'notes' => 'x'])->assertJsonValidationErrors('decision');
    }

    public function test_rejecting_during_the_window_reopens_it_and_tells_the_beneficiary(): void
    {
        $this->actingAsFleetAdmin('reviewer');
        $assignment = $this->assignment(['phone' => '+2348031234567']);
        $evaluation = $this->evaluation($assignment);

        $this->fleetApi('POST', "admin/evaluations/{$evaluation->id}/review", ['decision' => 'reject'])->assertJsonValidationErrors('notes');
        $this->fleetApi('POST', "admin/evaluations/{$evaluation->id}/review", ['decision' => 'reject', 'notes' => "The odometer\nisn't shown."])->assertOk();

        $this->assertTrue($evaluation->period->fresh()->acceptsSubmissions());
        $this->assertSame('+2348031234567', $this->sent[0]['phone']);
        $this->assertSame('fleet_evaluation_rejected', $this->sent[0]['template']);
        $this->assertSame(['Ada', $assignment->vehicle->displayPlate(), "The odometer isn't shown."], $this->sent[0]['parameters']);
        $this->assertSame('sent', Notification::sole()->status);

        // The beneficiary sees why
        $this->actingAs($assignment->beneficiary, 'fleet_beneficiary');
        $this->fleetApi('GET', 'evaluations')
            ->assertJsonPath('current.accepts_submissions', true)
            ->assertJsonPath('current.evaluation.review_notes', "The odometer\nisn't shown.");
    }

    public function test_rejecting_after_the_window_has_ended_records_it_as_rejected(): void
    {
        $this->actingAsFleetAdmin();
        $evaluation = $this->evaluation($this->assignment(daysAgo: 13));
        $this->travel(2)->days();

        $this->fleetApi('POST', "admin/evaluations/{$evaluation->id}/review", ['decision' => 'reject', 'notes' => 'Wrong vehicle'])->assertOk();

        $this->assertSame('rejected', $evaluation->period->fresh()->status);
    }

    public function test_reminders_go_out_once_before_the_due_date_and_once_after_a_miss(): void
    {
        $dueSoon = $this->assignment(['phone' => '+2348030000001'], daysAgo: 12); // window ends in 2 days
        $this->assignment(['phone' => '+2348030000002'], daysAgo: 5);             // ends in 9 days: not yet
        $this->evaluation($this->assignment(['phone' => '+2348030000003'], daysAgo: 12)); // already sent: no reminder

        $this->artisan('fleet:send-reminders')->assertSuccessful();
        $this->artisan('fleet:send-reminders')->assertSuccessful();

        $this->assertSame(['+2348030000001'], array_column($this->sent, 'phone'));
        $this->assertSame('fleet_evaluation_due', $this->sent[0]['template']);

        // The window passes with nothing sent
        $this->travel(3)->days();
        $this->artisan('fleet:send-reminders');

        $missed = collect($this->sent)->where('template', 'fleet_evaluation_missed')->values();
        $this->assertCount(1, $missed);
        $this->assertSame('+2348030000001', $missed[0]['phone']);
        $this->assertSame('missed', $dueSoon->evaluationPeriods()->where('sequence', 1)->value('status'));
    }

    public function test_suspended_beneficiaries_are_not_messaged(): void
    {
        $assignment = $this->assignment(daysAgo: 12);
        $assignment->beneficiary->forceFill(['deactivated_at' => now()])->save();

        $this->artisan('fleet:send-reminders');

        $this->assertSame([], $this->sent);
        $this->assertSame('skipped', Notification::sole()->status);
    }

    public function test_a_failed_send_is_recorded(): void
    {
        $assignment = $this->assignment(daysAgo: 12);
        $notification = app(\App\Services\Fleet\FleetNotifier::class)->evaluationDue($assignment->evaluationPeriods()->first());

        (new SendFleetNotification($notification->id))->failed(new \RuntimeException('WhatsApp send failed (400): template not found'));

        $this->assertSame('failed', $notification->fresh()->status);
        $this->actingAsFleetAdmin();
        $this->fleetApi('GET', "admin/beneficiaries/{$assignment->beneficiary_id}")
            ->assertJsonPath('notifications.0.status', 'failed')
            ->assertJsonPath('notifications.0.error', 'WhatsApp send failed (400): template not found');
    }

    public function test_recalled_beneficiaries_are_told_on_whatsapp(): void
    {
        $this->actingAsFleetAdmin();
        $assignment = $this->assignment();

        $this->fleetApi('POST', 'admin/recalls', ['title' => 'End of campaign', 'return_by' => '2026-12-01', 'all_active' => true])->assertCreated();

        $this->assertSame('fleet_vehicle_recalled', $this->sent[0]['template']);
        $this->assertSame(['Ada', $assignment->vehicle->displayPlate(), 'Tue 1 Dec 2026'], $this->sent[0]['parameters']);
    }

    public function test_compliance_puts_overdue_vehicles_first(): void
    {
        $this->actingAsFleetAdmin('reviewer');
        $fine = $this->assignment(daysAgo: 3);
        $behind = $this->assignment(daysAgo: 17); // first window ended 3 days ago with nothing sent
        $soon = $this->assignment(daysAgo: 12);
        app(EvaluationSchedule::class)->syncAll();

        $response = $this->fleetApi('GET', 'admin/compliance')->assertOk()
            ->assertJsonPath('summary', ['vehicles_out' => 3, 'overdue' => 1, 'due_soon' => 1, 'ok' => 1]);

        $this->assertSame([$behind->id, $soon->id, $fine->id], array_column($response->json('data'), 'assignment_id'));
        $this->assertSame(0.0, (float) $response->json('data.0.on_time_rate'));
    }

    public function test_dashboard_includes_evaluation_figures(): void
    {
        $this->actingAsFleetAdmin('reviewer');
        $this->evaluation($this->assignment());
        $this->assignment();

        $this->fleetApi('GET', 'admin/dashboard')
            ->assertJsonPath('evaluations.pending_review', 1)
            ->assertJsonPath('evaluations.this_window', ['vehicles' => 2, 'sent' => 1])
            ->assertJsonCount(8, 'evaluations.weekly');
    }

    public function test_mileage_history_combines_handovers_and_evaluations(): void
    {
        $this->actingAsFleetAdmin('reviewer');
        $assignment = $this->assignment(daysAgo: 3);
        $assignment->handovers()->create([
            'type' => 'handover', 'odometer_km' => 10000, 'fuel_level' => 'full', 'checklist' => [], 'officer_signature_path' => 'a',
            'signed_by_name' => 'Ada', 'signer_signature_path' => 'b', 'occurred_at' => now()->subDays(3),
        ]);
        $this->evaluation($assignment, ['mileage_km' => 10800]);

        $this->fleetApi('GET', "admin/vehicles/{$assignment->vehicle_id}/mileage")
            ->assertJsonPath('readings.0.km', 10000)
            ->assertJsonPath('readings.0.source', 'handover')
            ->assertJsonPath('readings.1.km', 10800)
            ->assertJsonPath('readings.1.source', 'evaluation');
    }

    public function test_exports_are_csv_and_neutralise_spreadsheet_formulas(): void
    {
        $this->actingAsFleetAdmin('reviewer');
        $assignment = $this->assignment();
        $this->evaluation($assignment, ['damage_notes' => '=HYPERLINK("http://evil")', 'has_damage' => true]);

        $csv = $this->get($this->fleetUrl('api/v1/admin/exports/evaluations'))->assertOk()->streamedContent();

        $this->assertStringStartsWith("\xEF\xBB\xBFSubmitted,Plate,Beneficiary", $csv);
        $this->assertStringContainsString("'=HYPERLINK", $csv);

        foreach (['vehicles', 'beneficiaries', 'assignments', 'compliance'] as $type) {
            $this->get($this->fleetUrl("api/v1/admin/exports/{$type}"))->assertOk()->assertHeader('Content-Type', 'text/csv; charset=UTF-8');
        }
    }
}
