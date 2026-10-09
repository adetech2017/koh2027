<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\EventRsvp;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class EventRsvpController extends Controller
{
    // Matches the event_rsvps.status enum
    private const STATUSES = ['confirmed', 'cancelled'];

    public function index(Request $request, Event $event): Response
    {
        $status = $request->query('status');
        $search = trim((string) $request->query('search'));

        // Token excluded: it lets the holder cancel the RSVP
        $rsvps = $event->rsvps()
            ->when(in_array($status, self::STATUSES), fn ($query) => $query->where('status', $status))
            ->when($search !== '', fn ($query) => $query->where(fn ($q) => $q
                ->where('name', 'like', "%{$search}%")
                ->orWhere('email', 'like', "%{$search}%")
                ->orWhere('phone', 'like', "%{$search}%")))
            ->latest()
            ->paginate(50, ['id', 'event_id', 'name', 'email', 'phone', 'lga', 'status', 'confirmed_at', 'created_at'])
            ->withQueryString();

        $statusCounts = array_merge(
            array_fill_keys(self::STATUSES, 0),
            $event->rsvps()->groupBy('status')->selectRaw('status, count(*) as count')->pluck('count', 'status')->toArray()
        );
        $statusCounts['all'] = array_sum($statusCounts);

        return Inertia::render('Admin/Events/RSVPs', [
            'event' => $event->only(['id', 'title', 'venue_name', 'address', 'lga', 'starts_at', 'ends_at', 'capacity', 'rsvp_enabled', 'is_active']),
            'rsvps' => $rsvps,
            'statusCounts' => $statusCounts,
            'filters' => ['status' => $status, 'search' => $search],
        ]);
    }

    public function update(Request $request, Event $event, EventRsvp $rsvp): RedirectResponse
    {
        abort_unless($rsvp->event_id === $event->id, 404);

        $validated = $request->validate([
            'status' => ['required', 'in:'.implode(',', self::STATUSES)],
        ]);

        $rsvp->update([
            'status' => $validated['status'],
            'confirmed_at' => $validated['status'] === 'confirmed' ? ($rsvp->confirmed_at ?? now()) : $rsvp->confirmed_at,
        ]);

        return back()->with('success', "RSVP for {$rsvp->name} marked as {$validated['status']}.");
    }

    public function destroy(Event $event, EventRsvp $rsvp): RedirectResponse
    {
        Gate::authorize('manage-content');
        abort_unless($rsvp->event_id === $event->id, 404);

        $rsvp->delete();

        return back()->with('success', 'RSVP removed.');
    }
}
