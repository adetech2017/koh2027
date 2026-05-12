<?php

namespace App\Http\Controllers;

use App\Http\Requests\EventRsvpRequest;
use App\Models\Event;
use App\Models\EventRsvp;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class EventController extends Controller
{
    public function index(Request $request): Response
    {
        $query = Event::upcoming();

        if ($request->filled('lga')) {
            $query->where('lga', $request->lga);
        }

        if ($request->filled('type')) {
            $query->where('event_type', $request->type);
        }

        $events = $query->paginate(12);
        $lgas = Event::distinct('lga')->pluck('lga');

        return Inertia::render('Events/Index', [
            'events' => $events,
            'filters' => $request->only(['lga', 'type']),
            'lgas' => $lgas,
        ]);
    }

    public function show(Request $request, string $slug): Response
    {
        $event = Event::where('slug', $slug)->where('is_active', true)->firstOrFail();
        $rsvpCount = $event->rsvp_count;
        $isFull = $event->is_full;

        return Inertia::render('Events/Show', [
            'event' => [...$event->toArray(), 'image_url' => $event->image_url],
            'rsvpCount' => $rsvpCount,
            'isFull' => $isFull,
            'meta' => [
                'title' => $event->title,
                'description' => $event->description,
                'image' => $event->image_url,
                'url' => request()->url(),
            ],
        ]);
    }

    public function rsvp(EventRsvpRequest $request, int $id): RedirectResponse
    {
        $event = Event::findOrFail($id);

        if (!$event->rsvp_enabled) {
            return back()->with('error', 'RSVPs are not enabled for this event.');
        }

        if ($event->is_full) {
            return back()->with('error', 'This event is at full capacity.');
        }

        $existing = EventRsvp::where('event_id', $id)
            ->where('email', $request->email)
            ->where('status', 'confirmed')
            ->exists();

        if ($existing) {
            return back()->with('error', 'You have already registered for this event.');
        }

        EventRsvp::create([
            'event_id' => $id,
            'name' => $request->name,
            'email' => $request->email,
            'phone' => $request->phone,
            'lga' => $request->lga,
        ]);

        return back()->with('success', 'Your RSVP has been confirmed!');
    }
}
