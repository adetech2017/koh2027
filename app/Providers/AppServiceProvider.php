<?php

namespace App\Providers;

use App\Models\Event;
use App\Models\GalleryImage;
use App\Models\HeroSlide;
use App\Models\Material;
use App\Models\NewsArticle;
use App\Models\PlatformPillar;
use App\Observers\ContentObserver;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Short names in polymorphic columns (fleet audit log actors and subjects)
        Relation::morphMap([
            'fleet_admin' => \App\Models\Fleet\FleetAdmin::class,
            'fleet_beneficiary' => \App\Models\Fleet\Beneficiary::class,
        ]);

        RateLimiter::for('contact', function (Request $request) {
            // A readable form error instead of a bare 429 page
            return Limit::perHour(5)->by($request->ip())->response(
                fn () => back()->withErrors(['message' => 'You have sent several messages recently. Please wait a little before sending another.'])
            );
        });

        RateLimiter::for('newsletter', function (Request $request) {
            // A readable form error instead of a bare 429 page
            return Limit::perHour(10)->by($request->ip())->response(
                fn () => back()->withErrors(['email' => 'Too many attempts. Please try again in a few minutes.'])
            );
        });

        RateLimiter::for('volunteer', function (Request $request) {
            // A readable form error instead of a bare 429 page
            return Limit::perHour(3)->by($request->ip())->response(
                fn () => back()->withErrors(['email' => 'Too many sign-ups from this connection. Please try again later.'])
            );
        });

        RateLimiter::for('manifesto-chat', function (Request $request) {
            return Limit::perMinute(10)->by($request->ip());
        });

        // Fleet app: WhatsApp login codes cost money per message, so limit by number and by connection
        RateLimiter::for('fleet-otp', function (Request $request) {
            // Normalised, so 0803... and +234803... count as the same number
            $phone = \App\Support\Fleet\PhoneNumber::normalize($request->input('phone')) ?? 'raw:'.$request->input('phone');
            $message = fn () => response()->json(['message' => 'Too many code requests. Please wait a few minutes before trying again.'], 429);

            return [
                Limit::perMinute(1)->by('fleet-otp-phone:'.$phone)->response($message),
                Limit::perHour(5)->by('fleet-otp-phone-hour:'.$phone)->response($message),
                Limit::perHour(30)->by('fleet-otp-ip:'.$request->ip())->response($message),
            ];
        });

        RateLimiter::for('fleet-otp-verify', function (Request $request) {
            return Limit::perMinute(10)->by($request->ip())->response(
                fn () => response()->json(['message' => 'Too many attempts. Please wait a minute and try again.'], 429)
            );
        });

        RateLimiter::for('fleet-admin-login', function (Request $request) {
            return Limit::perMinute(5)->by(strtolower((string) $request->input('email')).'|'.$request->ip())->response(
                fn () => response()->json(['message' => 'Too many sign-in attempts. Please wait a minute and try again.'], 429)
            );
        });

        Gate::define('manage-content', fn ($user) => $user->canManageContent());
        Gate::define('delete-content', fn ($user) => $user->canDelete());
        Gate::define('manage-users', fn ($user) => $user->isAdmin());

        Event::observe(ContentObserver::class);
        NewsArticle::observe(ContentObserver::class);
        Material::observe(ContentObserver::class);
        HeroSlide::observe(ContentObserver::class);
        PlatformPillar::observe(ContentObserver::class);
        GalleryImage::observe(ContentObserver::class);
    }
}
