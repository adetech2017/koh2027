<?php

namespace App\Http\Controllers\Admin;

use App\Events\VolunteerStatusChanged;
use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Volunteer;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class VolunteerController extends Controller
{
    public function index(Request $request): Response
    {
        $status = $request->query('status');
        $lga = $request->query('lga');
        $query = Volunteer::query();

        if ($status && in_array($status, ['pending', 'approved', 'active', 'inactive'])) {
            $query->where('status', $status);
        }

        if ($lga) {
            $query->where('lga', $lga);
        }

        $volunteers = $query->orderBy('created_at', 'desc')
            ->with(['notes.author', 'tags'])
            ->paginate(20);

        $statusCounts = [
            'pending' => Volunteer::where('status', 'pending')->count(),
            'approved' => Volunteer::where('status', 'approved')->count(),
            'active' => Volunteer::where('status', 'active')->count(),
            'inactive' => Volunteer::where('status', 'inactive')->count(),
        ];

        $lgas = Volunteer::distinct('lga')->pluck('lga');

        return Inertia::render('Admin/Volunteers/Index', [
            'volunteers' => $volunteers,
            'statusCounts' => $statusCounts,
            'lgaList' => $lgas,
            'filters' => $request->only('status', 'lga'),
        ]);
    }

    public function show(Volunteer $volunteer): Response
    {
        $volunteer->load(['notes.author', 'tags', 'activities']);

        return Inertia::render('Admin/Volunteers/Show', [
            'volunteer' => $volunteer,
        ]);
    }

    public function update(Request $request, Volunteer $volunteer): RedirectResponse
    {
        $oldStatus = $volunteer->status;

        $validated = $request->validate([
            'status' => ['required', 'in:pending,approved,active,inactive'],
        ]);

        $updates = $validated;
        if ($validated['status'] === 'approved' && !$volunteer->approved_at) {
            $updates['approved_at'] = now();
        }

        $volunteer->update($updates);

        ActivityLog::record('status_updated', $volunteer, [
            'old_status' => $oldStatus,
            'new_status' => $validated['status'],
        ], auth()->user());

        VolunteerStatusChanged::dispatch($volunteer, $validated['status'], $oldStatus);

        return back()->with('success', 'Volunteer status updated successfully.');
    }
}
