<?php

namespace App\Http\Controllers;

use App\Models\GalleryImage;
use App\Models\ImageCategory;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class GalleryController extends Controller
{
    public function index(Request $request): Response
    {
        $query = GalleryImage::active()->with('category');

        if ($request->filled('category')) {
            $query->whereHas('category', fn ($q) => $q->where('name', $request->category));
        }

        $images = $query->orderBy('sort_order')->orderBy('id')->paginate(24)->withQueryString();

        // Only categories with visible photos, with their counts
        $categories = ImageCategory::withCount(['galleryImages' => fn ($q) => $q->where('is_active', true)])
            ->orderBy('name')
            ->get()
            ->filter(fn ($c) => $c->gallery_images_count > 0)
            ->map(fn ($c) => ['name' => $c->name, 'count' => $c->gallery_images_count])
            ->values();

        return Inertia::render('Gallery', [
            'images' => $images->through(fn ($img) => [
                'id' => $img->id,
                'title' => $img->title,
                'alt_text' => $img->alt_text,
                'event_label' => $img->event_label,
                'image_url' => $img->image_url,
                'thumbnail_url' => $img->thumbnail_url,
                'category' => $img->category?->name,
            ]),
            'filters' => $request->only(['category']),
            'categories' => $categories,
            'total' => GalleryImage::active()->count(),
        ])->withViewData(['meta' => [
            'title' => 'Campaign Gallery — KOH 2027',
            'description' => 'Photos from rallies, town halls and community visits across Lagos State.',
            'image' => $images->first()['image_url'] ?? null,
        ]]);
    }
}
