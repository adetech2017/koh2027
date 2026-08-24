<?php

namespace App\Console\Commands;

use App\Models\Material;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Smalot\PdfParser\Parser;

#[Signature('manifesto:extract-text {--fresh : Re-extract even materials that already have extracted text}')]
#[Description('Extract text from the manifesto pillar PDFs so the chatbot can answer questions grounded in them')]
class ExtractManifestoText extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $materials = Material::query()
            ->where('category', 'manifesto')
            ->where('file_type', 'pdf')
            ->where('title', 'not like', 'Full Campaign Manifesto%')
            ->when(!$this->option('fresh'), fn ($query) => $query->whereNull('extracted_text'))
            ->get();

        if ($materials->isEmpty()) {
            $this->info('Nothing to extract. Pass --fresh to re-extract already-processed materials.');
            return self::SUCCESS;
        }

        $parser = new Parser();

        foreach ($materials as $material) {
            if (!Storage::disk('local')->exists($material->file_path)) {
                $this->warn("Skipping \"{$material->title}\" — file not found at {$material->file_path}");
                continue;
            }

            $this->line("Extracting \"{$material->title}\"...");

            $text = $parser->parseFile(Storage::disk('local')->path($material->file_path))->getText();

            $material->forceFill([
                'extracted_text' => trim($text),
                'extracted_at' => now(),
            ])->save();
        }

        $this->info("Extracted text for {$materials->count()} material(s).");
        return self::SUCCESS;
    }
}
