<?php

namespace App\Http\Controllers;

use App\Models\GalleryImage;
use App\Models\ImageCategory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class GalleryController extends Controller
{
    // Album cards plus every photo, newest first
    public function index(Request $request): Response|RedirectResponse
    {
        // Old links filtered by category name (?category=Events); send them to that album
        if ($request->filled('category')) {
            $album = ImageCategory::where('name', $request->query('category'))->first();

            return $album ? redirect()->route('gallery.album', $album->slug, 301) : redirect()->route('gallery');
        }

        $images = GalleryImage::active()->with('category')->newestFirst()->paginate(24)->withQueryString();

        return Inertia::render('Gallery', [
            'albums' => $this->albumCards(),
            'images' => $images->through(fn ($img) => $img->toPublicArray()),
            'album' => null,
        ])->withViewData(['meta' => [
            'title' => 'Campaign Gallery — KOH 2027',
            'description' => 'Photos from rallies, town halls and community visits across Lagos State.',
            'image' => $images->first()['image_url'] ?? null,
        ]]);
    }

    public function album(ImageCategory $album): Response
    {
        $images = $album->galleryImages()->active()->with('category')->newestFirst()->paginate(48)->withQueryString();
        abort_if($images->total() === 0, 404);

        return Inertia::render('Gallery', [
            'albums' => $this->albumCards(),
            'images' => $images->through(fn ($img) => $img->toPublicArray()),
            'album' => [
                'name' => $album->name,
                'slug' => $album->slug,
                'description' => $album->description,
                'event_date' => $album->event_date?->toDateString(),
                'count' => $images->total(),
            ],
        ])->withViewData(['meta' => [
            'title' => "{$album->name} — KOH 2027 Gallery",
            'description' => $album->description ?: "Photos from {$album->name}.",
            'image' => $images->first()['image_url'] ?? null,
        ]]);
    }

    private function albumCards()
    {
        $albums = ImageCategory::forPublic()->get();

        // Cover = the album's newest visible photo
        $covers = GalleryImage::active()
            ->whereIn('category_id', $albums->pluck('id'))
            ->newestFirst()
            ->get(['id', 'category_id', 'image_path', 'thumbnail_path'])
            ->unique('category_id')
            ->keyBy('category_id');

        return $albums->map(fn ($album) => [
            'name' => $album->name,
            'slug' => $album->slug,
            'event_date' => $album->event_date?->toDateString(),
            'count' => $album->gallery_images_count,
            'cover_url' => $covers->get($album->id)?->thumbnail_url ?? $covers->get($album->id)?->image_url,
        ])->values();
    }
}
