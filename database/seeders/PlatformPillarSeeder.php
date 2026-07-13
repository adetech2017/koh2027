<?php

namespace Database\Seeders;

use App\Models\PlatformPillar;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class PlatformPillarSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * Resets the platform_pillars table before reseeding, so this is safe
     * to re-run on an environment that already has old placeholder rows.
     */
    public function run(): void
    {
        PlatformPillar::truncate();

        $pillars = [
            [
                'title' => 'People First',
                'slug' => 'people-first',
                'summary' => 'Health, education, safety and service built around the dignity of every Lagosian',
                'body' => 'Aa o ni fi enikeni sile — we will not leave anyone behind. From the Ilera Eko health card and a maternal mortality target of 150 per 100,000 by 2031, to the Lagos Mind education architecture, safer communities policed through the Lagos State Neighbourhood Safety Agency, and a citizen service built around the resident\'s daily life, People First puts the household, the clinic, the classroom and the citizen at the centre of government.',
                'icon' => 'heart',
                'color' => '#27AE60',
                'sort_order' => 1,
            ],
            [
                'title' => 'Renewal',
                'slug' => 'renewal',
                'summary' => 'Building the roads, power, water and drainage that let Lagos work',
                'body' => 'What we build with discipline lasts a generation. Renewal carries the megacity\'s hard infrastructure: expanding the rail, road and bridge network of Building Greater Lagos, closing the power gap through IMOLE, the Lagos Power Promise, securing clean water through the state\'s treatment and distribution architecture, and strengthening waste, drainage and flood resilience ahead of the next flood season.',
                'icon' => 'bolt',
                'color' => '#FFB81C',
                'sort_order' => 2,
            ],
            [
                'title' => 'Opportunity',
                'slug' => 'opportunity',
                'summary' => 'Widening enterprise, industry, trade and the Lagos digital economy',
                'body' => 'Lagos produces roughly a quarter of Nigeria\'s GDP, and Opportunity is the commitment to widen that lead: deepening enterprise, industry and trade, strengthening food security, growing the Blue Economy at the West African gate, backing the Lagos Stage and the Digital Lagos tech ecosystem, and running it all through a transparent, consolidated Lagos Treasury.',
                'icon' => 'briefcase',
                'color' => '#003D82',
                'sort_order' => 3,
            ],
            [
                'title' => 'Machinery of Government',
                'slug' => 'machinery-of-government',
                'summary' => 'A government that works through the council, the courts and the citizen interface',
                'body' => 'Government must be felt at the gate of the citizen, in the local council and the traditional institution as much as in Alausa. Machinery of Government rebuilds the Lagos Compact between the state and its 20 local governments and 37 council development areas, strengthens Lagos Justice, digitises the citizen\'s front door, and holds every commitment in this Promise to account through the Hamzat Compact.',
                'icon' => 'building-library',
                'color' => '#6B21A8',
                'sort_order' => 4,
            ],
            [
                'title' => 'Inclusion',
                'slug' => 'inclusion',
                'summary' => 'Equity for every Lagos woman, young person, and every division of the state',
                'body' => 'The mother of Lagos is the maker of Lagos. Inclusion protects the dignity, safety and economic agency of the Lagos woman through Obirin Eko, opens opportunity for young people through Omo Eko, secures universal access and dignity for persons with disabilities through Iyi Eko, and closes the gap between Lagos\'s core and its historically underdeveloped divisions through One Lagos.',
                'icon' => 'user-group',
                'color' => '#DB2777',
                'sort_order' => 5,
            ],
            [
                'title' => 'Security and Resilience',
                'slug' => 'security-and-resilience',
                'summary' => 'A safe, climate-resilient and intelligently governed megacity',
                'body' => 'The first promise of a city is that her citizens go home safely. Security and Resilience builds a Police-led, intelligence-driven, community-anchored safety architecture, prepares Lagos for climate shocks and disasters, and deploys Smart Lagos technology to keep the megacity safe, resilient and ready.',
                'icon' => 'shield-check',
                'color' => '#B91C1C',
                'sort_order' => 6,
            ],
            [
                'title' => 'Economic Dignity',
                'slug' => 'economic-dignity',
                'summary' => 'Affordable housing, mobility and a lower cost of living for every household',
                'body' => 'The dignity of a household begins with the dignity of a roof. Economic Dignity delivers affordable shelter through Home for Lagos, with a mortgage benchmark of 10 per cent down and single-digit interest, integrates the megacity\'s transport network through Lagos in Motion, and tackles the day-to-day cost of living for Lagos families.',
                'icon' => 'home',
                'color' => '#0D9488',
                'sort_order' => 7,
            ],
        ];

        foreach ($pillars as $pillar) {
            \App\Models\PlatformPillar::create(array_merge($pillar, ['is_active' => true]));
        }
    }
}
