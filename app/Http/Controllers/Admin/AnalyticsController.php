<?php

namespace App\Http\Controllers\Admin;

use App\Services\CrmAnalyticsService;
use App\Services\TrafficAnalyticsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class AnalyticsController
{
    private const RANGES = [7, 30, 90];

    public function __construct(
        private CrmAnalyticsService $crm,
        private TrafficAnalyticsService $traffic,
    ) {}

    public function index(Request $request): Response
    {
        $days = in_array((int) $request->query('days'), self::RANGES) ? (int) $request->query('days') : 30;

        $lga = $this->crm->lgaBreakdown();
        $label = fn (array $rows) => collect($rows)
            ->map(fn ($row) => ['lga' => filled($row['lga']) ? $row['lga'] : 'Not given', 'count' => (int) $row['count']])
            ->values();

        return Inertia::render('Admin/Analytics', [
            'days' => $days,
            'ranges' => self::RANGES,
            // Website traffic
            'trafficSummary' => $this->traffic->summary($days),
            'trafficDaily' => $this->traffic->daily($days),
            'topPages' => $this->traffic->topPages($days),
            'sources' => $this->traffic->sources($days),
            'campaigns' => $this->traffic->campaigns($days),
            'devices' => $this->traffic->devices($days),
            'clicks' => $this->traffic->clicks($days),
            'trackingSince' => DB::table('page_views')->min('created_at'),
            // Campaign outcomes
            'periodTotals' => $this->crm->periodTotals($days),
            'overviewStats' => $this->crm->overviewStats(),
            'lgaBreakdown' => ['volunteers' => $label($lga['volunteers']), 'rsvps' => $label($lga['rsvps'])],
            'skillsInventory' => $this->crm->volunteerSkillsInventory(),
            'newsletterFunnel' => $this->crm->newsletterFunnel(),
            'eventAttendance' => $this->crm->eventAttendanceRates(),
        ]);
    }
}
