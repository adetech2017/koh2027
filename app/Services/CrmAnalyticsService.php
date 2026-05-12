<?php

namespace App\Services;

use App\Models\Contact;
use App\Models\Event;
use App\Models\EventRsvp;
use App\Models\NewsletterSubscriber;
use App\Models\Volunteer;
use Illuminate\Support\Facades\DB;

class CrmAnalyticsService
{
    public function overviewStats(): array
    {
        return [
            'volunteersTotal' => Volunteer::count(),
            'volunteersPending' => Volunteer::where('status', 'pending')->count(),
            'volunteersApproved' => Volunteer::where('status', 'approved')->count(),
            'volunteersActive' => Volunteer::where('status', 'active')->count(),
            'volunteersInactive' => Volunteer::where('status', 'inactive')->count(),
            'contactsTotal' => Contact::count(),
            'contactsNew' => Contact::where('status', 'new')->count(),
            'contactsRead' => Contact::where('status', 'read')->count(),
            'contactsReplied' => Contact::where('status', 'replied')->count(),
            'contactsArchived' => Contact::where('status', 'archived')->count(),
            'subscribersTotal' => NewsletterSubscriber::count(),
            'subscribersPending' => NewsletterSubscriber::where('status', 'pending')->count(),
            'subscribersConfirmed' => NewsletterSubscriber::where('status', 'confirmed')->count(),
            'subscribersUnsubscribed' => NewsletterSubscriber::where('status', 'unsubscribed')->count(),
            'rsvpsTotal' => EventRsvp::count(),
            'rsvpsConfirmed' => EventRsvp::where('status', 'confirmed')->count(),
        ];
    }

    public function growthTrend(int $days = 30): array
    {
        $dates = collect(range(0, $days - 1))
            ->map(fn ($i) => now()->subDays($days - 1 - $i)->toDateString())
            ->values();

        return $dates->map(fn ($date) => [
            'date' => $date,
            'volunteers' => Volunteer::whereDate('created_at', $date)->count(),
            'contacts' => Contact::whereDate('created_at', $date)->count(),
            'subscribers' => NewsletterSubscriber::whereDate('created_at', $date)->count(),
        ])->toArray();
    }

    public function lgaBreakdown(): array
    {
        $volunteers = Volunteer::groupBy('lga')
            ->selectRaw('lga, count(*) as count')
            ->orderByDesc('count')
            ->get()
            ->map(fn ($item) => ['lga' => $item->lga, 'count' => $item->count])
            ->toArray();

        $rsvps = EventRsvp::groupBy('lga')
            ->selectRaw('lga, count(*) as count')
            ->orderByDesc('count')
            ->get()
            ->map(fn ($item) => ['lga' => $item->lga, 'count' => $item->count])
            ->toArray();

        return [
            'volunteers' => $volunteers,
            'rsvps' => $rsvps,
        ];
    }

    public function volunteerSkillsInventory(): array
    {
        $volunteers = Volunteer::whereNotNull('skills')->get('skills');
        $skillCounts = [];

        foreach ($volunteers as $volunteer) {
            foreach ($volunteer->skills ?? [] as $skill) {
                $skillCounts[$skill] = ($skillCounts[$skill] ?? 0) + 1;
            }
        }

        arsort($skillCounts);
        return $skillCounts;
    }

    public function eventAttendanceRates(): array
    {
        return Event::active()
            ->get(['id', 'title', 'capacity'])
            ->map(function ($event) {
                $confirmed = EventRsvp::where('event_id', $event->id)
                    ->where('status', 'confirmed')
                    ->count();

                return [
                    'id' => $event->id,
                    'title' => $event->title,
                    'confirmed' => $confirmed,
                    'capacity' => $event->capacity,
                    'attendanceRate' => $event->capacity ? round(($confirmed / $event->capacity) * 100, 1) : 0,
                ];
            })
            ->toArray();
    }

    public function newsletterFunnel(): array
    {
        $confirmed = NewsletterSubscriber::where('status', 'confirmed')->count();
        $pending = NewsletterSubscriber::where('status', 'pending')->count();
        $unsubscribed = NewsletterSubscriber::where('status', 'unsubscribed')->count();

        return [
            'pending' => $pending,
            'confirmed' => $confirmed,
            'unsubscribed' => $unsubscribed,
        ];
    }
}
