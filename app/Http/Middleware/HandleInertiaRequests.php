<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    protected $rootView = 'app';

    public function share(Request $request): array
    {
        $ziggy = new \Tighten\Ziggy\Ziggy();
        return array_merge(parent::share($request), [
            'appName' => config('app.name'),
            // Campaign logo: drop public/images/logo.svg (or .png) in place; the text badge shows until then
            'logoUrl' => function () {
                $path = collect(['images/logo.svg', 'images/logo.png', 'images/logo.webp'])
                    ->first(fn ($path) => is_file(public_path($path)));

                return $path ? asset($path).'?v='.filemtime(public_path($path)) : null;
            },
            'campaign' => [
                'email' => config('campaign.contact_email'),
                'phone' => config('campaign.contact_phone'),
                'address' => config('campaign.address'),
            ],
            // Upcoming election days for the countdowns; each one drops off once its day ends in Lagos
            'elections' => fn () => collect(config('campaign.elections'))
                ->filter(fn ($election) => filled($election['starts_at']))
                ->map(function ($election) {
                    $startsAt = \Illuminate\Support\Carbon::parse($election['starts_at'], 'Africa/Lagos');

                    return [
                        'key' => $election['key'],
                        'label' => $election['label'],
                        'starts_at' => $startsAt->toIso8601String(),
                        'ends_at' => $startsAt->copy()->endOfDay()->toIso8601String(),
                    ];
                })
                ->filter(fn ($election) => now()->lt($election['ends_at']))
                ->sortBy('starts_at')
                ->values(),
            'flash' => [
                'success' => fn () => $request->session()->get('success'),
                'error' => fn () => $request->session()->get('error'),
                'info' => fn () => $request->session()->get('info'),
            ],
            'auth' => [
                'user' => $request->user() ? [
                    'id' => $request->user()->id,
                    'name' => $request->user()->name,
                    'email' => $request->user()->email,
                    'role' => $request->user()->role,
                ] : null,
            ],
            'ziggy' => fn () => $ziggy->toArray(),
        ]);
    }
}
