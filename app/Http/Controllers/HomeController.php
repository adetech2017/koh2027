<?php

namespace App\Http\Controllers;

use App\Models\CampaignStat;
use App\Models\Event;
use App\Models\GalleryImage;
use App\Models\HeroSlide;
use App\Models\NewsArticle;
use App\Models\PlatformPillar;
use App\Models\Testimonial;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class HomeController extends Controller
{
    public function index(Request $request): Response
    {
        $heroSlides = HeroSlide::active()->get();
        $pillars = PlatformPillar::active()->take(3)->get(['id', 'title', 'slug', 'summary', 'icon', 'color']);
        $stats = CampaignStat::active()->get();
        $events = Event::upcoming()->take(4)->get(['id', 'title', 'slug', 'lga', 'event_type', 'starts_at', 'image_path']);
        $articles = NewsArticle::published()->take(3)->get(['id', 'title', 'slug', 'excerpt', 'image_path', 'published_at']);
        $testimonials = Testimonial::active()->take(6)->get();
        $galleryImages = GalleryImage::active()->where('is_featured', true)->take(8)->get();

        return Inertia::render('Home', [
            'heroSlides' => $heroSlides->map(fn ($slide) => [...$slide->toArray(), 'image_url' => $slide->image_url]),
            'pillars' => $pillars,
            'stats' => $stats,
            'events' => $events->map(fn ($event) => [...$event->toArray(), 'image_url' => $event->image_url]),
            'articles' => $articles->map(fn ($article) => [...$article->toArray(), 'image_url' => $article->image_url]),
            'testimonials' => $testimonials->map(fn ($t) => [...$t->toArray(), 'avatar_url' => $t->avatar_url]),
            'galleryImages' => $galleryImages->map(fn ($img) => [...$img->toArray(), 'image_url' => $img->image_url]),
        ]);
    }
}
