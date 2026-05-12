<?php

namespace App\Http\Controllers\Admin;

use App\Services\CrmAnalyticsService;
use Inertia\Inertia;
use Inertia\Response;

class AnalyticsController
{
    public function __construct(private CrmAnalyticsService $analyticsService) {}

    public function index(): Response
    {
        return Inertia::render('Admin/Analytics', [
            'overviewStats' => $this->analyticsService->overviewStats(),
            'growthTrend' => $this->analyticsService->growthTrend(30),
            'lgaBreakdown' => $this->analyticsService->lgaBreakdown(),
            'skillsInventory' => $this->analyticsService->volunteerSkillsInventory(),
            'newsletterFunnel' => $this->analyticsService->newsletterFunnel(),
            'eventAttendance' => $this->analyticsService->eventAttendanceRates(),
        ]);
    }
}
