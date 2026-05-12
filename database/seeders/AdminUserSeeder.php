<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        User::create([
            'name' => 'Admin',
            'email' => 'admin@koh2027.com',
            'password' => bcrypt('Admin@2027!'),
            'role' => 'admin',
        ]);

        User::create([
            'name' => 'Editor',
            'email' => 'editor@koh2027.com',
            'password' => bcrypt('Editor@2027!'),
            'role' => 'editor',
        ]);

        User::create([
            'name' => 'Moderator',
            'email' => 'moderator@koh2027.com',
            'password' => bcrypt('Mod@2027!'),
            'role' => 'moderator',
        ]);
    }
}
