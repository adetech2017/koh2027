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
        $categories = Merchandise::distinct()->pluck('category')->sort()->values()->toArray();

        return Inertia::render('Merchandise/Index', [
            'products' => $products->map(fn ($p) => [...$p->toArray(), 'primary_image_url' => $p->primaryImage?->image_url]),
            'filters' => $request->only(['category']),
            'categories' => $categories,
        ]);
    }

    public function show(Request $request, string $slug): Response
    {
        $product = Merchandise::active()->with('images')->where('slug', $slug)->firstOrFail();
        $related = Merchandise::active()->with('primaryImage')
            ->where('category', $product->category)
            ->where('id', '!=', $product->id)
            ->take(4)
            ->get();

        return Inertia::render('Merchandise/Show', [
            'product' => [...$product->toArray(), 'images_urls' => $product->images->map(fn ($img) => ['url' => $img->image_url, 'alt' => $img->image_alt])],
            'related' => $related->map(fn ($p) => [...$p->toArray(), 'primary_image_url' => $p->primaryImage?->image_url]),
        ]);
    }
}
