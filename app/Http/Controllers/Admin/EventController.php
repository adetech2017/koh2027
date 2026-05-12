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
    public function index(): Response
    {
        $events = Event::orderBy('starts_at', 'desc')->paginate(20);

        return Inertia::render('Admin/Events/Index', [
            'events' => $events,
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Admin/Events/Create');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:200'],
            'description' => ['required', 'string'],
            'venue_name' => ['required', 'string', 'max:200'],
            'address' => ['required', 'string', 'max:300'],
            'lga' => ['required', 'string', 'max:100'],
            'event_type' => ['required', 'in:rally,townhall,fundraiser,workshop,meeting,other'],
            'starts_at' => ['required', 'date_format:Y-m-d H:i'],
            'ends_at' => ['required', 'date_format:Y-m-d H:i'],
            'capacity' => ['required', 'integer', 'min:1'],
            'rsvp_enabled' => ['boolean'],
            'image' => ['required', 'image', 'mimes:jpeg,png,jpg,gif', 'max:2048'],
            'map_embed_url' => ['nullable', 'string', 'url'],
            'is_active' => ['boolean'],
            'is_featured' => ['boolean'],
        ]);

        if ($request->hasFile('image')) {
            $validated['image_path'] = $request->file('image')->store('events', 'public');
        }

        unset($validated['image']);
        Event::create($validated);

        return redirect()->route('admin.events.index')->with('success', 'Event created successfully.');
    }

    public function edit(Event $event): Response
    {
        return Inertia::render('Admin/Events/Edit', [
            'event' => $event,
        ]);
    }

    public function update(Request $request, Event $event): RedirectResponse
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:200'],
            'description' => ['required', 'string'],
            'venue_name' => ['required', 'string', 'max:200'],
            'address' => ['required', 'string', 'max:300'],
            'lga' => ['required', 'string', 'max:100'],
            'event_type' => ['required', 'in:rally,townhall,fundraiser,workshop,meeting,other'],
            'starts_at' => ['required', 'date_format:Y-m-d H:i'],
            'ends_at' => ['required', 'date_format:Y-m-d H:i'],
            'capacity' => ['required', 'integer', 'min:1'],
            'rsvp_enabled' => ['boolean'],
            'image' => ['nullable', 'image', 'mimes:jpeg,png,jpg,gif', 'max:2048'],
            'map_embed_url' => ['nullable', 'string', 'url'],
            'is_active' => ['boolean'],
            'is_featured' => ['boolean'],
        ]);

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
        $event->delete();

        return redirect()->route('admin.events.index')->with('success', 'Event deleted successfully.');
    }
}
