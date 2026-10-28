<?php

namespace App\Services\Fleet;

use Illuminate\Http\UploadedFile;

/**
 * Reads an uploaded CSV (e.g. saved from Excel) into rows keyed by snake_case header.
 */
class CsvReader
{
    /**
     * @return array<int, array<string, string|null>> Keyed by the row's line number in the file
     */
    public function read(UploadedFile $file): array
    {
        $handle = fopen($file->getRealPath(), 'r');
        $headers = null;
        $rows = [];
        $line = 0;

        while (($cells = fgetcsv($handle, escape: '')) !== false) {
            $line++;

            if ($headers === null) {
                // Excel adds a byte-order mark to UTF-8 CSVs
                $cells[0] = preg_replace('/^\xEF\xBB\xBF/', '', (string) $cells[0]);
                // "Full Name", "LGA", "ID Type" → full_name, lga, id_type
                $headers = array_map(fn ($header) => trim(preg_replace('/[^a-z0-9]+/', '_', strtolower((string) $header)), '_'), $cells);

                continue;
            }

            // Skip blank lines, including rows of empty cells Excel leaves behind
            if (count(array_filter($cells, fn ($cell) => trim((string) $cell) !== '')) === 0) {
                continue;
            }

            $row = [];
            foreach ($headers as $index => $header) {
                $value = trim((string) ($cells[$index] ?? ''));
                $row[$header] = $value === '' ? null : $value;
            }
            $rows[$line] = $row;
        }

        fclose($handle);

        return $rows;
    }
}
