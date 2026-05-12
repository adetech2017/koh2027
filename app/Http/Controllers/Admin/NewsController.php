<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\NewsArticle;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class NewsController extends Controller
{
    public function index(): Response
    {
        $articles = NewsArticle::orderBy('created_at', 'desc')->paginate(20);
        return Inertia::render('Admin/News/Index', ['articles' => $articles]);
    }

    public function create(): Response
    {
        return Inertia::render('Admin/News/Create');
    }

    public function store(Request $request): RedirectResponse
    {
        Gate::authorize('manage-content');

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:200'],
            'category' => ['required', 'string', 'max:100'],
            'body' => ['required', 'string'],
            'image_path' => ['required', 'image', 'mimes:jpeg,png,jpg,gif,webp', 'max:2048'],
        ]);

        if ($request->hasFile('image_path')) {
            $path = $request->file('image_path')->store('news', 'public');
            $validated['image_path'] = $path;
            $validated['image_alt'] = $validated['title'];
        }

        NewsArticle::create($validated);

        return redirect()->route('admin.news.index')->with('success', 'News article created successfully.');
    }

    public function edit(NewsArticle $news): Response
    {
        return Inertia::render('Admin/News/Edit', [
            'article' => array_merge($news->toArray(), ['image_url' => $news->image_url]),
        ]);
    }

    public function update(Request $request, NewsArticle $news): RedirectResponse
    {
        Gate::authorize('manage-content');

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:200'],
            'category' => ['required', 'string', 'max:100'],
            'body' => ['required', 'string'],
            'image_path' => ['nullable', 'image', 'mimes:jpeg,png,jpg,gif,webp', 'max:2048'],
        ]);

        if ($request->hasFile('image_path')) {
            if ($news->image_path) {
                \Illuminate\Support\Facades\Storage::disk('public')->delete($news->image_path);
            }
            $path = $request->file('image_path')->store('news', 'public');
            $validated['image_path'] = $path;
            $validated['image_alt'] = $validated['title'];
        }

        $news->update($validated);

        return redirect()->route('admin.news.index')->with('success', 'News article updated successfully.');
    }

    public function destroy(NewsArticle $news): RedirectResponse
    {
        Gate::authorize('delete-content');

        if ($news->image_path) {
            \Illuminate\Support\Facades\Storage::disk('public')->delete($news->image_path);
        }

        $news->delete();

        return redirect()->route('admin.news.index')->with('success', 'News article deleted successfully.');
    }
}
