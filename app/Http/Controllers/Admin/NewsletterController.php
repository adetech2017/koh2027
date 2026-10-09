<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Mail\NewsletterConfirmationMail;
use App\Models\NewsletterSubscriber;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Mail;
use Inertia\Inertia;
use Inertia\Response;

class NewsletterController extends Controller
{
    private const STATUSES = ['pending', 'confirmed', 'unsubscribed'];

    public function index(Request $request): Response
    {
        $status = $request->query('status');
        $search = trim((string) $request->query('search'));

        // Only safe columns: the token would let anyone confirm/unsubscribe on the subscriber's behalf
        $subscribers = NewsletterSubscriber::query()
            ->when(in_array($status, self::STATUSES), fn ($query) => $query->where('status', $status))
            ->when($search !== '', fn ($query) => $query->where(fn ($q) => $q
                ->where('email', 'like', "%{$search}%")
                ->orWhere('name', 'like', "%{$search}%")))
            ->latest()
            ->paginate(50, ['id', 'email', 'name', 'status', 'confirmed_at', 'unsubscribed_at', 'created_at'])
            ->withQueryString();

        $funnel = array_merge(
            array_fill_keys(self::STATUSES, 0),
            NewsletterSubscriber::groupBy('status')
                ->selectRaw('status, count(*) as count')
                ->pluck('count', 'status')
                ->toArray()
        );

        return Inertia::render('Admin/Newsletter/Index', [
            'subscribers' => $subscribers,
            'funnel' => $funnel,
            'newThisWeek' => NewsletterSubscriber::where('created_at', '>=', now()->subDays(7))->count(),
            'filters' => ['status' => $status, 'search' => $search],
        ]);
    }

    public function resend(NewsletterSubscriber $subscriber): RedirectResponse
    {
        Gate::authorize('manage-content');

        if ($subscriber->status !== 'pending') {
            return back()->with('error', 'Only pending subscribers can be sent a confirmation email.');
        }

        Mail::send(new NewsletterConfirmationMail($subscriber));

        return back()->with('success', "Confirmation email re-sent to {$subscriber->email}.");
    }
}
