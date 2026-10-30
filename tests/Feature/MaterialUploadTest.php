<?php

namespace Tests\Feature;

use App\Models\Material;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class MaterialUploadTest extends TestCase
{
    use RefreshDatabase;

    private Material $material;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');
        Storage::fake('public');
        Storage::disk('local')->put('materials/old.pdf', 'old');

        $this->material = Material::create([
            'title' => 'Full Campaign Manifesto 2027',
            'description' => 'Complete manifesto',
            'category' => 'manifesto',
            'file_path' => 'materials/old.pdf',
            'file_name' => 'old.pdf',
            'file_type' => 'pdf',
            'file_size' => 3,
            'is_active' => true,
        ]);

        $this->actingAs(User::factory()->create(['role' => 'admin']));
    }

    protected function tearDown(): void
    {
        chmod(Storage::disk('local')->path('materials'), 0755);

        parent::tearDown();
    }

    private function replaceFile(): \Illuminate\Testing\TestResponse
    {
        return $this->put(route('admin.materials.update', $this->material), [
            'title' => $this->material->title,
            'description' => $this->material->description,
            'category' => 'manifesto',
            'is_active' => true,
            'file' => UploadedFile::fake()->create('new.pdf', 100, 'application/pdf'),
        ]);
    }

    public function test_replacing_the_file_saves_the_new_one_and_removes_the_old(): void
    {
        $this->replaceFile()->assertRedirect(route('admin.materials.index'))->assertSessionHasNoErrors();

        $this->material->refresh();
        $this->assertSame('new.pdf', $this->material->file_name);
        Storage::disk('local')->assertExists($this->material->file_path);
        Storage::disk('local')->assertMissing('materials/old.pdf');
    }

    public function test_a_file_the_server_cannot_save_is_reported_and_keeps_the_old_file(): void
    {
        // Mirrors production: a materials folder the web server can't write to
        chmod(Storage::disk('local')->path('materials'), 0555);

        $this->replaceFile()->assertSessionHasErrors('file');

        $this->material->refresh();
        $this->assertSame('materials/old.pdf', $this->material->file_path);
        Storage::disk('local')->assertExists('materials/old.pdf');
    }
}
