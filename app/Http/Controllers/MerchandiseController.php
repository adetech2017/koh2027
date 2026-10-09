<?php

namespace App\Http\Controllers;

use App\Models\Merchandise;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class MerchandiseController extends Controller
{
    public function index(Request $request): Response
    {
        $query = Merchandise::active()->with('primaryImage');

        if ($request->filled('category')) {
            $query->where('category', $request->category);
        }

        $products = $query->get();
        // Only categories that have products in the shop
        $categories = Merchandise::where('is_active', true)->distinct()->orderBy('category')->pluck('category')->values()->toArray();

        return Inertia::render('Merchandise/Index', [
            'products' => $products->map(fn ($p) => [...$p->toArray(), 'primary_image_url' => $p->primaryImage?->image_url]),
            'filters' => $request->only(['category']),
            'categories' => $categories,
        ])->withViewData(['meta' => [
            'title' => 'Merchandise Designs — KOH 2027',
            'description' => 'Official KOH 2027 campaign merchandise designs for support groups across Lagos.',
        ]]);
    }

    public function show(Request $request, string $slug): Response
    {
        $product = Merchandise::active()->with('images')->where('slug', $slug)->firstOrFail();
        $related = Merchandise::active()->with('primaryImage')
            ->where('category', $product->category)
            ->where('id', '!=', $product->id)
            ->take(4)
            ->get();

        // The thumbnail first, then the rest in order
        $images = $product->images->sortByDesc('is_primary')->values()
            ->map(fn ($img) => ['url' => $img->image_url, 'alt' => $img->image_alt]);

        return Inertia::render('Merchandise/Show', [
            // Previously only images_urls was sent, so the main photo (primary_image_url) never showed
            'product' => [...$product->toArray(), 'images_urls' => $images, 'primary_image_url' => $images->first()['url'] ?? null],
            'related' => $related->map(fn ($p) => [...$p->toArray(), 'primary_image_url' => $p->primaryImage?->image_url]),
        ])->withViewData(['meta' => [
            'title' => $product->name.' — KOH 2027 Merchandise Designs',
            'description' => \Illuminate\Support\Str::limit(\Illuminate\Support\Str::squish($product->description), 200),
            'image' => $images->first()['url'] ?? null,
        ]]);
    }
}
