<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * First-party, cookieless website analytics. visitor_id is a daily-rotating hash of
     * (date, app key, IP, user agent), so a visitor can't be identified or followed across days.
     */
    public function up(): void
    {
        Schema::create('page_views', function (Blueprint $table) {
            $table->id();
            $table->char('visitor_id', 16);
            $table->string('path', 255);
            $table->string('referrer_host', 255)->nullable();
            $table->string('utm_source', 100)->nullable();
            $table->string('utm_medium', 100)->nullable();
            $table->string('utm_campaign', 100)->nullable();
            $table->string('device', 10);
            $table->string('browser', 30)->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->index(['created_at', 'visitor_id']);
            $table->index(['path', 'created_at']);
        });

        Schema::create('site_events', function (Blueprint $table) {
            $table->id();
            $table->char('visitor_id', 16);
            $table->string('name', 50);
            $table->string('label', 255)->nullable();
            $table->string('path', 255)->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->index(['created_at', 'name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('site_events');
        Schema::dropIfExists('page_views');
    }
};
