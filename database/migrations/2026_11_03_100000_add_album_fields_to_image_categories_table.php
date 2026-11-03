<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

// Gallery categories become event albums: each gets a public URL, an event date and a description
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('image_categories', function (Blueprint $table) {
            $table->string('slug')->nullable()->after('name');
            $table->date('event_date')->nullable()->after('slug');
            $table->text('description')->nullable()->after('event_date');
        });

        $used = [];
        foreach (DB::table('image_categories')->orderBy('id')->get(['id', 'name']) as $row) {
            $base = Str::slug($row->name) ?: 'album';
            $slug = $base;
            for ($i = 2; in_array($slug, $used, true); $i++) {
                $slug = "{$base}-{$i}";
            }
            $used[] = $slug;
            DB::table('image_categories')->where('id', $row->id)->update(['slug' => $slug]);
        }

        Schema::table('image_categories', function (Blueprint $table) {
            $table->unique('slug');
        });
    }

    public function down(): void
    {
        Schema::table('image_categories', function (Blueprint $table) {
            $table->dropUnique(['slug']);
            $table->dropColumn(['slug', 'event_date', 'description']);
        });
    }
};
