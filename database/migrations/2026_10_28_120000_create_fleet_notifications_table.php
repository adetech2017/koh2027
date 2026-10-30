<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // WhatsApp messages to beneficiaries: reminders, missed evaluations, rejections, recalls,
        // and messages admins send themselves
        Schema::create('fleet_notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('beneficiary_id')->constrained('fleet_beneficiaries')->cascadeOnDelete();
            $table->string('type', 40); // evaluation_due, evaluation_missed, evaluation_rejected, vehicle_recalled, custom
            // e.g. "evaluation_due:period:12"; unique so the same message is never sent twice
            $table->string('reference')->unique();
            $table->foreignId('message_template_id')->nullable()->constrained('fleet_message_templates')->nullOnDelete();
            // The template name and language as sent, in case the template changes later
            $table->string('template');
            $table->string('language', 10);
            $table->json('parameters');
            $table->text('body')->nullable(); // the wording with the values filled in, as the beneficiary reads it
            $table->foreignId('sent_by')->nullable()->constrained('fleet_admins')->nullOnDelete();
            $table->uuid('batch')->nullable()->index(); // one admin send to several beneficiaries
            $table->string('status', 20)->default('pending'); // pending, sent, failed, skipped
            $table->string('whatsapp_message_id')->nullable();
            $table->text('error')->nullable();
            $table->dateTime('sent_at')->nullable();
            $table->timestamps();

            $table->index(['status', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fleet_notifications');
    }
};
