<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class HeroSlideSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $slides = [
            ['tagline' => 'A NEW DIRECTION FOR LAGOS', 'headline' => 'Visionary Leadership for Progressive Lagos', 'subtitle' => 'Building a better future for all Lagos residents', 'cta_text' => 'Learn Our Vision', 'cta_url' => '/platforms', 'cta_style' => 'primary', 'image_path' => 'hero/slide-1.jpg', 'image_alt' => 'Kadri Hamzat with Lagos skyline', 'sort_order' => 1],
            ['tagline' => 'PROVEN TRACK RECORD', 'headline' => 'Experience That Delivers Results', 'subtitle' => 'Years of dedicated service and tangible achievements for Lagos State', 'cta_text' => 'View Achievements', 'cta_url' => '/about', 'cta_style' => 'primary', 'image_path' => 'hero/slide-2.jpg', 'image_alt' => 'Kadri Hamzat with community members', 'sort_order' => 2],
            ['tagline' => 'OUR CORE VALUES', 'headline' => 'Integrity · Progress · Inclusivity', 'subtitle' => 'Committed to serving every Lagosian with equity and excellence', 'cta_text' => 'Our Values', 'cta_url' => '/about', 'cta_style' => 'primary', 'image_path' => 'hero/slide-3.jpg', 'image_alt' => 'Diverse Lagos community', 'sort_order' => 3],
            ['tagline' => 'BE PART OF THE MOVEMENT', 'headline' => 'Join the Movement for Better Lagos', 'subtitle' => 'Together, we build a stronger, more prosperous state', 'cta_text' => 'Get Involved', 'cta_url' => '/volunteer', 'cta_style' => 'primary', 'image_path' => 'hero/slide-4.jpg', 'image_alt' => 'Campaign rally and supporters', 'sort_order' => 4],
            ['tagline' => 'ELECTION DAY 2027', 'headline' => 'Vote Kadri Obafemi Hamzat for Governor', 'subtitle' => 'Saturday, March 11, 2027 – Vote for Progress and Prosperity', 'cta_text' => 'Register to Vote', 'cta_url' => 'https://inecnigeria.org', 'cta_style' => 'gold', 'image_path' => 'hero/slide-5.jpg', 'image_alt' => 'Lagos skyline forward momentum', 'sort_order' => 5],
        ];

        foreach ($slides as $slide) {
            \App\Models\HeroSlide::create(array_merge($slide, ['is_active' => true]));
        }
    }
}
