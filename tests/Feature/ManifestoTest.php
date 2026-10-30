<?php

namespace Tests\Feature;

use App\Models\Material;
use App\Services\ManifestoTextExtractor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ManifestoTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');
        $this->withoutVite();
    }

    private function document(string $title, string $file = 'materials/doc.pdf'): Material
    {
        Storage::disk('local')->put($file, '%PDF-1.6 not a real pdf');

        return Material::create([
            'title' => $title,
            'description' => 'A manifesto document',
            'category' => 'manifesto',
            'file_path' => $file,
            'file_name' => basename($file),
            'file_type' => 'pdf',
            'file_size' => 23,
            'is_active' => true,
        ]);
    }

    public function test_the_manifesto_page_lives_at_manifesto(): void
    {
        $this->get('/manifesto')->assertOk();
    }

    public function test_the_homepage_offers_the_manifesto_only_while_a_manifesto_document_is_switched_on(): void
    {
        $this->get('/')->assertInertia(fn ($page) => $page->where('manifestoAvailable', false));

        $full = $this->document('Full Campaign Manifesto 2027');
        $this->get('/')->assertInertia(fn ($page) => $page->where('manifestoAvailable', true));

        $full->update(['is_active' => false]);
        $this->get('/')->assertInertia(fn ($page) => $page->where('manifestoAvailable', false));
    }

    public function test_old_materials_links_redirect_and_keep_their_tracking_tags(): void
    {
        $location = $this->get('/materials?utm_source=whatsapp&utm_campaign=launch')
            ->assertStatus(301)
            ->headers->get('Location');

        $this->assertSame('/manifesto', parse_url($location, PHP_URL_PATH));
        parse_str(parse_url($location, PHP_URL_QUERY), $query);
        $this->assertEquals(['utm_source' => 'whatsapp', 'utm_campaign' => 'launch'], $query);

        $this->get('/materials')->assertStatus(301)->assertRedirect('/manifesto');
        $this->get('/materials/7/download')->assertStatus(301)->assertRedirect('/manifesto/7/download');
    }

    public function test_documents_download_from_the_new_address(): void
    {
        $material = $this->document('Full Campaign Manifesto 2027');

        $this->get("/manifesto/{$material->id}/download")->assertOk()->assertDownload('doc.pdf');
        $this->assertSame(1, $material->fresh()->download_count);
    }

    public function test_the_assistant_reads_the_full_manifesto_and_not_the_pillar_documents(): void
    {
        $full = $this->document('Full Campaign Manifesto 2027');
        $pillar = $this->document('The Lagos Promise — Pillar 1: People First', 'materials/pillar.pdf');

        $this->assertTrue(ManifestoTextExtractor::eligible($full));
        $this->assertFalse(ManifestoTextExtractor::eligible($pillar));
    }

    public function test_copy_protected_pdfs_are_read_with_pdftotext(): void
    {
        Process::fake(['*pdftotext*' => Process::result("Preface\fI am an engineer.\n\n\n\nA promise only has value when it is kept.")]);
        $full = $this->document('Full Campaign Manifesto 2027');

        $this->assertTrue(app(ManifestoTextExtractor::class)->extract($full));

        $this->assertSame("Preface\n\nI am an engineer.\n\nA promise only has value when it is kept.", $full->fresh()->extracted_text);
        Process::assertRan(fn ($process) => in_array(Storage::disk('local')->path('materials/doc.pdf'), $process->command, true));
    }

    public function test_extraction_fails_cleanly_when_pdftotext_is_missing_and_the_parser_cannot_read_the_file(): void
    {
        Process::fake(['*' => Process::result(errorOutput: 'command not found', exitCode: 127)]);
        $full = $this->document('Full Campaign Manifesto 2027');

        $this->assertFalse(app(ManifestoTextExtractor::class)->extract($full));
        $this->assertNull($full->fresh()->extracted_text);
    }
}
