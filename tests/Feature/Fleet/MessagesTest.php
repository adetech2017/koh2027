<?php

namespace Tests\Feature\Fleet;

use App\Models\Fleet\Assignment;
use App\Models\Fleet\Beneficiary;
use App\Models\Fleet\MessageTemplate;
use App\Models\Fleet\Notification;
use App\Models\Fleet\Vehicle;
use App\Services\Fleet\EvaluationSchedule;
use App\Services\Fleet\WhatsAppClient;
use Mockery;

class MessagesTest extends FleetTestCase
{
    /** @var array<int, array{phone: string, template: string, parameters: array}> */
    private array $sent = [];

    /** @var array<int, array> Templates submitted to Meta */
    private array $submitted = [];

    /** What Meta says when asked about a template, by name */
    private array $meta = [];

    private function fakeWhatsApp(bool $testMode = true): void
    {
        $whatsApp = Mockery::mock(WhatsAppClient::class);
        $whatsApp->shouldReceive('isFake')->andReturn($testMode);
        $whatsApp->shouldReceive('sendTemplate')->andReturnUsing(function ($phone, $template, $components) {
            $this->sent[] = ['phone' => $phone, 'template' => $template, 'parameters' => array_column($components[0]['parameters'], 'text')];

            return 'wamid.test';
        });
        $whatsApp->shouldReceive('createTemplate')->andReturnUsing(function (...$args) {
            $this->submitted[] = $args;

            return ['status' => 'PENDING', 'reason' => null];
        });
        $whatsApp->shouldReceive('findTemplate')->andReturnUsing(fn ($name) => $this->meta[$name] ?? null);
        $this->app->instance(WhatsAppClient::class, $whatsApp);
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->fakeWhatsApp();
    }

    private function template(string $key): MessageTemplate
    {
        return MessageTemplate::where('key', $key)->sole();
    }

    private function general(): MessageTemplate
    {
        return MessageTemplate::where('name', 'fleet_general_message')->sole();
    }

    private function assignment(array $beneficiary = [], int $daysAgo = 12): Assignment
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

    public function test_the_automatic_messages_and_a_general_message_are_set_up(): void
    {
        $this->actingAsFleetAdmin('reviewer');

        $response = $this->fleetApi('GET', 'admin/messages/templates')->assertOk()->assertJsonPath('test_mode', true);

        $this->assertSame(
            ['evaluation_due', 'evaluation_missed', 'evaluation_rejected', 'vehicle_recalled', null],
            array_column($response->json('data'), 'key'),
        );
        $this->assertSame(['First name', 'Plate number', 'Due date'], array_column($response->json('data.0.variables'), 'label'));
        $this->assertSame(3, $response->json('data.0.settings.days_before'));
    }

    public function test_a_switched_off_message_is_not_sent(): void
    {
        $this->actingAsFleetAdmin('super_admin');
        $this->fleetApi('PATCH', "admin/messages/templates/{$this->template('evaluation_due')->id}", ['enabled' => false])->assertOk();
        $this->assignment();

        $this->artisan('fleet:send-reminders');

        $this->assertSame([], $this->sent);
        $this->assertSame(0, Notification::count());
    }

    public function test_the_reminder_timing_can_be_changed(): void
    {
        $this->actingAsFleetAdmin('super_admin');
        $this->assignment(daysAgo: 8); // window ends in 6 days

        $this->artisan('fleet:send-reminders');
        $this->assertSame([], $this->sent);

        $this->fleetApi('PATCH', "admin/messages/templates/{$this->template('evaluation_due')->id}", ['days_before' => 7])->assertOk();
        $this->artisan('fleet:send-reminders');
        $this->assertCount(1, $this->sent);

        // Only the due reminder has a timing
        $this->fleetApi('PATCH', "admin/messages/templates/{$this->template('evaluation_missed')->id}", ['days_before' => 2])
            ->assertJsonValidationErrors('days_before');
    }

    public function test_new_wording_in_test_mode_is_approved_straight_away_under_a_new_name(): void
    {
        $this->actingAsFleetAdmin('super_admin');
        $template = $this->template('evaluation_due');

        $this->fleetApi('POST', "admin/messages/templates/{$template->id}/wording", [
            'body' => 'Hi {{1}}, please film vehicle {{2}} before {{3}}. Thank you.',
        ])
            ->assertOk()
            ->assertJsonPath('template.name', 'fleet_evaluation_due_v2')
            ->assertJsonPath('template.status', 'approved')
            ->assertJsonPath('template.draft', null);

        $assignment = $this->assignment();
        $this->artisan('fleet:send-reminders');

        $this->assertSame('fleet_evaluation_due_v2', $this->sent[0]['template']);
        $this->assertStringStartsWith('Hi Ada, please film vehicle '.$assignment->vehicle->displayPlate().' before ', Notification::sole()->body);
    }

    public function test_wording_meta_would_refuse_is_rejected_here_first(): void
    {
        $this->actingAsFleetAdmin('super_admin');
        $url = "admin/messages/templates/{$this->template('evaluation_rejected')->id}/wording";

        foreach ([
            'Hello {{1}}, vehicle {{2}} was not accepted.' => 'Use each of {{1}} to {{3}} exactly once.',
            '{{1}}, vehicle {{2}} was not accepted: {{3}}.' => "WhatsApp doesn't allow a message to start or end with a blank. Add some words before the first one or after the last.",
            'Hello {{1}}, vehicle {{2}} was not accepted: {{ 3 }}.' => 'Write the blanks as {{1}}, {{2}} and so on, with no spaces or words inside the braces.',
        ] as $body => $error) {
            $this->fleetApi('POST', $url, ['body' => $body])->assertJsonValidationErrors(['body' => $error]);
        }

        $this->assertSame('fleet_evaluation_rejected', $this->template('evaluation_rejected')->name);
    }

    public function test_new_wording_waits_for_meta_and_the_old_wording_is_used_meanwhile(): void
    {
        $this->fakeWhatsApp(testMode: false);
        $this->actingAsFleetAdmin('super_admin');
        $template = $this->template('evaluation_due');
        $this->meta['fleet_evaluation_due'] = ['status' => 'APPROVED', 'reason' => null, 'body' => $template->body];

        $this->fleetApi('POST', "admin/messages/templates/{$template->id}/wording", ['body' => 'Hi {{1}}, film vehicle {{2}} by {{3}}, please.'])
            ->assertOk()
            ->assertJsonPath('template.name', 'fleet_evaluation_due')
            ->assertJsonPath('template.draft.name', 'fleet_evaluation_due_v2')
            ->assertJsonPath('template.draft.status', 'pending');

        [$name, $language, $category, $body, $examples] = $this->submitted[0];
        $this->assertSame(['fleet_evaluation_due_v2', 'en', 'UTILITY'], [$name, $language, $category]);
        $this->assertSame(['Adebayo', 'LSD 123 AB', 'Tue 1 Dec 2026'], $examples);

        $this->assignment();
        $this->artisan('fleet:send-reminders');
        $this->assertSame('fleet_evaluation_due', $this->sent[0]['template']);

        // Meta approves; the scheduled check switches over
        $this->meta['fleet_evaluation_due_v2'] = ['status' => 'APPROVED', 'reason' => null, 'body' => 'Hi {{1}}, film vehicle {{2}} by {{3}}, please.'];
        $this->artisan('fleet:sync-templates')->assertSuccessful();

        $template->refresh();
        $this->assertSame(['fleet_evaluation_due_v2', 'Hi {{1}}, film vehicle {{2}} by {{3}}, please.', null], [$template->name, $template->body, $template->draft_status]);
    }

    public function test_a_rejected_draft_keeps_the_old_wording_and_says_why(): void
    {
        $this->fakeWhatsApp(testMode: false);
        $this->actingAsFleetAdmin('super_admin');
        $template = $this->template('vehicle_recalled');
        $this->meta['fleet_vehicle_recalled'] = ['status' => 'APPROVED', 'reason' => null, 'body' => $template->body];
        $this->fleetApi('POST', "admin/messages/templates/{$template->id}/wording", ['body' => 'Hi {{1}}, return {{2}} by {{3}} or else.'])->assertOk();

        $this->meta['fleet_vehicle_recalled_v2'] = ['status' => 'REJECTED', 'reason' => 'ABUSIVE_CONTENT', 'body' => null];
        $this->fleetApi('POST', "admin/messages/templates/{$template->id}/refresh")
            ->assertJsonPath('template.name', 'fleet_vehicle_recalled')
            ->assertJsonPath('template.status', 'approved')
            ->assertJsonPath('template.draft.status', 'rejected')
            ->assertJsonPath('template.draft.reason', 'Meta found the content abusive.');

        $this->fleetApi('DELETE', "admin/messages/templates/{$template->id}/draft")->assertJsonPath('template.draft', null);
    }

    public function test_pointing_at_a_whatsapp_manager_template_picks_up_its_wording_and_checks_it_fits(): void
    {
        $this->fakeWhatsApp(testMode: false);
        $this->actingAsFleetAdmin('super_admin');
        $template = $this->template('evaluation_missed');

        $this->meta['koh_missed_video'] = ['status' => 'APPROVED', 'reason' => null, 'body' => 'Dear {{1}}, the check for {{2}} due {{3}} is missing.'];
        $this->fleetApi('PATCH', "admin/messages/templates/{$template->id}", ['name' => 'koh_missed_video'])
            ->assertJsonPath('template.status', 'approved')
            ->assertJsonPath('template.body', 'Dear {{1}}, the check for {{2}} due {{3}} is missing.');

        $this->meta['koh_missed_short'] = ['status' => 'APPROVED', 'reason' => null, 'body' => 'Dear {{1}}, a video check is missing.'];
        $this->fleetApi('PATCH', "admin/messages/templates/{$template->id}", ['name' => 'koh_missed_short'])
            ->assertJsonPath('template.status', 'mismatch');

        $this->fleetApi('PATCH', "admin/messages/templates/{$template->id}", ['name' => 'not_in_meta'])
            ->assertJsonPath('template.status', 'not_submitted');
    }

    public function test_meta_settings_missing_is_explained(): void
    {
        $this->app->forgetInstance(WhatsAppClient::class);
        $this->app->offsetUnset(WhatsAppClient::class);
        config(['fleet.whatsapp.driver' => 'cloud', 'fleet.whatsapp.access_token' => 'token', 'fleet.whatsapp.business_account_id' => null]);
        $this->actingAsFleetAdmin('super_admin');

        $this->fleetApi('POST', "admin/messages/templates/{$this->template('evaluation_due')->id}/refresh")
            ->assertJsonValidationErrors('meta')
            ->assertJsonPath('errors.meta.0', fn ($message) => str_contains($message, 'FLEET_WHATSAPP_BUSINESS_ACCOUNT_ID'));
    }

    public function test_super_admins_create_custom_messages(): void
    {
        $this->fakeWhatsApp(testMode: false);
        $this->actingAsFleetAdmin('super_admin');

        $this->fleetApi('POST', 'admin/messages/templates', [
            'title' => 'Meeting notice',
            'name' => 'fleet_meeting_notice',
            'language' => 'en',
            'category' => 'UTILITY',
            'body' => 'Hello {{1}}, there is a beneficiaries meeting on {{2}} at {{3}}. Please attend.',
            'variables' => [['label' => 'Date', 'example' => 'Sat 5 Dec'], ['label' => 'Venue', 'example' => "The campaign\noffice"]],
        ])
            ->assertCreated()
            ->assertJsonPath('template.status', 'not_submitted')
            ->assertJsonPath('template.can_send', false)
            ->assertJsonPath('template.draft.status', 'pending')
            ->assertJsonPath('template.draft.name', 'fleet_meeting_notice');

        // Examples go to Meta tidied, with the first name first
        $this->assertSame(['Adebayo', 'Sat 5 Dec', 'The campaign office'], $this->submitted[0][4]);

        $this->fleetApi('POST', 'admin/messages/templates', [
            'title' => 'Bad', 'name' => 'Fleet Notice', 'language' => 'english', 'category' => 'UTILITY',
            'body' => 'Hello {{1}}, see {{2}}.', 'variables' => [],
        ])->assertJsonValidationErrors(['name', 'language', 'body']);
    }

    public function test_only_super_admins_change_templates(): void
    {
        $this->actingAsFleetAdmin('fleet_officer');
        $template = $this->template('evaluation_due');

        $this->fleetApi('PATCH', "admin/messages/templates/{$template->id}", ['enabled' => false])->assertForbidden();
        $this->fleetApi('POST', "admin/messages/templates/{$template->id}/wording", ['body' => 'x'])->assertForbidden();
        $this->fleetApi('GET', 'admin/messages/templates')->assertOk();
    }

    public function test_admins_send_a_message_to_one_beneficiary(): void
    {
        $admin = $this->actingAsFleetAdmin('fleet_officer');
        $beneficiary = Beneficiary::factory()->create(['full_name' => 'Tunde Bakare', 'phone' => '+2348035550000']);
        $this->general()->update(['status' => 'approved']);

        $this->fleetApi('POST', 'admin/messages/send', [
            'template_id' => $this->general()->id,
            'values' => ["Please bring your vehicle\nfor servicing on Monday"],
            'audience' => 'beneficiary',
            'beneficiary_id' => $beneficiary->id,
        ])->assertCreated()->assertJsonPath('queued', 1);

        $this->assertSame([
            'phone' => '+2348035550000',
            'template' => 'fleet_general_message',
            'parameters' => ['Tunde', 'Please bring your vehicle for servicing on Monday'],
        ], $this->sent[0]);

        $notification = Notification::sole();
        $this->assertSame('Hello Tunde, here is a message from the KOH Fleet team: Please bring your vehicle for servicing on Monday. Reply to this number if you have any questions.', $notification->body);

        $this->fleetApi('GET', "admin/beneficiaries/{$beneficiary->id}")
            ->assertJsonPath('notifications.0.title', 'General message')
            ->assertJsonPath('notifications.0.sent_by', $admin->name);
        $this->fleetApi('GET', 'admin/messages?search=tunde')
            ->assertJsonPath('total', 1)
            ->assertJsonPath('data.0.beneficiary.full_name', 'Tunde Bakare');
    }

    public function test_admins_send_to_a_group_and_suspended_beneficiaries_are_left_out(): void
    {
        $this->actingAsFleetAdmin('super_admin');
        $this->general()->update(['status' => 'approved']);
        $this->assignment(['lga' => 'Ikeja', 'phone' => '+2348030000001']);
        $this->assignment(['lga' => 'Ikeja', 'phone' => '+2348030000002'])->beneficiary->forceFill(['deactivated_at' => now()])->save();
        $this->assignment(['lga' => 'Epe', 'phone' => '+2348030000003']);
        Beneficiary::factory()->create(['lga' => 'Ikeja', 'phone' => '+2348030000004']); // no vehicle

        $audience = fn (array $query) => $this->fleetApi('GET', 'admin/messages/audience?'.http_build_query($query))->json('count');
        $this->assertSame(1, $audience(['audience' => 'with_vehicle', 'lga' => 'Ikeja']));
        $this->assertSame(2, $audience(['audience' => 'with_vehicle']));
        $this->assertSame(3, $audience(['audience' => 'all']));

        $this->fleetApi('POST', 'admin/messages/send', [
            'template_id' => $this->general()->id, 'values' => ['Meeting on Saturday'], 'audience' => 'all', 'lga' => 'Ikeja',
        ])->assertJsonPath('queued', 2);

        $this->assertEqualsCanonicalizing(['+2348030000001', '+2348030000004'], array_column($this->sent, 'phone'));
        $this->assertSame(1, Notification::distinct()->count('batch'));
    }

    public function test_messages_meta_has_not_approved_cannot_be_sent(): void
    {
        $this->actingAsFleetAdmin('super_admin');
        $beneficiary = Beneficiary::factory()->create();
        $send = fn (array $data = []) => $this->fleetApi('POST', 'admin/messages/send', $data + [
            'template_id' => $this->general()->id, 'values' => ['Hello'], 'audience' => 'beneficiary', 'beneficiary_id' => $beneficiary->id,
        ]);

        $this->general()->update(['status' => 'pending']);
        $send()->assertJsonValidationErrors(['template_id' => 'Meta has not approved this message yet, so it cannot be sent.']);

        $this->general()->update(['status' => 'approved']);
        $send(['values' => []])->assertJsonValidationErrors('values');
        $send(['template_id' => $this->template('evaluation_due')->id])->assertJsonValidationErrors('template_id');
        $this->assertSame([], $this->sent);
    }

    public function test_reviewers_cannot_send_messages(): void
    {
        $this->actingAsFleetAdmin('reviewer');

        $this->fleetApi('POST', 'admin/messages/send', ['template_id' => $this->general()->id, 'values' => ['Hi'], 'audience' => 'all'])->assertForbidden();
        $this->fleetApi('GET', 'admin/messages')->assertOk();
    }
}
