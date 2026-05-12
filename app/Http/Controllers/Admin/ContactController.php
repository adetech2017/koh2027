<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Contact;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ContactController extends Controller
{
    public function index(): Response
    {
        $contacts = Contact::orderBy('created_at', 'desc')
            ->with(['notes.author', 'tags'])
            ->paginate(20);

        $statusCounts = [
            'new' => Contact::where('status', 'new')->count(),
            'read' => Contact::where('status', 'read')->count(),
            'replied' => Contact::where('status', 'replied')->count(),
            'archived' => Contact::where('status', 'archived')->count(),
        ];

        return Inertia::render('Admin/Contacts/Index', [
            'contacts' => $contacts,
            'statusCounts' => $statusCounts,
        ]);
    }

    public function show(Contact $contact): Response
    {
        $contact->load(['notes.author', 'tags', 'activities']);

        return Inertia::render('Admin/Contacts/Show', [
            'contact' => $contact,
        ]);
    }

    public function update(Request $request, Contact $contact): RedirectResponse
    {
        $oldStatus = $contact->status;

        $validated = $request->validate([
            'status' => ['required', 'in:new,read,replied,archived'],
        ]);

        $contact->update(array_merge($validated, [
            'read_at' => $validated['status'] !== 'new' ? now() : null,
        ]));

        ActivityLog::record('status_updated', $contact, [
            'old_status' => $oldStatus,
            'new_status' => $validated['status'],
        ], auth()->user());

        return back()->with('success', 'Contact status updated successfully.');
    }
}
