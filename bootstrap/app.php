<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->web(append: [
            \App\Http\Middleware\EnsureAccountActive::class,
            \App\Http\Middleware\HandleInertiaRequests::class,
            \App\Http\Middleware\TrackPageView::class,
        ]);

        // The DNS record is currently "DNS only", so requests arrive straight from visitors and
        // nothing here applies. If it is ever switched to "Proxied", requests come from
        // Cloudflare instead; trusting only Cloudflare's published ranges lets request()->ip()
        // keep returning the real visitor (used by analytics and rate limits) without letting
        // anyone else spoof X-Forwarded-For. Ranges: https://www.cloudflare.com/ips/
        $middleware->trustProxies(at: [
            '173.245.48.0/20', '103.21.244.0/22', '103.22.200.0/22', '103.31.4.0/22',
            '141.101.64.0/18', '108.162.192.0/18', '190.93.240.0/20', '188.114.96.0/20',
            '197.234.240.0/22', '198.41.128.0/17', '162.158.0.0/15', '104.16.0.0/13',
            '104.24.0.0/14', '172.64.0.0/13', '131.0.72.0/22',
            '2400:cb00::/32', '2606:4700::/32', '2803:f800::/32', '2405:b500::/32',
            '2405:8100::/32', '2a06:98c0::/29', '2c0f:f248::/32',
        ]);

        // Click events arrive via navigator.sendBeacon, which can't send the CSRF header.
        // The endpoint only accepts a fixed list of event names and is rate-limited.
        $middleware->validateCsrfTokens(except: ['t/e']);

        $middleware->alias([
            'admin.role' => \App\Http\Middleware\EnsureAdminRole::class,
        ]);

        $middleware->redirectGuestsTo(fn () => route('admin.login'));
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
