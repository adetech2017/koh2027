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

        $images = $query->orderBy('sort_order')->paginate(24);
        $categories = ImageCategory::orderBy('name')->pluck('name');

        return Inertia::render('Gallery', [
            'images' => $images->through(fn ($img) => [
                'id' => $img->id,
                'alt_text' => $img->alt_text,
                'image_url' => $img->image_url,
                'thumbnail_url' => $img->thumbnail_url,
                'category' => $img->category?->name,
            ]),
            'filters' => $request->only(['category']),
            'categories' => $categories,
        ]);
    }
}
