<?php

namespace Database\Seeders;

use App\Models\Fleet\Beneficiary;
use App\Models\Fleet\FleetAdmin;
use Illuminate\Database\Seeder;

/**
 * Local sign-in accounts for the fleet app. Not part of DatabaseSeeder; run with
 * `php artisan db:seed --class=FleetDemoSeeder`. Refuses to run in production.
 */
class FleetDemoSeeder extends Seeder
{
    public function run(): void
    {
        if (app()->isProduction()) {
            $this->command->error('FleetDemoSeeder only runs outside production. Use `php artisan fleet:create-admin` there.');

            return;
        }

        FleetAdmin::updateOrCreate(
            ['email' => 'fleet-admin@example.com'],
            ['name' => 'Fleet Admin', 'password' => 'password', 'role' => 'super_admin'],
        );

        Beneficiary::updateOrCreate(
            ['phone' => '+2348030000001'],
            ['full_name' => 'Demo Beneficiary', 'lga' => 'Ikeja'],
        );

        $this->command->info('Admin: fleet-admin@example.com / password · Beneficiary: 0803 000 0001 (code appears in storage/logs)');
    }
}
