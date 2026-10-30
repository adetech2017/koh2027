<?php

namespace App\Services;

use App\Models\Material;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Storage;
use Smalot\PdfParser\Parser;
use Throwable;

/**
 * Extracts the text of the manifesto PDF so ManifestoAssistant can ground its answers in it.
 */
class ManifestoTextExtractor
{
    /**
     * The full manifesto feeds the assistant. The older per-pillar documents are skipped:
     * the manifesto is the authoritative version and they could contradict it.
     */
    public static function eligible(Material $material): bool
    {
        return $material->category === 'manifesto'
            && strtolower($material->file_type) === 'pdf'
            && !self::isPillarDocument($material);
    }

    // Same rule Admin\MaterialController and Pages/Materials.vue use to place a document under a pillar
    public static function isPillarDocument(Material $material): bool
    {
        return (bool) preg_match('/pillar\s*\d/i', $material->title);
    }

    /**
     * Returns true on success. Failures are logged and leave extracted_text empty.
     */
    public function extract(Material $material): bool
    {
        if (!self::eligible($material) || !Storage::disk('local')->exists($material->file_path)) {
            return false;
        }

        $path = Storage::disk('local')->path($material->file_path);
        $text = $this->withPdftotext($path) ?? $this->withParser($material, $path);

        if (blank($text)) {
            return false;
        }

        $material->forceFill([
            'extracted_text' => $text,
            'extracted_at' => now(),
        ])->save();

        return true;
    }

    /**
     * Poppler's pdftotext also reads copy-protected PDFs (the published manifesto is one),
     * which the PHP parser refuses. Returns null when the tool isn't installed or fails.
     */
    private function withPdftotext(string $path): ?string
    {
        try {
            $result = Process::timeout(120)->run([config('services.pdftotext.path'), '-enc', 'UTF-8', $path, '-']);
        } catch (Throwable) {
            return null;
        }

        if (!$result->successful()) {
            Log::info("pdftotext could not read {$path}: {$result->errorOutput()}");

            return null;
        }

        // Page breaks come through as form feeds
        return trim(preg_replace("/\n{3,}/", "\n\n", str_replace("\f", "\n\n", $result->output())));
    }

    private function withParser(Material $material, string $path): ?string
    {
        try {
            return trim((new Parser())->parseFile($path)->getText());
        } catch (Throwable $e) {
            Log::warning("Manifesto text extraction failed for material {$material->id}: {$e->getMessage()}");

            return null;
        }
    }
}
