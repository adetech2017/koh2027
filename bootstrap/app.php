<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Foundation\Events\DiagnosingHealth;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\View;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        // The fleet subdomain is registered first: routes match in the order they're added,
        // and its catch-all routes claim every path on that host, so none of the campaign
        // site's pages answer there.
        using: function () {
            Route::domain(config('fleet.domain'))
                ->middleware('fleet')
                ->name('fleet.')
                ->group(base_path('routes/fleet.php'));

            Route::middleware('api')->prefix('api')->group(base_path('routes/api.php'));

            // Same response as the framework's built-in health route, which `using` replaces
            Route::get('/up', function (Request $request) {
                Event::dispatch(new DiagnosingHealth);

                return $request->expectsJson()
                    ? response()->json(['status' => 'up'])
                    : response(View::file(base_path('vendor/laravel/framework/src/Illuminate/Foundation/resources/health-up.blade.php'), ['exception' => null]));
            });

            Route::middleware('web')->group(base_path('routes/web.php'));
        },
        commands: __DIR__.'/../routes/console.php',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // The fleet app has its own session cookie and none of the campaign site's
        // Inertia, analytics or staff-account middleware
        $middleware->group('fleet', [
            \App\Http\Middleware\Fleet\UseFleetSession::class,
            \Illuminate\Cookie\Middleware\EncryptCookies::class,
            \Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse::class,
            \Illuminate\Session\Middleware\StartSession::class,
            \Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class,
            \Illuminate\Routing\Middleware\SubstituteBindings::class,
        ]);

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
            'fleet.active' => \App\Http\Middleware\Fleet\EnsureFleetAccountActive::class,
            'fleet.role' => \App\Http\Middleware\Fleet\EnsureFleetAdminRole::class,
        ]);

        // The built-in health route did this itself; `using` routing replaces it
        $middleware->preventRequestsDuringMaintenance(except: ['up']);

        $middleware->redirectGuestsTo(fn () => route('admin.login'));
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // The fleet API always answers in JSON (401s, 404s, validation errors), whatever the client sends
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->expectsJson()
                || ($request->getHost() === config('fleet.domain') && $request->is('api/*'))
        );
    })->create();
