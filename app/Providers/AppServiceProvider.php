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
        RateLimiter::for('contact', function (Request $request) {
            return Limit::perHour(5)->by($request->ip());
        });

        RateLimiter::for('newsletter', function (Request $request) {
            return Limit::perHour(10)->by($request->ip());
        });

        RateLimiter::for('volunteer', function (Request $request) {
            return Limit::perHour(3)->by($request->ip());
        });

        RateLimiter::for('manifesto-chat', function (Request $request) {
            return Limit::perMinute(10)->by($request->ip());
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
