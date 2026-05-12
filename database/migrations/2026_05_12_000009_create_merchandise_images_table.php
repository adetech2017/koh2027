<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('merchandise_images', function (Blueprint $table) {
            $table->id();
            $table->foreignId('merchandise_id')->constrained('merchandises')->cascadeOnDelete();
            $table->string('image_path');
            $table->string('image_alt');
            $table->boolean('is_primary')->default(false);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('merchandise_images');
    }
};
