<?php

namespace App\Http\Controllers;

use App\Http\Requests\EventRsvpRequest;
use App\Models\Event;
use App\Models\EventRsvp;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class EventController extends Controller
{
    public function index(Request $request): Response
    {
        $when = $request->query('when') === 'past' ? 'past' : 'upcoming';

        $query = Event::active()
            ->when($when === 'upcoming', fn ($q) => $q->where('starts_at', '>=', now()->startOfDay())->orderBy('starts_at'))
            ->when($when === 'past', fn ($q) => $q->where('starts_at', '<', now()->startOfDay())->orderByDesc('starts_at'))
            ->when($request->filled('lga'), fn ($q) => $q->where('lga', $request->lga))
            ->when($request->filled('type'), fn ($q) => $q->where('event_type', $request->type));

        $events = $query->paginate(12, ['id', 'title', 'slug', 'lga', 'event_type', 'starts_at', 'image_path', 'image_alt'])
            ->withQueryString()
            ->through(fn ($event) => [...$event->toArray(), 'image_url' => $event->image_url]);

        return Inertia::render('Events/Index', [
            'events' => $events,
            'filters' => [...$request->only(['lga', 'type']), 'when' => $when],
            'counts' => [
                'upcoming' => Event::active()->where('starts_at', '>=', now()->startOfDay())->count(),
                'past' => Event::active()->where('starts_at', '<', now()->startOfDay())->count(),
            ],
            'lgas' => Event::active()->whereNotNull('lga')->distinct()->orderBy('lga')->pluck('lga'),
        ])->withViewData(['meta' => [
            'title' => 'Campaign Events — KOH 2027',
            'description' => 'Rallies, town halls and community meetings across Lagos State. Find an event near you and RSVP.',
        ]]);
    }

    public function show(Request $request, string $slug): Response
    {
        $event = Event::where('slug', $slug)->where('is_active', true)->firstOrFail();
        $rsvpCount = $event->rsvp_count;
        $isPast = ($event->ends_at ?? $event->starts_at)->isPast();

        return Inertia::render('Events/Show', [
            'event' => [
                ...$event->only(['id', 'title', 'slug', 'description', 'venue_name', 'address', 'lga', 'event_type', 'starts_at', 'ends_at', 'capacity', 'rsvp_enabled', 'image_alt']),
                'image_url' => $event->image_url,
                'map_embed_url' => $this->safeMapUrl($event->map_embed_url),
            ],
            'rsvpCount' => $rsvpCount,
            'isFull' => $event->is_full,
            'isPast' => $isPast,
            'placesLeft' => $event->capacity ? max(0, $event->capacity - $rsvpCount) : null,
            'shareUrl' => route('events.show', $event->slug),
        ])->withViewData(['meta' => [
            'title' => $event->title,
            'description' => Str::limit(Str::squish($event->description), 200),
            'image' => $event->image_url,
        ]]);
    }

    public function rsvp(EventRsvpRequest $request, int $id): RedirectResponse
    {
        $event = Event::where('is_active', true)->findOrFail($id);

        if (!$event->rsvp_enabled) {
            return back()->withErrors(['rsvp' => 'RSVPs are not open for this event.']);
        }

        if (($event->ends_at ?? $event->starts_at)->isPast()) {
            return back()->withErrors(['rsvp' => 'This event has already taken place.']);
        }

        if ($event->is_full) {
            return back()->withErrors(['rsvp' => 'Sorry, this event is now full.']);
        }

        $existing = EventRsvp::where('event_id', $id)
            ->where('email', $request->email)
            ->where('status', 'confirmed')
            ->exists();

        if ($existing) {
            return back()->withErrors(['email' => "You're already registered for this event with this email."]);
        }

        EventRsvp::create([
            'event_id' => $id,
            'name' => $request->name,
            'email' => $request->email,
            'phone' => $request->phone,
            'lga' => $request->lga,
            'confirmed_at' => now(),
        ]);

        return back()->with('success', "You're registered! See you at {$event->title}.");
    }

    // Only allow Google Maps embeds in the iframe
    private function safeMapUrl(?string $url): ?string
    {
        if (!$url) {
            return null;
        }
        $host = parse_url($url, PHP_URL_HOST);

        return $host && preg_match('/(^|\.)google\.[a-z.]+$/i', $host) && str_starts_with($url, 'https://') ? $url : null;
    }
}
