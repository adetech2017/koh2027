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

        $articles = $query->paginate(9)->withQueryString();
        // Only categories that have published articles, so every filter returns something
        $categories = NewsArticle::published()->reorder()->distinct()->orderBy('category')->pluck('category')->values()->toArray();

        return Inertia::render('News/Index', [
            'articles' => $articles->through(fn ($a) => [...$a->toArray(), 'image_url' => $a->image_url]),
            'filters' => $request->only(['category', 'search']),
            'categories' => $categories,
        ])->withViewData(['meta' => [
            'title' => 'Campaign News — KOH 2027',
            'description' => 'News, press releases and updates from the Kadri Obafemi Hamzat campaign for Lagos State.',
        ]]);
    }

    public function show(Request $request, string $slug): Response
    {
        $article = NewsArticle::published()->where('slug', $slug)->firstOrFail();
        $related = NewsArticle::published()
            ->where('category', $article->category)
            ->where('id', '!=', $article->id)
            ->take(3)
            ->get(['id', 'title', 'slug', 'category', 'excerpt', 'image_path', 'image_alt', 'published_at']);

        return Inertia::render('News/Show', [
            'article' => [...$article->toArray(), 'image_url' => $article->image_url],
            'related' => $related->map(fn ($a) => [...$a->toArray(), 'image_url' => $a->image_url]),
            'shareUrl' => route('news.show', $article->slug),
        ])->withViewData(['meta' => [
            'title' => $article->title,
            'description' => $article->excerpt,
            'image' => $article->image_url,
        ]]);
    }
}
