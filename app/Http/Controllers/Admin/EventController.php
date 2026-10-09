<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Event;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class EventController extends Controller
{
    public const TYPES = ['rally', 'townhall', 'fundraiser', 'workshop', 'meeting', 'other'];

    public function index(Request $request): Response
    {
        $when = in_array($request->query('when'), ['upcoming', 'past', 'all']) ? $request->query('when') : 'upcoming';
        $search = trim((string) $request->query('search'));

        $events = Event::query()
            ->when($when === 'upcoming', fn ($query) => $query->where('starts_at', '>=', now()->startOfDay())->orderBy('starts_at'))
            ->when($when === 'past', fn ($query) => $query->where('starts_at', '<', now()->startOfDay())->orderByDesc('starts_at'))
            ->when($when === 'all', fn ($query) => $query->orderByDesc('starts_at'))
            ->when($search !== '', fn ($query) => $query->where(fn ($q) => $q
                ->where('title', 'like', "%{$search}%")
                ->orWhere('venue_name', 'like', "%{$search}%")
                ->orWhere('lga', 'like', "%{$search}%")))
            ->withCount(['rsvps as confirmed_rsvps_count' => fn ($query) => $query->where('status', 'confirmed')])
            ->paginate(20)
            ->withQueryString()
            ->through(fn ($event) => [
                ...$event->only([
                    'id', 'title', 'slug', 'venue_name', 'lga', 'event_type', 'starts_at', 'ends_at',
                    'capacity', 'rsvp_enabled', 'is_active', 'is_featured', 'confirmed_rsvps_count',
                ]),
                'image_url' => $event->image_url,
            ]);

        return Inertia::render('Admin/Events/Index', [
            'events' => $events,
            'counts' => [
                'upcoming' => Event::where('starts_at', '>=', now()->startOfDay())->count(),
                'past' => Event::where('starts_at', '<', now()->startOfDay())->count(),
                'all' => Event::count(),
            ],
            'filters' => ['when' => $when, 'search' => $search],
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Admin/Events/Create', [
            'lgaOptions' => $this->lgaOptions(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validated($request, imageRequired: true);

        $validated['image_path'] = $request->file('image')->store('events', 'public');
        unset($validated['image']);

        Event::create($validated);

        return redirect()->route('admin.events.index')->with('success', 'Event created successfully.');
    }

    public function edit(Event $event): Response
    {
        $event->append('image_url');

        return Inertia::render('Admin/Events/Edit', [
            'event' => $event,
            'rsvpCount' => $event->rsvps()->where('status', 'confirmed')->count(),
            'lgaOptions' => $this->lgaOptions(),
        ]);
    }

    public function update(Request $request, Event $event): RedirectResponse
    {
        $validated = $this->validated($request, imageRequired: false);

        if ($request->hasFile('image')) {
            if ($event->image_path) {
                Storage::disk('public')->delete($event->image_path);
            }
            $validated['image_path'] = $request->file('image')->store('events', 'public');
        }

        unset($validated['image']);
        $event->update($validated);

        return redirect()->route('admin.events.index')->with('success', 'Event updated successfully.');
    }

    public function destroy(Event $event): RedirectResponse
    {
        Gate::authorize('delete-content');

        if ($event->image_path) {
            Storage::disk('public')->delete($event->image_path);
        }
        $event->delete();

        return redirect()->route('admin.events.index')->with('success', 'Event deleted successfully.');
    }

    private function validated(Request $request, bool $imageRequired): array
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:200'],
            'description' => ['required', 'string'],
            'venue_name' => ['required', 'string', 'max:200'],
            'address' => ['required', 'string', 'max:300'],
            'lga' => ['required', 'string', 'max:100'],
            'event_type' => ['required', 'in:'.implode(',', self::TYPES)],
            'starts_at' => ['required', 'date_format:Y-m-d H:i'],
            'ends_at' => ['nullable', 'date_format:Y-m-d H:i', 'after:starts_at'],
            'capacity' => ['nullable', 'integer', 'min:1'],
            'rsvp_enabled' => ['boolean'],
            'image' => [$imageRequired ? 'required' : 'nullable', 'image', 'mimes:jpeg,png,jpg,gif,webp', 'max:2048'],
            'image_alt' => ['nullable', 'string', 'max:200'],
            'map_embed_url' => ['nullable', 'string', 'url'],
            'is_active' => ['boolean'],
            'is_featured' => ['boolean'],
        ], [
            'ends_at.after' => 'The end time must be after the start time.',
        ]);

        foreach (['rsvp_enabled', 'is_active', 'is_featured'] as $flag) {
            $validated[$flag] = $request->boolean($flag);
        }

        return $validated;
    }

    private function lgaOptions(): array
    {
        return Event::whereNotNull('lga')->distinct()->orderBy('lga')->pluck('lga')->all();
    }
}
