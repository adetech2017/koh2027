<?php

namespace Tests\Feature\Fleet;

use App\Models\Fleet\Vehicle;
use App\Models\Fleet\VehicleMake;
use App\Models\Fleet\VehicleModel;
use App\Services\Fleet\VehicleCatalog;
use Illuminate\Http\UploadedFile;

class VehicleCatalogTest extends FleetTestCase
{
    private function vehicle(array $fields = []): \Illuminate\Testing\TestResponse
    {
        return $this->fleetApi('POST', 'admin/vehicles', $fields + ['plate_number' => 'KJA123XY', 'vehicle_type' => 'car', 'make' => 'Toyota', 'model' => 'Corolla']);
    }

    private function model(string $make, string $name): VehicleModel
    {
        return VehicleModel::whereHas('make', fn ($q) => $q->where('name', $make))->where('name', $name)->firstOrFail();
    }

    public function test_the_starting_list_is_loaded_with_each_model_tagged_by_type(): void
    {
        $this->actingAsFleetAdmin('reviewer');

        $toyota = collect($this->fleetApi('GET', 'admin/vehicle-catalog')->assertOk()->json('makes'))->firstWhere('name', 'Toyota');

        $types = collect($toyota['models'])->pluck('vehicle_type', 'name');
        $this->assertSame('car', $types['Corolla']);
        $this->assertSame('bus', $types['Hiace']);
        $this->assertSame('pickup', $types['Hilux']);
    }

    public function test_a_vehicle_must_use_a_listed_make_and_a_model_of_that_make_and_type(): void
    {
        $this->actingAsFleetAdmin();

        $this->vehicle(['make' => 'Toyta'])->assertJsonValidationErrors('make');
        $this->vehicle(['model' => 'Civic'])->assertJsonValidationErrors(['model' => 'Choose a model of Toyota.']);
        $this->vehicle(['model' => 'Hiace'])->assertJsonValidationErrors(['model' => 'Toyota Hiace is listed as a bus, not a car.']);
        $this->vehicle(['colour' => 'Sky blue'])->assertJsonValidationErrors(['colour' => 'Choose a colour from the list.']);
        $this->vehicle(['year' => 1975])->assertJsonValidationErrors('year');

        $this->vehicle(['vehicle_type' => 'bus', 'model' => 'Hiace', 'colour' => 'White', 'year' => 2020])->assertCreated();
    }

    public function test_editing_checks_the_model_against_the_saved_make_and_type(): void
    {
        $this->actingAsFleetAdmin();
        $vehicle = Vehicle::factory()->create(['vehicle_type' => 'car', 'make' => 'Toyota', 'model' => 'Corolla', 'colour' => 'fuchsia']);

        $this->fleetApi('PATCH', "admin/vehicles/{$vehicle->id}", ['model' => 'Camry'])->assertOk();
        // Changing the type alone would leave a car model on a bus
        $this->fleetApi('PATCH', "admin/vehicles/{$vehicle->id}", ['vehicle_type' => 'bus'])->assertJsonValidationErrors('model');
        // A colour recorded before the list existed is kept on edit
        $this->fleetApi('PATCH', "admin/vehicles/{$vehicle->id}", ['colour' => 'fuchsia', 'year' => 2021])->assertOk();
    }

    public function test_only_super_admins_can_change_the_list(): void
    {
        $this->actingAsFleetAdmin('fleet_officer');
        $this->fleetApi('POST', 'admin/vehicle-makes', ['name' => 'Haojue'])->assertForbidden();

        $this->actingAsFleetAdmin('super_admin');
        $make = $this->fleetApi('POST', 'admin/vehicle-makes', ['name' => 'Haojue'])->assertCreated()->json('make');
        $this->fleetApi('POST', 'admin/vehicle-makes', ['name' => 'Haojue'])->assertJsonValidationErrors(['name' => 'This make is already on the list.']);

        $this->fleetApi('POST', "admin/vehicle-makes/{$make['id']}/models", ['name' => 'HJ125', 'vehicle_type' => 'motorcycle'])->assertCreated();
        $this->fleetApi('POST', "admin/vehicle-makes/{$make['id']}/models", ['name' => 'HJ125', 'vehicle_type' => 'motorcycle'])
            ->assertJsonValidationErrors(['name' => 'Haojue already has a model with this name.']);
    }

    public function test_renames_are_carried_onto_vehicles_and_used_entries_cannot_be_deleted(): void
    {
        $this->actingAsFleetAdmin('super_admin');
        $vehicle = Vehicle::factory()->create(['make' => 'Toyota', 'model' => 'Corolla']);
        $toyota = VehicleMake::where('name', 'Toyota')->first();
        $corolla = $this->model('Toyota', 'Corolla');

        $this->fleetApi('PATCH', "admin/vehicle-models/{$corolla->id}", ['name' => 'Corolla Altis'])->assertOk();
        $this->fleetApi('PATCH', "admin/vehicle-makes/{$toyota->id}", ['name' => 'Toyota Motors'])->assertOk();
        $this->assertSame(['Toyota Motors', 'Corolla Altis'], [$vehicle->fresh()->make, $vehicle->fresh()->model]);

        $this->fleetApi('PATCH', "admin/vehicle-models/{$corolla->id}", ['vehicle_type' => 'suv'])->assertJsonValidationErrors('vehicle_type');
        $this->fleetApi('DELETE', "admin/vehicle-models/{$corolla->id}")->assertJsonValidationErrors('name');
        $this->fleetApi('DELETE', "admin/vehicle-makes/{$toyota->id}")->assertJsonValidationErrors('name');

        $this->fleetApi('DELETE', 'admin/vehicle-models/'.$this->model('Toyota Motors', 'Yaris')->id)->assertOk();
        $this->assertDatabaseMissing('fleet_vehicle_models', ['name' => 'Yaris']);
    }

    public function test_existing_vehicles_are_added_to_the_list_and_their_spelling_tidied(): void
    {
        Vehicle::factory()->create(['make' => 'toyota', 'model' => 'COROLLA']);
        Vehicle::factory()->create(['vehicle_type' => 'tricycle', 'make' => 'Sinoki', 'model' => 'SK150']);

        VehicleCatalog::absorbExistingVehicles();

        $this->assertDatabaseHas('fleet_vehicles', ['make' => 'Toyota', 'model' => 'Corolla']);
        $this->assertSame('tricycle', $this->model('Sinoki', 'SK150')->vehicle_type);
    }

    public function test_the_csv_import_matches_makes_models_and_colours_regardless_of_case(): void
    {
        $this->actingAsFleetAdmin();
        $csv = "plate_number,vehicle_type,make,model,colour\nKJA 123 XY,car,toyota,COROLLA,white\nLND 456 AB,car,Toyota,Hiace,\n";

        $this->post($this->fleetUrl('api/v1/admin/import/vehicles'), ['file' => UploadedFile::fake()->createWithContent('v.csv', $csv)], ['Accept' => 'application/json'])
            ->assertStatus(422)
            ->assertJsonPath('row_errors.0.line', 3)
            ->assertJsonPath('row_errors.0.errors.0', 'Toyota Hiace is listed as a bus, not a car.');

        $csv = "plate_number,vehicle_type,make,model,colour\nKJA 123 XY,car,toyota,COROLLA,white\n";
        $this->post($this->fleetUrl('api/v1/admin/import/vehicles'), ['file' => UploadedFile::fake()->createWithContent('v.csv', $csv)], ['Accept' => 'application/json'])
            ->assertOk();
        $this->assertDatabaseHas('fleet_vehicles', ['plate_number' => 'KJA123XY', 'make' => 'Toyota', 'model' => 'Corolla', 'colour' => 'White']);
    }
}
