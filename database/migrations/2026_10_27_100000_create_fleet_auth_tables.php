<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Fleet admins are separate from the campaign site's staff accounts
        Schema::create('fleet_admins', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('password');
            $table->string('role', 30)->default('reviewer'); // super_admin, fleet_officer, reviewer
            $table->timestamp('last_login_at')->nullable();
            // Suspended admins can't sign in; their reviews and history are kept
            $table->timestamp('deactivated_at')->nullable();
            $table->rememberToken();
            $table->timestamps();
        });

        Schema::create('fleet_beneficiaries', function (Blueprint $table) {
            $table->id();
            $table->string('full_name');
            // E.164 (+234...), the WhatsApp number the login code is sent to
            $table->string('phone', 20)->unique();
            $table->string('alt_phone', 20)->nullable();
            $table->string('email')->nullable();
            $table->string('address')->nullable();
            $table->string('lga', 100)->nullable();
            $table->string('ward', 100)->nullable();
            $table->string('id_type', 30)->nullable(); // nin, voters_card, drivers_licence, passport
            $table->string('id_number', 50)->nullable();
            $table->string('photo_path')->nullable();
            $table->text('notes')->nullable();
            $table->timestamp('last_login_at')->nullable();
            $table->timestamp('deactivated_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('fleet_admins')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index('lga');
        });

        // One-time WhatsApp login codes; only a hash of the code is stored
        Schema::create('fleet_otps', function (Blueprint $table) {
            $table->id();
            $table->string('phone', 20)->index();
            $table->string('code_hash');
            $table->unsignedTinyInteger('attempts')->default(0);
            $table->timestamp('expires_at');
            $table->timestamp('consumed_at')->nullable();
            $table->string('ip', 45)->nullable();
            $table->timestamps();
        });

        Schema::create('fleet_audit_logs', function (Blueprint $table) {
            $table->id();
            // Who did it: a fleet admin or a beneficiary (null for system jobs)
            $table->nullableMorphs('actor');
            $table->string('action', 100);
            $table->nullableMorphs('subject');
            $table->json('properties')->nullable();
            $table->string('ip', 45)->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index('action');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fleet_audit_logs');
        Schema::dropIfExists('fleet_otps');
        Schema::dropIfExists('fleet_beneficiaries');
        Schema::dropIfExists('fleet_admins');
    }
};
