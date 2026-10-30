<?php

namespace Tests\Feature\Fleet;

use App\Models\Fleet\Beneficiary;
use Illuminate\Http\UploadedFile;

class DirectorateTest extends FleetTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config(['fleet.directorates' => [
            'Directorate of Mobilisation' => ['Youth Mobilisation', 'Women Mobilisation'],
            'Directorate of Media' => [],
        ]]);
        $this->actingAsFleetAdmin();
    }

    public function test_the_admin_app_gets_directorates_with_their_sub_directorates(): void
    {
        $this->fleetApi('GET', 'admin/options')
            ->assertJsonPath('directorates.Directorate of Mobilisation', ['Youth Mobilisation', 'Women Mobilisation'])
            ->assertJsonPath('directorates.Directorate of Media', []);
    }

    public function test_a_beneficiary_can_be_given_a_directorate_and_sub_directorate(): void
    {
        $this->fleetApi('POST', 'admin/beneficiaries', [
            'full_name' => 'Ada Obi', 'phone' => '08031234567',
            'directorate' => 'Directorate of Mobilisation', 'sub_directorate' => 'Women Mobilisation',
        ])->assertCreated()
            ->assertJsonPath('beneficiary.directorate', 'Directorate of Mobilisation')
            ->assertJsonPath('beneficiary.sub_directorate', 'Women Mobilisation');

        // Both are optional, and a directorate without sub-directorates needs none
        $this->fleetApi('POST', 'admin/beneficiaries', ['full_name' => 'Bola Ade', 'phone' => '08031111111'])->assertCreated();
        $this->fleetApi('POST', 'admin/beneficiaries', ['full_name' => 'Chi Eze', 'phone' => '08032222222', 'directorate' => 'Directorate of Media'])
            ->assertCreated();
    }

    public function test_sub_directorates_must_belong_to_the_chosen_directorate(): void
    {
        $post = fn (array $fields) => $this->fleetApi('POST', 'admin/beneficiaries', ['full_name' => 'X', 'phone' => '08031234567'] + $fields);

        $post(['directorate' => 'Directorate of Finance'])->assertJsonValidationErrors('directorate');
        $post(['directorate' => 'Directorate of Mobilisation', 'sub_directorate' => 'Press'])
            ->assertJsonValidationErrors(['sub_directorate' => 'Choose a sub-directorate of Directorate of Mobilisation.']);
        $post(['directorate' => 'Directorate of Media', 'sub_directorate' => 'Youth Mobilisation'])
            ->assertJsonValidationErrors(['sub_directorate' => 'Directorate of Media has no sub-directorates.']);
        $post(['sub_directorate' => 'Youth Mobilisation'])
            ->assertJsonValidationErrors(['sub_directorate' => 'Choose a directorate first.']);
    }

    public function test_editing_checks_the_sub_directorate_against_the_saved_directorate_and_clears_it_on_a_move(): void
    {
        $beneficiary = Beneficiary::factory()->create(['directorate' => 'Directorate of Mobilisation', 'sub_directorate' => 'Youth Mobilisation']);

        $this->fleetApi('PATCH', "admin/beneficiaries/{$beneficiary->id}", ['sub_directorate' => 'Women Mobilisation'])
            ->assertOk()->assertJsonPath('beneficiary.sub_directorate', 'Women Mobilisation');

        $this->fleetApi('PATCH', "admin/beneficiaries/{$beneficiary->id}", ['directorate' => 'Directorate of Media'])
            ->assertOk()
            ->assertJsonPath('beneficiary.directorate', 'Directorate of Media')
            ->assertJsonPath('beneficiary.sub_directorate', null);
    }

    public function test_the_csv_import_matches_directorate_names_regardless_of_case(): void
    {
        $csv = "full_name,phone,directorate,sub_directorate\n"
            ."Ada Obi,08031234567,directorate of mobilisation,youth mobilisation\n"
            ."Bola Ade,08031111111,DIRECTORATE OF MEDIA,\n";

        $this->post($this->fleetUrl('api/v1/admin/import/beneficiaries'), ['file' => UploadedFile::fake()->createWithContent('b.csv', $csv)], ['Accept' => 'application/json'])
            ->assertOk()->assertJsonPath('imported', 2);

        $this->assertDatabaseHas('fleet_beneficiaries', ['full_name' => 'Ada Obi', 'directorate' => 'Directorate of Mobilisation', 'sub_directorate' => 'Youth Mobilisation']);
        $this->assertDatabaseHas('fleet_beneficiaries', ['full_name' => 'Bola Ade', 'directorate' => 'Directorate of Media', 'sub_directorate' => null]);
    }
}
