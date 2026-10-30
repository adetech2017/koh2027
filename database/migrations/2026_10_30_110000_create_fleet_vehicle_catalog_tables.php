<?php

use App\Services\Fleet\VehicleCatalog;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fleet_vehicle_makes', function (Blueprint $table) {
            $table->id();
            $table->string('name', 60)->unique();
            $table->timestamps();
        });

        Schema::create('fleet_vehicle_models', function (Blueprint $table) {
            $table->id();
            $table->foreignId('make_id')->constrained('fleet_vehicle_makes')->cascadeOnDelete();
            $table->string('name', 60);
            $table->string('vehicle_type', 30); // a key of config('fleet.vehicle_types')
            $table->timestamps();

            $table->unique(['make_id', 'name']);
        });

        // The starting list, plus whatever existing vehicles already use so none of them fail validation
        VehicleCatalog::seedDefaults();
        VehicleCatalog::absorbExistingVehicles();
    }

    public function down(): void
    {
        Schema::dropIfExists('fleet_vehicle_models');
        Schema::dropIfExists('fleet_vehicle_makes');
    }
};
