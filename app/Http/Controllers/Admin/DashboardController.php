<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Contact;
use App\Models\EventRsvp;
use App\Services\CrmAnalyticsService;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __construct(private CrmAnalyticsService $analyticsService) {}

    public function index(): Response
    {
        $recentContacts = Contact::latest()->take(5)->get(['id', 'name', 'email', 'subject', 'status', 'created_at']);
        $recentRsvps = EventRsvp::with('event:id,title')->latest()->take(5)->get(['id', 'event_id', 'name', 'status', 'created_at']);

        $growthTrend = $this->analyticsService->growthTrend(7);

        // lgaBreakdown() is already ordered by count desc
        $lgaTop5 = collect($this->analyticsService->lgaBreakdown()['volunteers'])
            ->filter(fn ($row) => filled($row['lga']))
            ->take(5)
            ->values()
            ->toArray();

        $recentActivity = ActivityLog::with('user:id,name')
            ->latest()
            ->take(10)
            ->get(['id', 'user_id', 'action', 'subject_type', 'subject_id', 'created_at'])
            ->map(fn ($log) => [
                'id' => $log->id,
                'action' => $log->action,
                'subject' => class_basename($log->subject_type),
                'user' => $log->user?->name,
                'created_at' => $log->created_at,
            ]);

        return Inertia::render('Admin/Dashboard', [
            'recentContacts' => $recentContacts,
            'recentRsvps' => $recentRsvps,
            'stats' => $this->analyticsService->overviewStats(),
            'growthTrend' => $growthTrend,
            'lgaTop5' => $lgaTop5,
            'recentActivity' => $recentActivity,
        ]);
    }
}
