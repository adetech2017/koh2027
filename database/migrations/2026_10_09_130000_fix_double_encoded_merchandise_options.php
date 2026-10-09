<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * The seeder json_encode()d sizes/colors and the model's array cast encoded them again,
     * so rows hold a JSON string of a JSON array. Unwrap them to plain JSON arrays.
     */
    public function up(): void
    {
        DB::table('merchandises')->orderBy('id')->each(function ($row) {
            $updates = [];
            foreach (['sizes', 'colors'] as $column) {
                if ($row->$column === null) {
                    continue;
                }
                $decoded = json_decode($row->$column, true);
                if (is_string($decoded)) {
                    $inner = json_decode($decoded, true);
                    if (is_array($inner)) {
                        $updates[$column] = json_encode(array_values($inner));
                    }
                }
            }
            if ($updates) {
                DB::table('merchandises')->where('id', $row->id)->update($updates);
            }
        });
    }

    public function down(): void
    {
        // Not reversible: re-wrapping would reintroduce the bug.
    }
};
