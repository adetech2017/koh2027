<?php

namespace App\Http\Controllers;

use App\Models\GalleryImage;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class GalleryController extends Controller
{
    public function index(Request $request): Response
    {
        $query = GalleryImage::active();

        if ($request->filled('category')) {
            $query->where('category', $request->category);
        }

        $images = $query->orderBy('sort_order')->paginate(24);
        $categories = GalleryImage::distinct('category')->pluck('category');

        return Inertia::render('Gallery', [
            'images' => $images->through(fn ($img) => [...$img->toArray(), 'image_url' => $img->image_url, 'thumbnail_url' => $img->thumbnail_url]),
            'filters' => $request->only(['category']),
            'categories' => $categories,
        ]);
    }
}
