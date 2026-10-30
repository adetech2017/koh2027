<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // WhatsApp message templates. Meta only delivers wording it has approved, so each row is
        // the approved wording (name/body) plus, while a change waits for Meta, the new draft.
        Schema::create('fleet_message_templates', function (Blueprint $table) {
            $table->id();
            // Automatic messages are sent by key (evaluation_due...); custom ones, sent by admins, have none
            $table->string('key', 40)->nullable()->unique();
            $table->string('title');
            $table->string('name'); // the template's name in WhatsApp Manager
            $table->string('language', 10);
            $table->string('category', 20)->default('UTILITY'); // UTILITY, MARKETING
            $table->text('body')->nullable(); // approved wording, with {{1}}, {{2}}... where values go
            // Custom templates: [{ label, example }] for {{2}} onwards ({{1}} is always the first name)
            $table->json('variables')->nullable();
            $table->json('settings')->nullable(); // e.g. { days_before: 3 } for the due reminder
            $table->boolean('enabled')->default(true);
            // Meta's status for `name`: not_checked, not_submitted, pending, approved, rejected, paused, disabled, mismatch
            $table->string('status', 20)->default('not_checked');
            $table->text('status_reason')->nullable();
            // New wording waiting for Meta; it replaces name/body once approved
            $table->string('draft_name')->nullable();
            $table->text('draft_body')->nullable();
            $table->json('draft_variables')->nullable();
            $table->string('draft_status', 20)->nullable(); // pending, rejected
            $table->text('draft_reason')->nullable();
            $table->dateTime('draft_submitted_at')->nullable();
            $table->dateTime('checked_at')->nullable();
            $table->foreignId('updated_by')->nullable()->constrained('fleet_admins')->nullOnDelete();
            $table->timestamps();
        });

        $now = now();
        $language = config('fleet.whatsapp.language', 'en');
        $row = fn (array $values) => $values + [
            'language' => $language, 'category' => 'UTILITY', 'enabled' => true, 'status' => 'not_checked',
            'variables' => null, 'settings' => null, 'created_at' => $now, 'updated_at' => $now,
        ];

        DB::table('fleet_message_templates')->insert([
            $row([
                'key' => 'evaluation_due',
                'title' => 'Evaluation due reminder',
                'name' => 'fleet_evaluation_due',
                'body' => 'Hello {{1}}, your two-weekly video check for vehicle {{2}} is due by {{3}}. Please open the KOH Fleet app at fleet.koh2027.ng to record it.',
                'settings' => json_encode(['days_before' => 3]),
            ]),
            $row([
                'key' => 'evaluation_missed',
                'title' => 'Missed evaluation',
                'name' => 'fleet_evaluation_missed',
                'body' => "Hello {{1}}, we did not receive the video check for vehicle {{2}} that was due on {{3}}. Please record this period's video in the KOH Fleet app as soon as possible.",
            ]),
            $row([
                'key' => 'evaluation_rejected',
                'title' => 'Evaluation rejected',
                'name' => 'fleet_evaluation_rejected',
                'body' => 'Hello {{1}}, your video check for vehicle {{2}} was not accepted. Reason: {{3}}. Please open the KOH Fleet app for next steps.',
            ]),
            $row([
                'key' => 'vehicle_recalled',
                'title' => 'Vehicle recalled',
                'name' => 'fleet_vehicle_recalled',
                'body' => 'Hello {{1}}, the campaign committee has recalled vehicle {{2}}. Please return it by {{3}}. Your fleet coordinator will contact you to arrange the return.',
            ]),
            $row([
                'key' => null,
                'title' => 'General message',
                'name' => 'fleet_general_message',
                'body' => 'Hello {{1}}, here is a message from the KOH Fleet team: {{2}}. Reply to this number if you have any questions.',
                'variables' => json_encode([['label' => 'Message', 'example' => 'The beneficiaries meeting holds on Saturday at 10am at the campaign office']]),
            ]),
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('fleet_message_templates');
    }
};
