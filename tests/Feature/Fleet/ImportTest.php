<?php

namespace Tests\Feature\Fleet;

use Illuminate\Http\UploadedFile;

class ImportTest extends FleetTestCase
{
    private function csv(string $content): UploadedFile
    {
        return UploadedFile::fake()->createWithContent('import.csv', $content);
    }

    public function test_beneficiaries_import_from_an_excel_csv(): void
    {
        $this->actingAsFleetAdmin();

        // Excel's byte-order mark, header names as people write them, and a blank trailing row
        $file = $this->csv("\xEF\xBB\xBFFull Name,Phone,LGA,ID Type,ID Number\nAda Obi,0803 123 4567,ikeja,Voter's card,123\nBola Ade,+2349011112222,Surulere,nin,456\n,,,,\n");

        $this->post($this->fleetUrl('api/v1/admin/import/beneficiaries'), ['file' => $file], ['Accept' => 'application/json'])
            ->assertOk()
            ->assertJsonPath('imported', 2);

        $this->assertDatabaseHas('fleet_beneficiaries', ['full_name' => 'Ada Obi', 'phone' => '+2348031234567', 'lga' => 'Ikeja', 'id_type' => 'voters_card']);
    }

    public function test_an_import_with_any_bad_row_imports_nothing_and_lists_every_problem(): void
    {
        $this->actingAsFleetAdmin();

        $file = $this->csv("full_name,phone\nAda Obi,08031234567\nNo Phone,\nDuplicate,0803 123 4567\n");

        $this->post($this->fleetUrl('api/v1/admin/import/beneficiaries'), ['file' => $file], ['Accept' => 'application/json'])
            ->assertStatus(422)
            ->assertJsonPath('row_errors.0.line', 3)
            ->assertJsonPath('row_errors.1.line', 4)
            ->assertJsonPath('row_errors.1.errors.0', 'Same phone number as line 2.');

        $this->assertDatabaseCount('fleet_beneficiaries', 0);
    }

    public function test_vehicles_import_and_duplicate_plates_are_caught(): void
    {
        $this->actingAsFleetAdmin();

        $ok = $this->csv("plate_number,vehicle_type,make,model,year\nKJA 123 XY,Car,Toyota,Corolla,2020\nLND-456-AB,bus,Toyota,Hiace,2019\n");
        $this->post($this->fleetUrl('api/v1/admin/import/vehicles'), ['file' => $ok], ['Accept' => 'application/json'])->assertOk()->assertJsonPath('imported', 2);

        $again = $this->csv("plate_number,vehicle_type,make,model\nkja123xy,car,Toyota,Corolla\n");
        $this->post($this->fleetUrl('api/v1/admin/import/vehicles'), ['file' => $again], ['Accept' => 'application/json'])
            ->assertStatus(422)
            ->assertJsonPath('row_errors.0.errors.0', 'A vehicle with this plate number is already registered.');
    }

    public function test_a_file_without_the_required_columns_is_rejected(): void
    {
        $this->actingAsFleetAdmin();

        $this->post($this->fleetUrl('api/v1/admin/import/vehicles'), ['file' => $this->csv("plate,make\nKJA123XY,Toyota\n")], ['Accept' => 'application/json'])
            ->assertStatus(422)
            ->assertJsonFragment(['message' => 'The file is missing these columns: plate_number, vehicle_type, model. Download the template to see the expected layout.']);
    }

    public function test_templates_download(): void
    {
        $this->actingAsFleetAdmin('reviewer');

        $response = $this->get($this->fleetUrl('api/v1/admin/import/vehicles/template'));
        $response->assertOk();
        $this->assertStringStartsWith('plate_number,vehicle_type,make,model', $response->streamedContent());
    }
}
