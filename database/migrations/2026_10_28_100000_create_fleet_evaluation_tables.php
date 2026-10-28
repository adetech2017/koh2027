<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Back-to-back two-week windows from the handover date; one video is due in each
        Schema::create('fleet_evaluation_periods', function (Blueprint $table) {
            $table->id();
            $table->foreignId('assignment_id')->constrained('fleet_assignments')->cascadeOnDelete();
            $table->unsignedSmallInteger('sequence');
            // datetime, not timestamp: on MySQL/MariaDB with explicit_defaults_for_timestamp off, a
            // NOT NULL timestamp silently gets ON UPDATE CURRENT_TIMESTAMP and would reset on every update
            $table->dateTime('starts_at');
            $table->dateTime('ends_at');
            // open, submitted, missed, closed (assignment ended first); review adds approved, rejected
            $table->string('status', 20)->default('open');
            $table->timestamps();

            $table->unique(['assignment_id', 'sequence']);
            $table->index(['status', 'ends_at']);
        });

        // The code shown on screen while recording live, so a reviewer can tell the video is new
        Schema::create('fleet_evaluation_challenges', function (Blueprint $table) {
            $table->id();
            $table->foreignId('beneficiary_id')->constrained('fleet_beneficiaries')->cascadeOnDelete();
            $table->foreignId('period_id')->constrained('fleet_evaluation_periods')->cascadeOnDelete();
            $table->string('code', 8);
            $table->dateTime('expires_at');
            $table->timestamp('used_at')->nullable();
            $table->timestamps();
        });

        // A video arriving in pieces; the pieces themselves are files under storage/app/private/fleet-uploads
        Schema::create('fleet_upload_sessions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignId('beneficiary_id')->constrained('fleet_beneficiaries')->cascadeOnDelete();
            $table->foreignId('period_id')->constrained('fleet_evaluation_periods')->cascadeOnDelete();
            $table->string('filename');
            $table->string('mime', 100);
            $table->unsignedBigInteger('size');
            $table->unsignedInteger('chunk_size');
            $table->unsignedInteger('total_chunks');
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
        });

        Schema::create('fleet_evaluations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('period_id')->constrained('fleet_evaluation_periods');
            $table->foreignId('assignment_id')->constrained('fleet_assignments');
            $table->foreignId('beneficiary_id')->constrained('fleet_beneficiaries');
            $table->foreignId('vehicle_id')->constrained('fleet_vehicles');

            // How it was captured and what proves it's new
            $table->string('source', 10); // live, gallery
            $table->foreignId('challenge_id')->nullable()->constrained('fleet_evaluation_challenges')->nullOnDelete();
            $table->string('challenge_code', 8)->nullable();
            $table->string('trust_level', 20); // verified_live, gallery_ok, unverified
            $table->json('trust_flags')->nullable();

            // The file
            $table->string('original_filename');
            $table->string('original_mime', 100);
            $table->unsignedBigInteger('original_size');
            $table->char('file_hash', 64)->unique(); // sha256; the same file can't be submitted twice
            $table->string('working_path')->nullable(); // local copy until processed
            $table->string('video_path')->nullable(); // processed MP4 on the fleet disk
            $table->string('thumbnail_path')->nullable();
            $table->string('processing_status', 20)->default('processing'); // processing, ready, failed
            $table->text('processing_error')->nullable();
            $table->decimal('duration_seconds', 7, 2)->nullable();
            $table->timestamp('recorded_at')->nullable(); // from the video's own metadata
            $table->json('metadata')->nullable();

            // Where
            $table->decimal('device_latitude', 10, 7)->nullable();
            $table->decimal('device_longitude', 10, 7)->nullable();
            $table->unsignedInteger('device_accuracy_m')->nullable();
            $table->decimal('metadata_latitude', 10, 7)->nullable();
            $table->decimal('metadata_longitude', 10, 7)->nullable();

            // The beneficiary's checklist
            $table->unsignedInteger('mileage_km');
            $table->string('fuel_level', 20);
            $table->boolean('has_damage');
            $table->text('damage_notes')->nullable();
            $table->boolean('branding_intact');
            $table->text('branding_notes')->nullable();
            $table->text('notes')->nullable();

            // Review (Phase 4)
            $table->string('review_status', 20)->default('pending'); // pending, approved, rejected
            $table->foreignId('reviewed_by')->nullable()->constrained('fleet_admins')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->text('review_notes')->nullable();

            $table->dateTime('submitted_at');
            $table->timestamps();

            $table->index(['review_status', 'submitted_at']);
            $table->index('trust_level');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fleet_evaluations');
        Schema::dropIfExists('fleet_upload_sessions');
        Schema::dropIfExists('fleet_evaluation_challenges');
        Schema::dropIfExists('fleet_evaluation_periods');
    }
};
