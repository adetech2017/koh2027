<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->timestamp('last_login_at')->nullable()->after('role');
            // Suspended users can't sign in; their notes and history are kept
            $table->timestamp('deactivated_at')->nullable()->after('last_login_at');
        });

        // Deleting a staff account used to delete every note they wrote and their whole
        // activity history. Keep both and show them as written by a deleted user.
        Schema::table('constituent_notes', function (Blueprint $table) {
            $table->dropForeign(['author_id']);
        });
        Schema::table('constituent_notes', function (Blueprint $table) {
            $table->foreignId('author_id')->nullable()->change();
            $table->foreign('author_id')->references('id')->on('users')->nullOnDelete();
        });

        Schema::table('activity_logs', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->foreign('user_id')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('activity_logs', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
        });

        Schema::table('constituent_notes', function (Blueprint $table) {
            $table->dropForeign(['author_id']);
        });
        Schema::table('constituent_notes', function (Blueprint $table) {
            $table->foreignId('author_id')->nullable(false)->change();
            $table->foreign('author_id')->references('id')->on('users')->cascadeOnDelete();
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['last_login_at', 'deactivated_at']);
        });
    }
};
