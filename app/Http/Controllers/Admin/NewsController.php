<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\NewsArticle;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class NewsController extends Controller
{
    private const DEFAULT_CATEGORIES = ['announcement', 'update', 'press_release', 'opinion', 'event'];

    public function index(Request $request): Response
    {
        $status = in_array($request->query('status'), ['published', 'scheduled', 'draft']) ? $request->query('status') : null;
        $search = trim((string) $request->query('search'));
        $category = $request->query('category');

        $articles = NewsArticle::query()
            ->when($status, fn ($query) => $this->scopeStatus($query, $status))
            ->when($category, fn ($query) => $query->where('category', $category))
            ->when($search !== '', fn ($query) => $query->where(fn ($q) => $q
                ->where('title', 'like', "%{$search}%")
                ->orWhere('excerpt', 'like', "%{$search}%")))
            // Drafts first (by last edit), then newest publish date
            ->orderByRaw('is_published asc')
            ->orderByDesc('published_at')
            ->orderByDesc('updated_at')
            ->paginate(20, ['id', 'title', 'slug', 'category', 'excerpt', 'image_path', 'image_alt', 'author_name', 'is_featured', 'is_published', 'published_at', 'updated_at'])
            ->withQueryString()
            ->through(fn ($article) => [
                ...$article->only(['id', 'title', 'slug', 'category', 'excerpt', 'image_url', 'author_name', 'is_featured', 'published_at', 'updated_at']),
                'status' => $this->status($article),
            ]);

        $counts = ['all' => NewsArticle::count()];
        foreach (['published', 'scheduled', 'draft'] as $key) {
            $counts[$key] = $this->scopeStatus(NewsArticle::query(), $key)->count();
        }

        return Inertia::render('Admin/News/Index', [
            'articles' => $articles,
            'counts' => $counts,
            'categories' => $this->categories(),
            'filters' => ['status' => $status, 'search' => $search, 'category' => $category],
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Admin/News/Create', [
            'categories' => $this->categories(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        Gate::authorize('manage-content');

        $data = $this->validated($request);

        if ($request->hasFile('image')) {
            $data['image_path'] = $request->file('image')->store('news', 'public');
        }

        $article = NewsArticle::create($data);

        return redirect()->route('admin.news.edit', $article)->with('success', $this->savedMessage($article, 'created'));
    }

    public function edit(NewsArticle $news): Response
    {
        return Inertia::render('Admin/News/Edit', [
            'article' => [...$news->toArray(), 'status' => $this->status($news)],
            'categories' => $this->categories(),
        ]);
    }

    public function update(Request $request, NewsArticle $news): RedirectResponse
    {
        Gate::authorize('manage-content');

        $data = $this->validated($request, $news);

        if ($request->hasFile('image') || $request->boolean('remove_image')) {
            if ($news->image_path) {
                Storage::disk('public')->delete($news->image_path);
            }
            $data['image_path'] = $request->hasFile('image') ? $request->file('image')->store('news', 'public') : null;
        }

        $news->update($data);

        return back()->with('success', $this->savedMessage($news, 'saved'));
    }

    // Quick publish/unpublish from the list
    public function publish(NewsArticle $article): RedirectResponse
    {
        Gate::authorize('manage-content');

        if ($article->is_published) {
            $article->update(['is_published' => false]);

            return back()->with('success', "\"{$article->title}\" moved to drafts.");
        }

        $article->update([
            'is_published' => true,
            'published_at' => $article->published_at && $article->published_at->isPast() ? $article->published_at : now(),
        ]);

        return back()->with('success', "\"{$article->title}\" is now live.");
    }

    public function destroy(NewsArticle $news): RedirectResponse
    {
        Gate::authorize('delete-content');

        if ($news->image_path) {
            Storage::disk('public')->delete($news->image_path);
        }

        $news->delete();

        return redirect()->route('admin.news.index')->with('success', 'Article deleted.');
    }

    private function validated(Request $request, ?NewsArticle $article = null): array
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:200'],
            'category' => ['required', 'string', 'max:100'],
            'excerpt' => ['nullable', 'string', 'max:300'],
            'body' => ['required', 'string', function ($attribute, $value, $fail) {
                if (trim(strip_tags($value)) === '') {
                    $fail('The article needs some content.');
                }
            }],
            'author_name' => ['nullable', 'string', 'max:100'],
            'image' => ['nullable', 'image', 'mimes:jpeg,png,jpg,gif,webp', 'max:2048'],
            'image_alt' => ['nullable', 'string', 'max:200'],
            'is_featured' => ['boolean'],
            'status' => ['required', 'in:draft,published,scheduled'],
            'published_at' => ['nullable', 'required_if:status,scheduled', 'date_format:Y-m-d H:i'],
        ], [
            'published_at.required_if' => 'Choose when the article should go live.',
        ]);

        // Keep categories consistent ("Press Release" -> "press_release") so public filters group them
        $category = str($validated['category'])->trim()->lower()->replaceMatches('/[^a-z0-9]+/', '_')->trim('_')->toString();

        $publishedAt = $validated['published_at'] ? Carbon::createFromFormat('Y-m-d H:i', $validated['published_at'], 'UTC') : null;
        if ($validated['status'] === 'scheduled' && $publishedAt && $publishedAt->isPast()) {
            // A past "scheduled" time just means publish now
            $validated['status'] = 'published';
        }

        return [
            'title' => $validated['title'],
            'category' => $category ?: 'update',
            // excerpt is NOT NULL and shown on cards/search; derive it from the body when left empty
            'excerpt' => filled($validated['excerpt'])
                ? $validated['excerpt']
                : str(html_entity_decode(strip_tags($validated['body'])))->squish()->limit(200)->toString(),
            'body' => $validated['body'],
            'author_name' => filled($validated['author_name']) ? $validated['author_name'] : 'KOH Campaign Team',
            'image_alt' => $validated['image_alt'] ?: null,
            'is_featured' => $request->boolean('is_featured'),
            'is_published' => $validated['status'] !== 'draft',
            'published_at' => match ($validated['status']) {
                'scheduled' => $publishedAt,
                'published' => $publishedAt && $publishedAt->isPast()
                    ? $publishedAt
                    : ($article?->published_at && $article->published_at->isPast() ? $article->published_at : now()),
                default => $article?->published_at,
            },
        ];
    }

    private function status(NewsArticle $article): string
    {
        if (!$article->is_published) {
            return 'draft';
        }

        return $article->published_at && $article->published_at->isFuture() ? 'scheduled' : 'published';
    }

    private function scopeStatus($query, string $status)
    {
        return match ($status) {
            'draft' => $query->where('is_published', false),
            'scheduled' => $query->where('is_published', true)->where('published_at', '>', now()),
            'published' => $query->where('is_published', true)->where(fn ($q) => $q->whereNull('published_at')->orWhere('published_at', '<=', now())),
        };
    }

    private function categories(): array
    {
        return collect(self::DEFAULT_CATEGORIES)
            ->merge(NewsArticle::distinct()->pluck('category'))
            ->filter()
            ->unique()
            ->sort()
            ->values()
            ->all();
    }

    private function savedMessage(NewsArticle $article, string $verb): string
    {
        return match ($this->status($article)) {
            'draft' => "Draft {$verb}.",
            // The server clock is UTC, so the exact time is left to the page, which shows it in local time
            'scheduled' => "Article {$verb} and scheduled.",
            default => "Article {$verb} and live on the site.",
        };
    }
}
