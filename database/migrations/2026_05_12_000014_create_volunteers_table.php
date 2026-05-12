<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('volunteers', function (Blueprint $table) {
            $table->id();
            $table->string('first_name');
            $table->string('last_name');
            $table->string('email')->unique();
            $table->string('phone');
            $table->string('lga');
            $table->string('ward')->nullable();
            $table->string('occupation')->nullable();
            $table->text('motivation')->nullable();
            $table->json('skills')->nullable();
            $table->boolean('has_vehicle')->default(false);
            $table->enum('status', ['pending', 'approved', 'active', 'inactive'])->default('pending');
            $table->timestamp('approved_at')->nullable();
            $table->ipAddress()->nullable();
            $table->timestamps();
            $table->index(['status', 'lga']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('volunteers');
    }
};
