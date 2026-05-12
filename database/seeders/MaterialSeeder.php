<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class MaterialSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $materials = [
            [
                'title' => 'Campaign Manifesto 2027',
                'description' => 'Complete manifesto outlining all campaign policies and vision for Lagos',
                'category' => 'manifesto',
                'file_path' => 'materials/manifesto-2027.pdf',
                'file_name' => 'manifesto-2027.pdf',
                'file_type' => 'pdf',
                'file_size' => 2500000,
                'is_active' => true,
            ],
            [
                'title' => 'Economic Policy Brief',
                'description' => 'Detailed economic policy framework for job creation and business growth',
                'category' => 'policy',
                'file_path' => 'materials/economic-policy.pdf',
                'file_name' => 'economic-policy.pdf',
                'file_type' => 'pdf',
                'file_size' => 1800000,
                'is_active' => true,
            ],
            [
                'title' => 'Healthcare Blueprint',
                'description' => 'Comprehensive healthcare plan for accessible quality medical services',
                'category' => 'policy',
                'file_path' => 'materials/healthcare-blueprint.pdf',
                'file_name' => 'healthcare-blueprint.pdf',
                'file_type' => 'pdf',
                'file_size' => 2100000,
                'is_active' => true,
            ],
            [
                'title' => 'Education Roadmap',
                'description' => 'Strategic education plan focusing on quality and accessibility',
                'category' => 'policy',
                'file_path' => 'materials/education-roadmap.pdf',
                'file_name' => 'education-roadmap.pdf',
                'file_type' => 'pdf',
                'file_size' => 1900000,
                'is_active' => true,
            ],
            [
                'title' => 'Campaign Brochure',
                'description' => 'Overview of campaign goals and key initiatives',
                'category' => 'brochure',
                'file_path' => 'materials/campaign-brochure.pdf',
                'file_name' => 'campaign-brochure.pdf',
                'file_type' => 'pdf',
                'file_size' => 3500000,
                'is_active' => true,
            ],
            [
                'title' => 'Frequently Asked Questions',
                'description' => 'Answers to common questions about the campaign and policies',
                'category' => 'faq',
                'file_path' => 'materials/faq.pdf',
                'file_name' => 'faq.pdf',
                'file_type' => 'pdf',
                'file_size' => 850000,
                'is_active' => true,
            ],
        ];

        foreach ($materials as $material) {
            \App\Models\Material::create($material);
        }
    }
}
