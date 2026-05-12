<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class PlatformPillarSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $pillars = [
            [
                'title' => 'Economic Development',
                'slug' => 'economic-development',
                'summary' => 'Creating sustainable jobs and fostering business growth',
                'body' => 'We are committed to transforming Lagos into Africa\'s leading economic hub. Our plan includes: attracting foreign investment, supporting SMEs, developing technology parks, and creating 50,000 new jobs annually. We will streamline business registration, reduce bureaucratic bottlenecks, and provide mentorship programs for entrepreneurs.',
                'icon' => 'briefcase',
                'color' => '#003D82',
                'sort_order' => 1,
            ],
            [
                'title' => 'Education & Skills',
                'slug' => 'education-skills',
                'summary' => 'Building world-class educational institutions and workforce',
                'body' => 'Education is the foundation of progress. We will invest in quality education from primary to tertiary levels, establish vocational training centers, upgrade school infrastructure, and provide scholarships for 100,000 students annually. Our focus includes STEM education, digital literacy, and skills matching market demands.',
                'icon' => 'book-open',
                'color' => '#FFB81C',
                'sort_order' => 2,
            ],
            [
                'title' => 'Healthcare Access',
                'slug' => 'healthcare-access',
                'summary' => 'Ensuring quality healthcare for all Lagosians',
                'body' => 'Everyone deserves access to quality healthcare. We will establish 100 primary health centers, upgrade existing hospitals, implement free maternal and child healthcare, provide universal health insurance, and train 5,000 additional healthcare workers. Prevention and wellness will be prioritized alongside treatment.',
                'icon' => 'heart',
                'color' => '#27AE60',
                'sort_order' => 3,
            ],
        ];

        foreach ($pillars as $pillar) {
            \App\Models\PlatformPillar::create(array_merge($pillar, ['is_active' => true]));
        }
    }
}
