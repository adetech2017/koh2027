<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class EventSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $events = [
            [
                'title' => 'Grand Campaign Launch',
                'slug' => 'grand-campaign-launch',
                'description' => 'Join us for the official launch of our 2027 gubernatorial campaign at the Tafawa Balewa Square in Lagos Island.',
                'starts_at' => now()->addDays(5)->setHour(10)->setMinute(0),
                'ends_at' => now()->addDays(5)->setHour(14)->setMinute(0),
                'venue_name' => 'Tafawa Balewa Square',
                'address' => 'Lagos Island, Lagos',
                'lga' => 'Lagos Island',
                'event_type' => 'rally',
                'capacity' => 5000,
                'image_path' => 'events/campaign-launch.jpg',
                'image_alt' => 'Campaign launch event',
                'is_active' => true,
                'rsvp_enabled' => true,
            ],
            [
                'title' => 'Healthcare Town Hall',
                'slug' => 'healthcare-town-hall',
                'description' => 'Community dialogue on healthcare access and our vision for a healthier Lagos.',
                'starts_at' => now()->addDays(10)->setHour(14)->setMinute(0),
                'ends_at' => now()->addDays(10)->setHour(17)->setMinute(0),
                'venue_name' => 'Ikeja Local Government Secretariat',
                'address' => 'Ikeja, Lagos',
                'lga' => 'Ikeja',
                'event_type' => 'townhall',
                'capacity' => 1000,
                'image_path' => 'events/healthcare-townhall.jpg',
                'image_alt' => 'Healthcare town hall',
                'is_active' => true,
                'rsvp_enabled' => true,
            ],
            [
                'title' => 'Youth Employment Fair',
                'slug' => 'youth-employment-fair',
                'description' => 'Career opportunities and job matching event for young Lagosians.',
                'starts_at' => now()->addDays(15)->setHour(9)->setMinute(0),
                'ends_at' => now()->addDays(15)->setHour(16)->setMinute(0),
                'venue_name' => 'Lekki Expo Center',
                'address' => 'Lekki, Lagos',
                'lga' => 'Lekki',
                'event_type' => 'fair',
                'capacity' => 2000,
                'image_path' => 'events/employment-fair.jpg',
                'image_alt' => 'Youth employment fair',
                'is_active' => true,
                'rsvp_enabled' => true,
            ],
            [
                'title' => 'Education Stakeholders Meeting',
                'slug' => 'education-stakeholders-meeting',
                'description' => 'Engagement with teachers, administrators, and education stakeholders.',
                'starts_at' => now()->addDays(20)->setHour(10)->setMinute(0),
                'ends_at' => now()->addDays(20)->setHour(13)->setMinute(0),
                'venue_name' => 'SUBEB Headquarters',
                'address' => 'Alausa, Ikeja',
                'lga' => 'Ikeja',
                'event_type' => 'meeting',
                'capacity' => 500,
                'image_path' => 'events/education-meeting.jpg',
                'image_alt' => 'Education meeting',
                'is_active' => true,
                'rsvp_enabled' => true,
            ],
        ];

        foreach ($events as $event) {
            \App\Models\Event::create($event);
        }
    }
}
