<?php

namespace App\Http\Controllers;

use App\Models\NewsArticle;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class NewsController extends Controller
{
    public function index(Request $request): Response
    {
        $query = NewsArticle::published();

        if ($request->filled('category')) {
            $query->where('category', $request->category);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%$search%")
                    ->orWhere('excerpt', 'like', "%$search%");
            });
        }

        $articles = $query->paginate(9);
        $categories = NewsArticle::distinct()->pluck('category')->sort()->values()->toArray();

        return Inertia::render('News/Index', [
            'articles' => $articles->through(fn ($a) => [...$a->toArray(), 'image_url' => $a->image_url]),
            'filters' => $request->only(['category', 'search']),
            'categories' => $categories,
        ]);
    }

    public function show(Request $request, string $slug): Response
    {
        $article = NewsArticle::published()->where('slug', $slug)->firstOrFail();
        $related = NewsArticle::published()
            ->where('category', $article->category)
            ->where('id', '!=', $article->id)
            ->take(3)
            ->get(['id', 'title', 'slug', 'excerpt', 'image_path', 'published_at']);

        return Inertia::render('News/Show', [
            'article' => [...$article->toArray(), 'image_url' => $article->image_url],
            'related' => $related->map(fn ($a) => [...$a->toArray(), 'image_url' => $a->image_url]),
            'meta' => [
                'title' => $article->title,
                'description' => $article->excerpt,
                'image' => $article->image_url,
                'url' => request()->url(),
            ],
        ]);
    }
}
