<?php

namespace Tests\Feature\Fleet;

use App\Models\Fleet\Assignment;
use App\Models\Fleet\Beneficiary;
use App\Models\Fleet\Handover;
use App\Models\Fleet\Vehicle;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class HandoverTest extends FleetTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('fleet');
    }

    private function form(array $overrides = []): array
    {
        return array_merge([
            'odometer_km' => 12000,
            'fuel_level' => 'half',
            'checklist' => json_encode($this->checklist(['tyres' => ['status' => 'issue', 'note' => 'Spare is flat']])),
            'signed_by_name' => 'Ada Obi',
            'officer_signature' => $this->signature(),
            'signer_signature' => $this->signature(),
            'latitude' => 6.6018,
            'longitude' => 3.3515,
            'location_accuracy_m' => 12.4,
            'photos' => [UploadedFile::fake()->image('front.jpg', 2400, 1800), UploadedFile::fake()->image('back.jpg')],
        ], $overrides);
    }

    private function postForm(string $path, array $data)
    {
        return $this->post($this->fleetUrl('api/v1/'.$path), $data, ['Accept' => 'application/json']);
    }

    private function handOver(Vehicle $vehicle, Beneficiary $beneficiary): Handover
    {
        $id = $this->postForm('admin/handovers', $this->form(['vehicle_id' => $vehicle->id, 'beneficiary_id' => $beneficiary->id]))
            ->assertCreated()->json('handover.id');

        return Handover::findOrFail($id);
    }

    public function test_handing_over_a_vehicle_starts_an_assignment_with_a_signed_form(): void
    {
        $officer = $this->actingAsFleetAdmin();
        $vehicle = Vehicle::factory()->create();
        $beneficiary = Beneficiary::factory()->create(['full_name' => 'Ada Obi']);

        $handover = $this->handOver($vehicle, $beneficiary);

        $this->assertSame('assigned', $vehicle->fresh()->status);
        $this->assertSame('handover', $handover->type);
        $this->assertSame($officer->id, $handover->officer_id);
        $this->assertSame('Spare is flat', $handover->checklist['tyres']['note']);
        $this->assertSame(12, $handover->location_accuracy_m);
        $this->assertCount(2, $handover->photos);

        Storage::disk('fleet')->assertExists([$handover->officer_signature_path, $handover->signer_signature_path, $handover->photos[0]->path]);
        // Photos are shrunk to at most 1600px
        [$width] = getimagesizefromstring(Storage::disk('fleet')->get($handover->photos[0]->path));
        $this->assertSame(1600, $width);

        $this->fleetApi('GET', 'admin/assignments?status=active')->assertJsonCount(1, 'data')->assertJsonPath('data.0.beneficiary.full_name', 'Ada Obi');
        $this->assertDatabaseHas('fleet_audit_logs', ['action' => 'handover.created', 'actor_id' => $officer->id]);
    }

    public function test_a_vehicle_cannot_be_handed_out_twice_and_no_files_are_left_behind(): void
    {
        $this->actingAsFleetAdmin();
        $vehicle = Vehicle::factory()->create();
        $this->handOver($vehicle, Beneficiary::factory()->create());
        $filesBefore = count(Storage::disk('fleet')->allFiles());

        $this->postForm('admin/handovers', $this->form(['vehicle_id' => $vehicle->id, 'beneficiary_id' => Beneficiary::factory()->create()->id]))
            ->assertJsonValidationErrors('vehicle_id');

        $this->assertSame(1, Assignment::count());
        $this->assertCount($filesBefore, Storage::disk('fleet')->allFiles());
    }

    public function test_the_form_requires_signatures_and_notes_for_problems(): void
    {
        $this->actingAsFleetAdmin();

        $this->postForm('admin/handovers', $this->form([
            'vehicle_id' => Vehicle::factory()->create()->id,
            'beneficiary_id' => Beneficiary::factory()->create()->id,
            'officer_signature' => 'data:image/png;base64,bm90IGEgcG5n',
            'signer_signature' => '',
            'checklist' => json_encode($this->checklist(['glass' => ['status' => 'issue', 'note' => '']])),
        ]))->assertJsonValidationErrors([
            'officer_signature' => 'The signature could not be read. Clear it and sign again.',
            'signer_signature',
            'checklist.glass.note' => 'Describe the problem with “Windscreen, windows and mirrors”.',
        ]);
    }

    public function test_returning_a_vehicle_ends_the_assignment(): void
    {
        $this->actingAsFleetAdmin();
        $vehicle = Vehicle::factory()->create();
        $handover = $this->handOver($vehicle, Beneficiary::factory()->create());
        $assignmentId = $handover->assignment_id;

        // Odometer can't go backwards
        $this->postForm("admin/assignments/{$assignmentId}/return", $this->form(['odometer_km' => 11000, 'vehicle_status' => 'available']))
            ->assertJsonValidationErrors('odometer_km');

        $this->postForm("admin/assignments/{$assignmentId}/return", $this->form(['odometer_km' => 15400, 'vehicle_status' => 'maintenance', 'photos' => []]))
            ->assertCreated()
            ->assertJsonPath('handover.type', 'return')
            ->assertJsonPath('handover.reference', fn ($ref) => str_starts_with($ref, 'RET-'));

        $this->assertSame('returned', Assignment::find($assignmentId)->status);
        $this->assertSame('maintenance', $vehicle->fresh()->status);

        // And can't be returned twice
        $this->postForm("admin/assignments/{$assignmentId}/return", $this->form(['odometer_km' => 15500, 'vehicle_status' => 'available']))
            ->assertJsonValidationErrors('assignment');
    }

    public function test_receipt_photos_and_signatures_are_served_to_admins_only(): void
    {
        $this->actingAsFleetAdmin('reviewer');
        $vehicle = Vehicle::factory()->create();
        $beneficiary = Beneficiary::factory()->create();
        $this->actingAsFleetAdmin();
        $handover = $this->handOver($vehicle, $beneficiary);
        $photo = $handover->photos->first();

        $this->get($this->fleetUrl("api/v1/admin/handovers/{$handover->id}/receipt"))->assertOk()->assertHeader('Content-Type', 'application/pdf');
        $this->get($this->fleetUrl("api/v1/admin/handovers/{$handover->id}/photos/{$photo->id}"))->assertOk();
        $this->get($this->fleetUrl("api/v1/admin/handovers/{$handover->id}/signature/signer"))->assertOk();
        $this->fleetApi('GET', "admin/handovers/{$handover->id}")->assertOk()->assertJsonPath('handover.vehicle.id', $vehicle->id);

        // A photo from another handover can't be fetched through this one
        $other = $this->handOver(Vehicle::factory()->create(), Beneficiary::factory()->create());
        $this->get($this->fleetUrl("api/v1/admin/handovers/{$handover->id}/photos/{$other->photos->first()->id}"))->assertNotFound();

        auth('fleet_admin')->logout();
        $this->fleetApi('GET', "admin/handovers/{$handover->id}/receipt")->assertUnauthorized();
    }

    public function test_the_beneficiary_sees_their_vehicle_and_any_recall(): void
    {
        $this->actingAsFleetAdmin();
        $beneficiary = Beneficiary::factory()->create();
        $handover = $this->handOver(Vehicle::factory()->create(['plate_number' => 'KJA123XY']), $beneficiary);

        $this->fleetApi('POST', 'admin/recalls', [
            'title' => 'End of campaign', 'return_by' => now()->addWeek()->toDateString(), 'assignment_ids' => [$handover->assignment_id],
        ])->assertCreated();

        $this->actingAs($beneficiary, 'fleet_beneficiary');
        $this->fleetApi('GET', 'vehicle')
            ->assertOk()
            ->assertJsonPath('assignment.vehicle.plate_number', 'KJA 123 XY')
            ->assertJsonPath('assignment.recall.title', 'End of campaign');

        $this->actingAs(Beneficiary::factory()->create(), 'fleet_beneficiary');
        $this->fleetApi('GET', 'vehicle')->assertOk()->assertJsonPath('assignment', null);
    }

    public function test_a_recall_tracks_how_many_vehicles_have_come_back(): void
    {
        $this->actingAsFleetAdmin();
        $first = $this->handOver(Vehicle::factory()->create(), Beneficiary::factory()->create());
        $this->handOver(Vehicle::factory()->create(), Beneficiary::factory()->create());

        $recallId = $this->fleetApi('POST', 'admin/recalls', ['title' => 'Batch 1', 'return_by' => now()->addDays(3)->toDateString(), 'all_active' => true])
            ->assertCreated()
            ->assertJsonPath('recall.vehicles_total', 2)
            ->json('recall.id');

        $this->fleetApi('GET', 'admin/assignments?status=recalled')->assertJsonCount(2, 'data');

        // Already-recalled vehicles can't join a second recall
        $this->fleetApi('POST', 'admin/recalls', ['title' => 'Batch 2', 'return_by' => now()->addDays(3)->toDateString(), 'all_active' => true])
            ->assertJsonValidationErrors('assignment_ids');

        $this->postForm("admin/assignments/{$first->assignment_id}/return", $this->form(['odometer_km' => 13000, 'vehicle_status' => 'available', 'photos' => []]))->assertCreated();

        $this->fleetApi('GET', "admin/recalls/{$recallId}")->assertJsonPath('recall.vehicles_returned', 1)->assertJsonPath('recall.overdue', false);

        $this->travel(5)->days();
        $this->fleetApi('GET', "admin/recalls/{$recallId}")->assertJsonPath('recall.overdue', true);
    }
}
