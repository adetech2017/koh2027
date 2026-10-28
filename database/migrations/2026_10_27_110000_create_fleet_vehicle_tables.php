<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fleet_vehicles', function (Blueprint $table) {
            $table->id();
            $table->string('plate_number', 20)->unique(); // stored upper-case, no spaces
            $table->string('vehicle_type', 30); // car, suv, bus, pickup, motorcycle, tricycle
            $table->string('make', 60);
            $table->string('model', 60);
            $table->unsignedSmallInteger('year')->nullable();
            $table->string('colour', 40)->nullable();
            $table->string('chassis_number', 40)->nullable()->unique();
            $table->string('engine_number', 40)->nullable();
            $table->string('branding')->nullable(); // what's on the vehicle, e.g. "Full wrap, KOH 2027"
            $table->string('status', 20)->default('available'); // available, assigned, maintenance, retired
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('fleet_admins')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index('status');
        });

        // The campaign committee calls vehicles back in batches
        Schema::create('fleet_recalls', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('reason')->nullable();
            $table->date('return_by');
            $table->foreignId('created_by')->nullable()->constrained('fleet_admins')->nullOnDelete();
            $table->timestamps();
        });

        // A vehicle in a beneficiary's hands, from handover to return
        Schema::create('fleet_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vehicle_id')->constrained('fleet_vehicles');
            $table->foreignId('beneficiary_id')->constrained('fleet_beneficiaries');
            $table->string('status', 20)->default('active'); // active, returned
            // datetime, not timestamp: on MySQL/MariaDB with explicit_defaults_for_timestamp off, a
            // NOT NULL timestamp silently gets ON UPDATE CURRENT_TIMESTAMP and would reset on every update
            $table->dateTime('handed_over_at');
            $table->timestamp('returned_at')->nullable();
            $table->foreignId('recall_id')->nullable()->constrained('fleet_recalls')->nullOnDelete();
            $table->timestamp('recalled_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('fleet_admins')->nullOnDelete();
            $table->timestamps();

            $table->index(['vehicle_id', 'status']);
            $table->index(['beneficiary_id', 'status']);
        });

        // The signed form for a handover (vehicle goes out) or a return (vehicle comes back)
        Schema::create('fleet_handovers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('assignment_id')->constrained('fleet_assignments');
            $table->string('type', 20); // handover, return
            $table->unsignedInteger('odometer_km');
            $table->string('fuel_level', 20); // empty, quarter, half, three_quarters, full
            $table->json('checklist'); // { item: { status: ok|issue|missing, note } }
            $table->text('notes')->nullable();
            $table->foreignId('officer_id')->nullable()->constrained('fleet_admins')->nullOnDelete();
            $table->string('officer_signature_path');
            // Usually the beneficiary; can be a representative signing for them
            $table->string('signed_by_name');
            $table->string('signer_signature_path');
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->unsignedInteger('location_accuracy_m')->nullable();
            $table->dateTime('occurred_at');
            $table->timestamps();

            $table->unique(['assignment_id', 'type']);
        });

        Schema::create('fleet_handover_photos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('handover_id')->constrained('fleet_handovers')->cascadeOnDelete();
            $table->string('path');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fleet_handover_photos');
        Schema::dropIfExists('fleet_handovers');
        Schema::dropIfExists('fleet_assignments');
        Schema::dropIfExists('fleet_recalls');
        Schema::dropIfExists('fleet_vehicles');
    }
};
