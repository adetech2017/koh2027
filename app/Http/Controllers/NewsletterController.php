<?php

namespace App\Http\Controllers;

use App\Http\Requests\NewsletterRequest;
use App\Mail\NewsletterConfirmationMail;
use App\Models\NewsletterSubscriber;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Mail;

class NewsletterController extends Controller
{
    public function subscribe(NewsletterRequest $request): RedirectResponse
    {
        $existing = NewsletterSubscriber::where('email', $request->email)->first();

        if ($existing && $existing->status === 'confirmed') {
            return back()->with('info', 'You are already subscribed.');
        }

        if ($existing) {
            $subscriber = $existing;
            $subscriber->update(['status' => 'pending', 'name' => $request->name]);
        } else {
            $subscriber = NewsletterSubscriber::create([
                'email' => $request->email,
                'name' => $request->name,
                'ip_address' => $request->ip(),
            ]);
        }

        Mail::send(new NewsletterConfirmationMail($subscriber));

        return back()->with('success', 'Please check your email to confirm your subscription.');
    }

    public function confirm(string $token): RedirectResponse
    {
        $subscriber = NewsletterSubscriber::where('token', $token)
            ->where('status', 'pending')
            ->firstOrFail();

        $subscriber->update([
            'status' => 'confirmed',
            'confirmed_at' => now(),
        ]);

        return redirect('/')->with('success', 'Your subscription is confirmed. Welcome!');
    }

    public function unsubscribe(string $token): RedirectResponse
    {
        $subscriber = NewsletterSubscriber::where('token', $token)->firstOrFail();

        $subscriber->update([
            'status' => 'unsubscribed',
            'unsubscribed_at' => now(),
        ]);

        return redirect('/')->with('info', 'You have been unsubscribed.');
    }
}
