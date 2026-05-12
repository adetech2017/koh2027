<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class NewsArticleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $articles = [
            [
                'title' => 'Kadri Hamzat Launches Economic Masterplan for Lagos',
                'slug' => 'kadri-launches-economic-masterplan',
                'excerpt' => 'The gubernatorial candidate unveiled a comprehensive economic development plan targeting job creation and foreign investment.',
                'body' => '<p>In a landmark speech at the Victoria Island Conference Center, Kadri Obafemi Hamzat unveiled his administration\'s comprehensive economic masterplan for Lagos State. The plan focuses on three key pillars: attracting global investment, supporting local entrepreneurs, and creating sustainable jobs across all sectors.</p><p>"We will transform Lagos into Africa\'s most competitive economy," Hamzat stated, outlining initiatives to establish technology parks, streamline business registration, and provide subsidized loans to SMEs.</p>',
                'category' => 'announcement',
                'image_path' => 'news/economic-plan.jpg',
                'image_alt' => 'Economic masterplan launch',
                'is_featured' => true,
                'is_published' => true,
                'published_at' => now()->subDays(3),
            ],
            [
                'title' => '15,000 Volunteers Join Campaign Movement',
                'slug' => 'volunteers-campaign',
                'excerpt' => 'Grassroots support surges as thousands of Lagosians volunteer for the campaign.',
                'body' => '<p>The campaign has welcomed its 15,000th volunteer, demonstrating widespread grassroots support across Lagos State. Volunteers span all demographics and are actively engaged in community mobilization, voter registration drives, and campaign events.</p><p>"This shows the hunger for change among Lagosians," said Campaign Director Michael Adeniyi. "Our volunteers are the backbone of this movement."</p>',
                'category' => 'update',
                'image_path' => 'news/volunteers.jpg',
                'image_alt' => 'Campaign volunteers',
                'is_featured' => true,
                'is_published' => true,
                'published_at' => now()->subDays(5),
            ],
            [
                'title' => 'Healthcare Initiative Benefits 250+ Communities',
                'slug' => 'healthcare-communities',
                'excerpt' => 'Our comprehensive healthcare plan promises to reach every corner of Lagos State.',
                'body' => '<p>The campaign\'s healthcare initiative aims to establish primary health centers in 100 additional locations across Lagos State. The plan includes free maternal and child healthcare, vaccination programs, and subsidized treatments for chronic diseases.</p><p>"Healthcare is a fundamental right," Hamzat emphasized. "Every Lagosian deserves access to quality medical services regardless of their economic status."</p>',
                'category' => 'press_release',
                'image_path' => 'news/healthcare.jpg',
                'image_alt' => 'Healthcare initiative',
                'is_featured' => false,
                'is_published' => true,
                'published_at' => now()->subDays(7),
            ],
            [
                'title' => 'Education Sector Primed for Transformation',
                'slug' => 'education-transformation',
                'excerpt' => 'New education policy promises improved infrastructure and better-quality teaching.',
                'body' => '<p>The education pillar of the campaign includes infrastructure upgrades to 500 schools, teacher training programs, and the establishment of 50 vocational training centers. The plan targets a 95% literacy rate within the first term.</p><p>"Education is the key to unlocking Nigeria\'s potential," said Education Spokesperson Dr. Folake Adeyemi.</p>',
                'category' => 'opinion',
                'image_path' => 'news/education.jpg',
                'image_alt' => 'Education initiative',
                'is_featured' => false,
                'is_published' => true,
                'published_at' => now()->subDays(10),
            ],
        ];

        foreach ($articles as $article) {
            \App\Models\NewsArticle::create($article);
        }
    }
}
