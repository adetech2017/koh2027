<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            HeroSlideSeeder::class,
            CampaignStatSeeder::class,
            PlatformPillarSeeder::class,
            EventSeeder::class,
            NewsArticleSeeder::class,
            TestimonialSeeder::class,
            GalleryImageSeeder::class,
            MaterialSeeder::class,
            MerchandiseSeeder::class,
        ]);
    }
}
