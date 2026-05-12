<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class TestimonialSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $testimonials = [
            [
                'quote' => 'Kadri\'s vision for Lagos is clear and actionable. His economic policies will create the jobs our youth desperately need.',
                'author_name' => 'Chioma Okonkwo',
                'author_title' => 'Business Owner & Entrepreneur',
                'author_lga' => 'Ikeja',
                'avatar_path' => 'testimonials/avatar-1.jpg',
                'is_active' => true,
                'sort_order' => 1,
            ],
            [
                'quote' => 'As a teacher, I\'m impressed by the education policies. The investment in teacher training and school infrastructure is exactly what we need.',
                'author_name' => 'Mr. Adebayo Okafor',
                'author_title' => 'Senior Secondary School Teacher',
                'author_lga' => 'Lagos Island',
                'avatar_path' => 'testimonials/avatar-2.jpg',
                'is_active' => true,
                'sort_order' => 2,
            ],
            [
                'quote' => 'Healthcare should be accessible to everyone. Hamzat\'s plan to establish health centers in every community resonates with me deeply.',
                'author_name' => 'Dr. Ngozi Eze',
                'author_title' => 'Physician & Public Health Advocate',
                'author_lga' => 'Lekki',
                'avatar_path' => 'testimonials/avatar-3.jpg',
                'is_active' => true,
                'sort_order' => 3,
            ],
            [
                'quote' => 'I\'ve seen Kadri\'s work firsthand. He delivers results. He\'s committed to making Lagos work for all of us.',
                'author_name' => 'Chief Ayo Oladele',
                'author_title' => 'Community Leader & Civil Society',
                'author_lga' => 'Surulere',
                'avatar_path' => 'testimonials/avatar-4.jpg',
                'is_active' => true,
                'sort_order' => 4,
            ],
            [
                'quote' => 'The youth employment initiatives give me hope. Finally, a leader who understands the challenges young people face.',
                'author_name' => 'Zainab Mohammed',
                'author_title' => 'Graduate & Job Seeker',
                'author_lga' => 'Epe',
                'avatar_path' => 'testimonials/avatar-5.jpg',
                'is_active' => true,
                'sort_order' => 5,
            ],
            [
                'quote' => 'Lagos needs a leader with experience and vision. Hamzat has both. He\'s the right person for the job.',
                'author_name' => 'Prof. Kunle Toyin',
                'author_title' => 'Academic & Development Expert',
                'author_lga' => 'Ikoyi',
                'avatar_path' => 'testimonials/avatar-6.jpg',
                'is_active' => true,
                'sort_order' => 6,
            ],
        ];

        foreach ($testimonials as $testimonial) {
            \App\Models\Testimonial::create($testimonial);
        }
    }
}
