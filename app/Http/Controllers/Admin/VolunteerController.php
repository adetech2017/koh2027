<?php

namespace App\Http\Controllers\Admin;

use App\Events\VolunteerStatusChanged;
use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Tag;
use App\Models\Volunteer;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class VolunteerController extends Controller
{
    private const STATUSES = ['pending', 'approved', 'active', 'inactive'];

    public function index(Request $request): Response
    {
        $status = $request->query('status');
        $lga = $request->query('lga');
        $vehicle = $request->query('vehicle');
        $search = trim((string) $request->query('search'));

        $volunteers = Volunteer::query()
            ->when(in_array($status, self::STATUSES), fn ($query) => $query->where('status', $status))
            ->when($lga, fn ($query) => $query->where('lga', $lga))
            ->when(in_array($vehicle, ['yes', 'no']), fn ($query) => $query->where('has_vehicle', $vehicle === 'yes'))
            // Every word must match somewhere, so "ada okafor" finds first + last name
            ->when($search !== '', function ($query) use ($search) {
                foreach (preg_split('/\s+/', $search) as $term) {
                    $query->where(fn ($q) => $q
                        ->where('first_name', 'like', "%{$term}%")
                        ->orWhere('last_name', 'like', "%{$term}%")
                        ->orWhere('email', 'like', "%{$term}%")
                        ->orWhere('phone', 'like', "%{$term}%"));
                }
            })
            ->latest()
            ->paginate(20, ['id', 'first_name', 'last_name', 'email', 'phone', 'lga', 'ward', 'skills', 'has_vehicle', 'status', 'created_at'])
            ->withQueryString()
            ->through(fn ($volunteer) => [
                ...$volunteer->only(['id', 'email', 'phone', 'lga', 'ward', 'skills', 'has_vehicle', 'status', 'created_at']),
                'name' => $volunteer->full_name,
            ]);

        $statusCounts = Volunteer::groupBy('status')
            ->selectRaw('status, count(*) as count')
            ->pluck('count', 'status')
            ->toArray();
        $statusCounts['all'] = array_sum($statusCounts);

        return Inertia::render('Admin/Volunteers/Index', [
            'volunteers' => $volunteers,
            'statusCounts' => $statusCounts,
            'lgaList' => Volunteer::whereNotNull('lga')->distinct()->orderBy('lga')->pluck('lga'),
            'filters' => [
                'status' => $status,
                'lga' => $lga,
                'vehicle' => $vehicle,
                'search' => $search,
            ],
        ]);
    }

    public function show(Volunteer $volunteer): Response
    {
        $volunteer->load([
            'notes' => fn ($query) => $query->latest()->with('author:id,name'),
            'tags:id,name,color',
            'activities' => fn ($query) => $query->latest()->with('user:id,name'),
        ]);
        $volunteer->append('full_name');

        return Inertia::render('Admin/Volunteers/Show', [
            'volunteer' => $volunteer,
            'allTags' => Tag::orderBy('name')->get(['id', 'name', 'color']),
        ]);
    }

    public function update(Request $request, Volunteer $volunteer): RedirectResponse
    {
        $oldStatus = $volunteer->status;

        $validated = $request->validate([
            'status' => ['required', 'in:'.implode(',', self::STATUSES)],
        ]);

        // Avoid re-sending the status email when nothing changed
        if ($validated['status'] === $oldStatus) {
            return back();
        }

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

        $message = "{$volunteer->full_name} marked as {$validated['status']}.";
        if (in_array($validated['status'], ['approved', 'active'])) {
            $message .= ' A notification email has been sent.';
        }

        return back()->with('success', $message);
    }
}
