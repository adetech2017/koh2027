<?php

namespace App\Services;

use App\Models\Material;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Smalot\PdfParser\Parser;
use Throwable;

/**
 * Extracts the text of manifesto PDFs so ManifestoAssistant can ground its answers in them.
 */
class ManifestoTextExtractor
{
    /**
     * Pillar documents feed the assistant. The full manifesto is skipped because it repeats them.
     */
    public static function eligible(Material $material): bool
    {
        return $material->category === 'manifesto'
            && strtolower($material->file_type) === 'pdf'
            && !str_starts_with($material->title, 'Full Campaign Manifesto');
    }

    /**
     * Returns true on success. Failures are logged and leave extracted_text empty.
     */
    public function extract(Material $material): bool
    {
        if (!self::eligible($material) || !Storage::disk('local')->exists($material->file_path)) {
            return false;
        }

        try {
            $text = (new Parser())->parseFile(Storage::disk('local')->path($material->file_path))->getText();
        } catch (Throwable $e) {
            Log::warning("Manifesto text extraction failed for material {$material->id}: {$e->getMessage()}");

            return false;
        }

        $material->forceFill([
            'extracted_text' => trim($text),
            'extracted_at' => now(),
        ])->save();

        return true;
    }
}
