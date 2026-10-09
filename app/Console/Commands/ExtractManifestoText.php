<?php

namespace App\Console\Commands;

use App\Models\Material;
use App\Services\ManifestoTextExtractor;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('manifesto:extract-text {--fresh : Re-extract even materials that already have extracted text}')]
#[Description('Extract text from the manifesto pillar PDFs so the chatbot can answer questions grounded in them')]
class ExtractManifestoText extends Command
{
    public function handle(ManifestoTextExtractor $extractor): int
    {
        $materials = Material::query()
            ->when(!$this->option('fresh'), fn ($query) => $query->whereNull('extracted_text'))
            ->get()
            ->filter(fn ($material) => ManifestoTextExtractor::eligible($material));

        if ($materials->isEmpty()) {
            $this->info('Nothing to extract. Pass --fresh to re-extract already-processed materials.');
            return self::SUCCESS;
        }

        $done = 0;
        foreach ($materials as $material) {
            $this->line("Extracting \"{$material->title}\"...");
            if ($extractor->extract($material)) {
                $done++;
            } else {
                $this->warn("Skipped \"{$material->title}\" (file missing or unreadable; see the log).");
            }
        }

        $this->info("Extracted text for {$done} material(s).");
        return self::SUCCESS;
    }
}
