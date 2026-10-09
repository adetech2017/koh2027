<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Contact;
use App\Models\Tag;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ContactController extends Controller
{
    private const STATUSES = ['new', 'read', 'replied', 'archived'];

    public function index(Request $request): Response
    {
        $status = $request->query('status');
        $search = trim((string) $request->query('search'));

        $contacts = Contact::query()
            ->when(in_array($status, self::STATUSES), fn ($query) => $query->where('status', $status))
            ->when($search !== '', fn ($query) => $query->where(fn ($q) => $q
                ->where('name', 'like', "%{$search}%")
                ->orWhere('email', 'like', "%{$search}%")
                ->orWhere('subject', 'like', "%{$search}%")))
            ->with('tags:id,name,color')
            ->latest()
            ->paginate(20, ['id', 'name', 'email', 'subject', 'message', 'status', 'created_at'])
            ->withQueryString()
            ->through(fn ($contact) => [
                ...$contact->only(['id', 'name', 'email', 'subject', 'status', 'created_at', 'tags']),
                'preview' => str($contact->message)->squish()->limit(90)->toString(),
            ]);

        $statusCounts = Contact::groupBy('status')
            ->selectRaw('status, count(*) as count')
            ->pluck('count', 'status')
            ->toArray();
        $statusCounts['all'] = array_sum($statusCounts);

        return Inertia::render('Admin/Contacts/Index', [
            'contacts' => $contacts,
            'statusCounts' => $statusCounts,
            'filters' => ['status' => $status, 'search' => $search],
        ]);
    }

    public function show(Contact $contact): Response
    {
        // Opening a new message counts as reading it
        if ($contact->status === 'new') {
            $contact->update(['status' => 'read', 'read_at' => now()]);
            ActivityLog::record('status_updated', $contact, [
                'old_status' => 'new',
                'new_status' => 'read',
            ], auth()->user());
        }

        $contact->load([
            'notes' => fn ($query) => $query->latest()->with('author:id,name'),
            'tags:id,name,color',
            'activities' => fn ($query) => $query->latest()->with('user:id,name'),
        ]);

        return Inertia::render('Admin/Contacts/Show', [
            'contact' => $contact,
            'allTags' => Tag::orderBy('name')->get(['id', 'name', 'color']),
        ]);
    }

    public function update(Request $request, Contact $contact): RedirectResponse
    {
        $oldStatus = $contact->status;

        $validated = $request->validate([
            'status' => ['required', 'in:'.implode(',', self::STATUSES)],
        ]);

        $contact->update(array_merge($validated, [
            'read_at' => $validated['status'] !== 'new' ? ($contact->read_at ?? now()) : null,
        ]));

        ActivityLog::record('status_updated', $contact, [
            'old_status' => $oldStatus,
            'new_status' => $validated['status'],
        ], auth()->user());

        return back()->with('success', 'Contact marked as '.$validated['status'].'.');
    }
}
