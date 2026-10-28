<?php

namespace Tests\Feature\Fleet;

use App\Models\Fleet\Beneficiary;
use App\Models\Fleet\Vehicle;

class VehicleAndBeneficiaryTest extends FleetTestCase
{
    public function test_an_officer_can_register_a_vehicle_and_plates_are_normalised(): void
    {
        $this->actingAsFleetAdmin();

        $this->fleetApi('POST', 'admin/vehicles', [
            'plate_number' => 'kja-123 xy', 'vehicle_type' => 'car', 'make' => 'Toyota', 'model' => 'Corolla', 'year' => 2020,
        ])->assertCreated()->assertJsonPath('vehicle.plate_number', 'KJA 123 XY');

        $this->assertDatabaseHas('fleet_vehicles', ['plate_number' => 'KJA123XY', 'status' => 'available']);

        // The same plate typed differently is a duplicate
        $this->fleetApi('POST', 'admin/vehicles', [
            'plate_number' => 'KJA 123XY', 'vehicle_type' => 'car', 'make' => 'Toyota', 'model' => 'Camry',
        ])->assertJsonValidationErrors('plate_number');
    }

    public function test_vehicles_can_be_searched_by_plate(): void
    {
        $this->actingAsFleetAdmin('reviewer');
        Vehicle::factory()->create(['plate_number' => 'KJA123XY']);
        Vehicle::factory()->create(['plate_number' => 'LND456AB']);

        $this->fleetApi('GET', 'admin/vehicles?search=kja 123')->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.plate_number', 'KJA 123 XY');
    }

    public function test_an_assigned_vehicle_cannot_be_set_to_maintenance_by_hand(): void
    {
        $this->actingAsFleetAdmin();
        $vehicle = Vehicle::factory()->create(['status' => 'assigned']);

        $this->fleetApi('PATCH', "admin/vehicles/{$vehicle->id}", ['status' => 'maintenance'])->assertJsonValidationErrors('status');
        $this->fleetApi('PATCH', "admin/vehicles/{$vehicle->id}", ['status' => 'assigned'])->assertJsonValidationErrors('status');
    }

    public function test_an_officer_can_register_a_beneficiary_with_a_local_phone_number(): void
    {
        $this->actingAsFleetAdmin();

        $this->fleetApi('POST', 'admin/beneficiaries', ['full_name' => 'Ada Obi', 'phone' => '0803 123 4567', 'lga' => 'Ikeja'])
            ->assertCreated()
            ->assertJsonPath('beneficiary.phone', '+2348031234567');

        $this->fleetApi('POST', 'admin/beneficiaries', ['full_name' => 'Someone Else', 'phone' => '+234 803 123 4567'])
            ->assertJsonValidationErrors(['phone' => 'Another beneficiary already has this phone number.']);
        $this->fleetApi('POST', 'admin/beneficiaries', ['full_name' => 'Bad', 'phone' => '1234'])
            ->assertJsonValidationErrors('phone');
        $this->fleetApi('POST', 'admin/beneficiaries', ['full_name' => 'Bad LGA', 'phone' => '08031111111', 'lga' => 'Abuja'])
            ->assertJsonValidationErrors('lga');
    }

    public function test_beneficiaries_can_be_found_by_partial_phone_number(): void
    {
        $this->actingAsFleetAdmin('reviewer');
        Beneficiary::factory()->create(['phone' => '+2348031234567', 'full_name' => 'Ada Obi']);
        Beneficiary::factory()->create(['phone' => '+2349099999999', 'full_name' => 'Bola Ade']);

        $this->fleetApi('GET', 'admin/beneficiaries?search=0803 123')->assertJsonCount(1, 'data')->assertJsonPath('data.0.full_name', 'Ada Obi');
    }

    public function test_a_beneficiary_can_be_suspended_and_reinstated(): void
    {
        $this->actingAsFleetAdmin();
        $beneficiary = Beneficiary::factory()->create();

        $this->fleetApi('PATCH', "admin/beneficiaries/{$beneficiary->id}", ['active' => false])->assertJsonPath('beneficiary.active', false);
        $this->assertFalse($beneficiary->fresh()->isActive());

        $this->fleetApi('PATCH', "admin/beneficiaries/{$beneficiary->id}", ['active' => true])->assertJsonPath('beneficiary.active', true);
    }

    public function test_reviewers_can_view_but_not_change_records(): void
    {
        $this->actingAsFleetAdmin('reviewer');
        $vehicle = Vehicle::factory()->create();

        $this->fleetApi('GET', 'admin/vehicles')->assertOk();
        $this->fleetApi('GET', "admin/vehicles/{$vehicle->id}")->assertOk();
        $this->fleetApi('PATCH', "admin/vehicles/{$vehicle->id}", ['make' => 'Honda'])->assertForbidden();
        $this->fleetApi('POST', 'admin/beneficiaries', ['full_name' => 'X', 'phone' => '08031234567'])->assertForbidden();
    }

    public function test_guests_and_beneficiaries_cannot_reach_admin_records(): void
    {
        $this->fleetApi('GET', 'admin/vehicles')->assertUnauthorized();

        $this->actingAs(Beneficiary::factory()->create(), 'fleet_beneficiary');
        $this->fleetApi('GET', 'admin/beneficiaries')->assertUnauthorized();
    }
}
