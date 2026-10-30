<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Names from config('fleet.directorates'); stored as text like lga, so the list can change without a migration
        Schema::table('fleet_beneficiaries', function (Blueprint $table) {
            $table->string('directorate', 150)->nullable()->after('ward');
            $table->string('sub_directorate', 150)->nullable()->after('directorate');

            $table->index('directorate');
        });
    }

    public function down(): void
    {
        Schema::table('fleet_beneficiaries', function (Blueprint $table) {
            $table->dropIndex(['directorate']);
            $table->dropColumn(['directorate', 'sub_directorate']);
        });
    }
};
