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
