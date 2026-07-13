<?php

namespace Database\Seeders;

use App\Models\Material;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class MaterialSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * Resets the materials table before reseeding, so this is safe to
     * re-run on an environment that already has old/duplicate rows.
     *
     * Note: this only creates the database rows. The actual PDF files must
     * exist under storage/app/private/materials/ (upload them via
     * Admin > Materials on this environment) — without them, downloads
     * will 404 until the matching files are placed there.
     */
    public function run(): void
    {
        Material::truncate();

        $materials = [
            [
                'title' => 'Full Campaign Manifesto 2027',
                'description' => 'Complete manifesto outlining all campaign policies',
                'category' => 'manifesto',
                'file_path' => 'materials/The-Lagos-Promise-Full.pdf',
                'file_name' => 'The-Lagos-Promise-Full.pdf',
                'file_type' => 'pdf',
                'file_size' => 137115288,
                'is_active' => true,
            ],
            [
                'title' => 'The Lagos Promise — Pillar 1: People First',
                'description' => 'Health, education, safety and service built around the dignity of every Lagosian',
                'category' => 'manifesto',
                'file_path' => 'materials/01-people-first.pdf',
                'file_name' => '01-people-first.pdf',
                'file_type' => 'pdf',
                'file_size' => 20503214,
                'is_active' => true,
            ],
            [
                'title' => 'The Lagos Promise — Pillar 2: Renewal',
                'description' => 'Building the roads, power, water and drainage that let Lagos work',
                'category' => 'manifesto',
                'file_path' => 'materials/02-renewal.pdf',
                'file_name' => '02-renewal.pdf',
                'file_type' => 'pdf',
                'file_size' => 20631732,
                'is_active' => true,
            ],
            [
                'title' => 'The Lagos Promise — Pillar 3: Opportunity',
                'description' => 'Widening enterprise, industry, trade and the Lagos digital economy',
                'category' => 'manifesto',
                'file_path' => 'materials/03-opportunity.pdf',
                'file_name' => '03-opportunity.pdf',
                'file_type' => 'pdf',
                'file_size' => 25604154,
                'is_active' => true,
            ],
            [
                'title' => 'The Lagos Promise — Pillar 4: Machinery of Government',
                'description' => 'A government that works through the council, the courts and the citizen interface',
                'category' => 'manifesto',
                'file_path' => 'materials/04-machinery-of-government.pdf',
                'file_name' => '04-machinery-of-government.pdf',
                'file_type' => 'pdf',
                'file_size' => 16636518,
                'is_active' => true,
            ],
            [
                'title' => 'The Lagos Promise — Pillar 5: Inclusion',
                'description' => 'Equity for every Lagos woman, young person, and every division of the state',
                'category' => 'manifesto',
                'file_path' => 'materials/05-inclusion.pdf',
                'file_name' => '05-inclusion.pdf',
                'file_type' => 'pdf',
                'file_size' => 19688514,
                'is_active' => true,
            ],
            [
                'title' => 'The Lagos Promise — Pillar 6: Security and Resilience',
                'description' => 'A safe, climate-resilient and intelligently governed megacity',
                'category' => 'manifesto',
                'file_path' => 'materials/06-security-and-resilience.pdf',
                'file_name' => '06-security-and-resilience.pdf',
                'file_type' => 'pdf',
                'file_size' => 17829737,
                'is_active' => true,
            ],
            [
                'title' => 'The Lagos Promise — Pillar 7: Economic Dignity',
                'description' => 'Affordable housing, mobility and a lower cost of living for every household',
                'category' => 'manifesto',
                'file_path' => 'materials/07-economic-dignity.pdf',
                'file_name' => '07-economic-dignity.pdf',
                'file_type' => 'pdf',
                'file_size' => 11713347,
                'is_active' => true,
            ],
        ];

        foreach ($materials as $material) {
            \App\Models\Material::create($material);
        }
    }
}
