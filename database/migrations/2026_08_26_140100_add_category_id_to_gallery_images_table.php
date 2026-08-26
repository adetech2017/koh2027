<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('gallery_images', function (Blueprint $table) {
            $table->foreignId('category_id')->nullable()->after('category')->constrained('image_categories')->nullOnDelete();
        });

        $names = DB::table('gallery_images')->whereNotNull('category')->distinct()->pluck('category');

        foreach ($names as $name) {
            $categoryId = DB::table('image_categories')->where('name', $name)->value('id');

            if (!$categoryId) {
                $categoryId = DB::table('image_categories')->insertGetId([
                    'name' => $name,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            DB::table('gallery_images')->where('category', $name)->update(['category_id' => $categoryId]);
        }

        Schema::table('gallery_images', function (Blueprint $table) {
            $table->dropColumn('category');
        });
    }

    public function down(): void
    {
        Schema::table('gallery_images', function (Blueprint $table) {
            $table->string('category')->nullable()->after('category_id');
        });

        DB::table('gallery_images')->whereNotNull('category_id')->get()->each(function ($row) {
            $name = DB::table('image_categories')->where('id', $row->category_id)->value('name');
            DB::table('gallery_images')->where('id', $row->id)->update(['category' => $name]);
        });

        Schema::table('gallery_images', function (Blueprint $table) {
            $table->dropConstrainedForeignId('category_id');
        });
    }
};
