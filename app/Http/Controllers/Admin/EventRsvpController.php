<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\EventRsvp;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class EventRsvpController extends Controller
{
    public function index(Event $event): Response
    {
        $rsvps = $event->rsvps()->with('volunteer')->paginate(20);

        $statusCounts = [
            'total' => $event->rsvps()->count(),
            'pending' => $event->rsvps()->where('status', 'pending')->count(),
            'confirmed' => $event->rsvps()->where('status', 'confirmed')->count(),
            'declined' => $event->rsvps()->where('status', 'declined')->count(),
            'cancelled' => $event->rsvps()->where('status', 'cancelled')->count(),
        ];

        return Inertia::render('Admin/Events/RSVPs', [
            'event' => $event,
            'rsvps' => $rsvps,
            'statusCounts' => $statusCounts,
        ]);
    }

    public function update(Request $request, Event $event, EventRsvp $rsvp): RedirectResponse
    {
        $validated = $request->validate([
            'status' => ['required', 'in:pending,confirmed,declined,cancelled'],
        ]);

        $rsvp->update($validated);

        return back()->with('success', 'RSVP status updated successfully.');
    }

    public function destroy(Event $event, EventRsvp $rsvp): RedirectResponse
    {
        $rsvp->delete();

        return back()->with('success', 'RSVP removed successfully.');
    }
}
