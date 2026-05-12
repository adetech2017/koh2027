<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class GalleryImageSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $images = [
            [
                'image_path' => 'gallery/rally-1.jpg',
                'alt_text' => 'Campaign rally with thousands of supporters',
                'category' => 'rallies',
                'is_featured' => true,
                'sort_order' => 1,
            ],
            [
                'image_path' => 'gallery/community-1.jpg',
                'alt_text' => 'Community engagement in Surulere',
                'category' => 'community',
                'is_featured' => true,
                'sort_order' => 2,
            ],
            [
                'image_path' => 'gallery/health-1.jpg',
                'alt_text' => 'Healthcare initiative in Badagry',
                'category' => 'initiatives',
                'is_featured' => true,
                'sort_order' => 3,
            ],
            [
                'image_path' => 'gallery/education-1.jpg',
                'alt_text' => 'School visit and education engagement',
                'category' => 'education',
                'is_featured' => true,
                'sort_order' => 4,
            ],
            [
                'image_path' => 'gallery/rally-2.jpg',
                'alt_text' => 'Candidate addressing supporters',
                'category' => 'rallies',
                'is_featured' => false,
                'sort_order' => 5,
            ],
            [
                'image_path' => 'gallery/volunteer-1.jpg',
                'alt_text' => 'Volunteers mobilizing in local communities',
                'category' => 'volunteers',
                'is_featured' => false,
                'sort_order' => 6,
            ],
            [
                'image_path' => 'gallery/business-1.jpg',
                'alt_text' => 'Business forum with entrepreneurs',
                'category' => 'business',
                'is_featured' => false,
                'sort_order' => 7,
            ],
            [
                'image_path' => 'gallery/youth-1.jpg',
                'alt_text' => 'Youth engagement program',
                'category' => 'youth',
                'is_featured' => false,
                'sort_order' => 8,
            ],
            [
                'image_path' => 'gallery/town-1.jpg',
                'alt_text' => 'Town hall meeting with residents',
                'category' => 'community',
                'is_featured' => false,
                'sort_order' => 9,
            ],
            [
                'image_path' => 'gallery/event-1.jpg',
                'alt_text' => 'Campaign event in Ikeja',
                'category' => 'events',
                'is_featured' => false,
                'sort_order' => 10,
            ],
        ];

        foreach ($images as $image) {
            \App\Models\GalleryImage::create(array_merge($image, ['is_active' => true]));
        }
    }
}
