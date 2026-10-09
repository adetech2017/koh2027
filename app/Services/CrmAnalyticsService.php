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
        $start = now()->subDays($days - 1)->startOfDay();

        $volunteers = $this->dailyCounts(Volunteer::query(), $start);
        $contacts = $this->dailyCounts(Contact::query(), $start);
        $subscribers = $this->dailyCounts(NewsletterSubscriber::query(), $start);

        return collect(range(0, $days - 1))
            ->map(function ($i) use ($start, $volunteers, $contacts, $subscribers) {
                $date = $start->copy()->addDays($i)->toDateString();

                return [
                    'date' => $date,
                    'volunteers' => (int) ($volunteers[$date] ?? 0),
                    'contacts' => (int) ($contacts[$date] ?? 0),
                    'subscribers' => (int) ($subscribers[$date] ?? 0),
                ];
            })
            ->toArray();
    }

    private function dailyCounts($query, $start): array
    {
        return $query->where('created_at', '>=', $start)
            ->selectRaw('DATE(created_at) as day, count(*) as count')
            ->groupBy('day')
            ->pluck('count', 'day')
            ->toArray();
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

    /**
     * RSVP fill for upcoming events (soonest first), then the most recent past ones.
     */
    public function eventAttendanceRates(int $limit = 8): array
    {
        $withConfirmed = fn ($query) => $query->withCount(['rsvps as confirmed' => fn ($q) => $q->where('status', 'confirmed')]);

        $upcoming = $withConfirmed(Event::active()->where('starts_at', '>=', now()->startOfDay()))
            ->orderBy('starts_at')->take($limit)->get();
        $past = $withConfirmed(Event::active()->where('starts_at', '<', now()->startOfDay()))
            ->orderByDesc('starts_at')->take(max(0, $limit - $upcoming->count()))->get();

        return $upcoming->concat($past)
            ->map(fn ($event) => [
                'id' => $event->id,
                'title' => $event->title,
                'starts_at' => $event->starts_at,
                'is_past' => $event->starts_at->isPast(),
                'confirmed' => (int) $event->confirmed,
                'capacity' => $event->capacity,
                'fillRate' => $event->capacity ? round(($event->confirmed / $event->capacity) * 100, 1) : null,
            ])
            ->values()
            ->toArray();
    }

    /**
     * New sign-ups in the last $days days, and in the $days before that, for comparison.
     */
    public function periodTotals(int $days): array
    {
        $start = now()->subDays($days - 1)->startOfDay();
        $previousStart = $start->copy()->subDays($days);

        $models = [
            'volunteers' => Volunteer::class,
            'contacts' => Contact::class,
            'subscribers' => NewsletterSubscriber::class,
            'rsvps' => EventRsvp::class,
        ];

        $totals = [];
        foreach ($models as $key => $model) {
            $totals[$key] = [
                'current' => $model::where('created_at', '>=', $start)->count(),
                'previous' => $model::whereBetween('created_at', [$previousStart, $start])->count(),
            ];
        }

        return $totals;
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
