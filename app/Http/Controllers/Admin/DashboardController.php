<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\CampaignStat;
use App\Models\Contact;
use App\Models\Event;
use App\Models\EventRsvp;
use App\Models\GalleryImage;
use App\Models\HeroSlide;
use App\Models\Material;
use App\Models\Merchandise;
use App\Models\MerchandiseImage;
use App\Models\NewsArticle;
use App\Models\NewsletterSubscriber;
use App\Models\PlatformPillar;
use App\Models\Testimonial;
use App\Models\Volunteer;
use App\Services\CrmAnalyticsService;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __construct(private CrmAnalyticsService $analyticsService) {}

    public function index(): Response
    {
        $counts = [
            'heroSlides' => HeroSlide::count(),
            'platformPillars' => PlatformPillar::count(),
            'campaignStats' => CampaignStat::count(),
            'events' => Event::count(),
            'eventRsvps' => EventRsvp::count(),
            'newsArticles' => NewsArticle::count(),
            'materials' => Material::count(),
            'merchandise' => Merchandise::count(),
            'merchandiseImages' => MerchandiseImage::count(),
            'testimonials' => Testimonial::count(),
            'galleryImages' => GalleryImage::count(),
            'contacts' => Contact::count(),
            'newsletterSubscribers' => NewsletterSubscriber::count(),
            'volunteers' => Volunteer::count(),
        ];

        $recentContacts = Contact::latest()->take(5)->get(['id', 'name', 'email', 'subject', 'status', 'created_at']);
        $recentRsvps = EventRsvp::with('event:id,title')->latest()->take(5)->get(['id', 'event_id', 'name', 'status', 'created_at']);

        $growthTrend = $this->analyticsService->growthTrend(7);
        $lgaTop5 = collect($this->analyticsService->lgaBreakdown()['volunteers'])
            ->sortDesc()
            ->take(5)
            ->toArray();

        $recentActivity = ActivityLog::with('user:id,name')
            ->latest()
            ->take(10)
            ->get(['id', 'user_id', 'action', 'subject_type', 'subject_id', 'created_at']);

        return Inertia::render('Admin/Dashboard', [
            'counts' => $counts,
            'recentContacts' => $recentContacts,
            'recentRsvps' => $recentRsvps,
            'stats' => $this->analyticsService->overviewStats(),
            'growthTrend' => $growthTrend,
            'lgaTop5' => $lgaTop5,
            'recentActivity' => $recentActivity,
        ]);
    }
}
