<?php

namespace App\Console\Commands;

use App\Models\Fleet\FleetAdmin;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;

/**
 * Creates the first fleet admin (after that, super admins add others from the dashboard).
 */
class CreateFleetAdmin extends Command
{
    protected $signature = 'fleet:create-admin {--role=super_admin : super_admin, fleet_officer or reviewer}';

    protected $description = 'Create a fleet app admin account';

    public function handle(): int
    {
        $data = [
            'name' => $this->ask('Name'),
            'email' => $this->ask('Email'),
            'password' => $this->secret('Password (at least 10 characters)'),
            'role' => $this->option('role'),
        ];

        $validator = Validator::make($data, [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'unique:fleet_admins,email'],
            'password' => ['required', 'string', 'min:10'],
            'role' => ['required', 'in:'.implode(',', FleetAdmin::ROLES)],
        ]);

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $error) {
                $this->error($error);
            }

            return self::FAILURE;
        }

        $admin = FleetAdmin::create($data);
        $this->info("Created {$admin->role} {$admin->email}.");

        return self::SUCCESS;
    }
}
