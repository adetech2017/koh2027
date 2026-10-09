<?php

namespace App\Http\Controllers\Admin;

use App\Models\EmailCampaign;
use App\Services\BulkEmailService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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
            'audienceCounts' => $this->emailService->counts(),
            'recentCampaigns' => EmailCampaign::with('sender:id,name')->latest()->take(10)->get(),
            'delivery' => $this->deliveryStatus(),
            'senderEmail' => auth()->user()->email,
        ]);
    }

    public function test(Request $request): RedirectResponse
    {
        Gate::authorize('manage-users');

        $content = $this->validated($request, requireAudience: false);
        $user = $request->user();

        $this->emailService->sendTest($user->email, strtok($user->name, ' ') ?: null, $content);

        return back()->with('success', "Test email sent to {$user->email}.");
    }

    public function send(Request $request): RedirectResponse
    {
        Gate::authorize('manage-users');

        $content = $this->validated($request, requireAudience: true);
        $audience = $request->input('audience');

        // Stop an accidental double click (or a second admin) re-sending the same email
        $duplicate = EmailCampaign::where('subject', $content['subject'])
            ->where('audience', $audience)
            ->where('created_at', '>=', now()->subMinutes(10))
            ->exists();
        if ($duplicate && !$request->boolean('confirm_duplicate')) {
            return back()->withErrors(['subject' => 'An email with this subject was sent to this audience in the last 10 minutes. Change the subject if this is a new message.']);
        }

        $count = $this->emailService->send($audience, $content);

        if ($count === 0) {
            return back()->withErrors(['audience' => 'There is nobody in this audience yet.']);
        }

        EmailCampaign::create([
            ...$content,
            'audience' => $audience,
            'recipients_count' => $count,
            'sent_by' => $request->user()->id,
        ]);

        return redirect()->route('admin.bulk-email.compose')
            ->with('success', "Campaign queued for {$count} recipient".($count !== 1 ? 's' : '').'.');
    }

    private function validated(Request $request, bool $requireAudience): array
    {
        $validated = $request->validate([
            'audience' => [$requireAudience ? 'required' : 'nullable', 'in:'.implode(',', BulkEmailService::AUDIENCES)],
            'subject' => ['required', 'string', 'max:150'],
            'body' => ['required', 'string', 'max:10000'],
            'cta_text' => ['nullable', 'required_with:cta_url', 'string', 'max:50'],
            'cta_url' => ['nullable', 'required_with:cta_text', 'url:https,http', 'max:255'],
        ], [
            'cta_url.required_with' => 'Add the link for the button, or clear the button text.',
            'cta_text.required_with' => 'Add the button text, or clear the link.',
        ]);

        return [
            'subject' => $validated['subject'],
            'body' => $validated['body'],
            'cta_text' => $validated['cta_text'] ?? null,
            'cta_url' => $validated['cta_url'] ?? null,
        ];
    }

    /**
     * Warn when emails won't actually go out: the log mailer only writes to a file, and queued
     * emails sit in the jobs table until a queue worker picks them up.
     */
    private function deliveryStatus(): array
    {
        $queue = config('queue.default');
        $pending = $queue === 'database' ? DB::table('jobs')->count() : null;
        $oldest = $queue === 'database' ? DB::table('jobs')->min('created_at') : null;

        return [
            'mailer' => config('mail.default'),
            'fromAddress' => config('mail.from.address'),
            'fromName' => config('mail.from.name'),
            'queue' => $queue,
            'pendingJobs' => $pending,
            // A job waiting more than 10 minutes means no worker is running
            'workerStalled' => $oldest !== null && now()->timestamp - (int) $oldest > 600,
            'failedJobs' => DB::table('failed_jobs')->count(),
        ];
    }
}
