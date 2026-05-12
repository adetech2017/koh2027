<?php

namespace App\Http\Controllers\Admin;

use App\Models\NewsletterSubscriber;
use App\Models\Volunteer;
use App\Services\BulkEmailService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class BulkEmailController
{
    public function __construct(private BulkEmailService $emailService) {}

    public function compose(): Response
    {
        Gate::authorize('manage-users');

        return Inertia::render('Admin/BulkEmail', [
            'audienceCounts' => [
                'subscribers' => NewsletterSubscriber::where('status', 'confirmed')->count(),
                'volunteers' => Volunteer::where('status', 'active')->count(),
            ],
        ]);
    }

    public function send(Request $request)
    {
        Gate::authorize('manage-users');

        $validated = $request->validate([
            'audience' => 'required|in:subscribers,volunteers',
            'subject' => 'required|string|max:255',
            'body' => 'required|string|max:10000',
        ]);

        $count = match ($validated['audience']) {
            'subscribers' => $this->emailService->sendToSubscribers(
                $validated['subject'],
                $validated['body']
            ),
            'volunteers' => $this->emailService->sendToVolunteers(
                $validated['subject'],
                $validated['body']
            ),
        };

        return back()->with('success', "Email queued for {$count} recipients");
    }
}
