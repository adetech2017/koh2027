<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class CampaignStatSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $stats = [
            ['label' => 'Volunteers', 'value' => '15,000', 'icon' => 'users', 'sort_order' => 1],
            ['label' => 'Communities Reached', 'value' => '250+', 'icon' => 'map-pin', 'sort_order' => 2],
            ['label' => 'Campaign Events', 'value' => '89', 'icon' => 'calendar', 'sort_order' => 3],
            ['label' => 'Supporters', 'value' => '500K+', 'icon' => 'heart', 'sort_order' => 4],
        ];

        foreach ($stats as $stat) {
            \App\Models\CampaignStat::create(array_merge($stat, ['is_active' => true]));
        }
    }
}
