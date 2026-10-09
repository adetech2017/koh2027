<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\GalleryImage;
use App\Models\ImageCategory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class GalleryController extends Controller
{
    // HomeController shows this many featured images
    public const HOMEPAGE_SLOTS = 8;

    public function index(Request $request): Response
    {
        $categories = ImageCategory::withCount('galleryImages')
            ->withCount(['galleryImages as featured_count' => fn ($query) => $query->where('is_featured', true)])
            ->with(['galleryImages' => fn ($query) => $query->orderBy('sort_order')->orderBy('id')->limit(1)])
            ->orderBy('name')
            ->get()
            ->map(fn ($category) => [
                'id' => $category->id,
                'name' => $category->name,
                'gallery_images_count' => $category->gallery_images_count,
                'featured_count' => $category->featured_count,
                'cover_url' => $category->galleryImages->first()?->image_url,
            ]);

        // ?category=<id> or ?category=uncategorized opens that category's images
        $selected = $request->query('category');
        $images = null;
        if ($selected === 'uncategorized' || ($selected && $categories->contains('id', (int) $selected))) {
            $images = GalleryImage::query()
                ->when($selected === 'uncategorized', fn ($q) => $q->whereNull('category_id'), fn ($q) => $q->where('category_id', (int) $selected))
                ->orderBy('sort_order')
                ->orderBy('id')
                ->paginate(60)
                ->withQueryString()
                ->through(fn ($image) => $this->present($image));
        } else {
            $selected = null;
        }

        return Inertia::render('Admin/Gallery/Index', [
            'categories' => $categories,
            'uncategorizedCount' => GalleryImage::whereNull('category_id')->count(),
            'featuredCount' => GalleryImage::where('is_featured', true)->where('is_active', true)->count(),
            'homepageSlots' => self::HOMEPAGE_SLOTS,
            'selectedCategory' => $selected,
            'images' => $images,
            'maxUploadBytes' => $this->maxUploadBytes(),
            'maxUploadFiles' => (int) (ini_get('max_file_uploads') ?: 20),
        ]);
    }

    public function create(Request $request): Response
    {
        return Inertia::render('Admin/Gallery/Create', [
            'categories' => $this->categoryList(),
            'selectedCategoryId' => $request->integer('category_id') ?: null,
            'maxUploadBytes' => $this->maxUploadBytes(),
            'maxUploadFiles' => (int) (ini_get('max_file_uploads') ?: 20),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        Gate::authorize('manage-content');

        $validated = $request->validate([
            'images' => ['required', 'array', 'min:1'],
            'images.*' => ['image', 'mimes:jpeg,png,jpg,gif,webp', 'max:2048'],
            'category_id' => ['required', 'integer', 'exists:image_categories,id'],
        ], [
            'images.*.max' => 'Each image must be 2 MB or smaller.',
            'images.*.mimes' => 'Images must be JPG, PNG, GIF or WebP.',
        ]);

        // Append after the category's existing images instead of restarting at 0
        $nextSort = (GalleryImage::where('category_id', $validated['category_id'])->max('sort_order') ?? -1) + 1;

        foreach ($request->file('images') as $offset => $file) {
            GalleryImage::create([
                'title' => null,
                // alt_text is NOT NULL; left empty so the admin is prompted to describe the photo
                'alt_text' => '',
                'image_path' => $file->store('gallery', 'public'),
                'category_id' => $validated['category_id'],
                'sort_order' => $nextSort + $offset,
            ]);
        }

        $count = count($request->file('images'));

        // The uploader sends big selections in batches; only the last one navigates away
        if ($request->boolean('stay')) {
            return back();
        }

        return redirect()
            ->route('admin.gallery.index', ['category' => $validated['category_id']])
            ->with('success', "Uploaded {$count} image".($count !== 1 ? 's' : '').'.');
    }

    public function edit(GalleryImage $gallery): Response
    {
        $siblings = GalleryImage::query()
            ->when($gallery->category_id, fn ($q) => $q->where('category_id', $gallery->category_id), fn ($q) => $q->whereNull('category_id'))
            ->orderBy('sort_order')
            ->orderBy('id')
            ->pluck('id');
        $position = $siblings->search($gallery->id);

        return Inertia::render('Admin/Gallery/Edit', [
            'image' => $this->present($gallery->load('category:id,name')),
            'categories' => $this->categoryList(),
            'prevId' => $position > 0 ? $siblings[$position - 1] : null,
            'nextId' => $siblings[$position + 1] ?? null,
            'position' => $position + 1,
            'siblingCount' => $siblings->count(),
        ]);
    }

    public function update(Request $request, GalleryImage $gallery): RedirectResponse
    {
        Gate::authorize('manage-content');

        $validated = $request->validate([
            'title' => ['nullable', 'string', 'max:200'],
            'alt_text' => ['required', 'string', 'max:255'],
            'event_label' => ['nullable', 'string', 'max:100'],
            'taken_on' => ['nullable', 'date', 'before_or_equal:today'],
            'category_id' => ['nullable', 'integer', 'exists:image_categories,id'],
            'is_active' => ['boolean'],
            'is_featured' => ['boolean'],
        ], [
            'alt_text.required' => 'Describe the photo so screen-reader users know what it shows.',
        ]);
        $validated['is_active'] = $request->boolean('is_active');
        $validated['is_featured'] = $request->boolean('is_featured');

        $validated['category_id'] = isset($validated['category_id']) ? (int) $validated['category_id'] : null;
        if ($validated['category_id'] !== $gallery->category_id) {
            $validated['sort_order'] = (GalleryImage::where('category_id', $validated['category_id'])->max('sort_order') ?? -1) + 1;
        }

        $gallery->update($validated);

        return $request->boolean('stay')
            ? back()->with('success', 'Image details saved.')
            : redirect()->route('admin.gallery.index', ['category' => $gallery->category_id ?? 'uncategorized'])
                ->with('success', 'Image details saved.');
    }

    public function toggleFeatured(GalleryImage $gallery): RedirectResponse
    {
        Gate::authorize('manage-content');

        $gallery->update(['is_featured' => !$gallery->is_featured]);

        if (!$gallery->is_featured) {
            return back()->with('success', 'Removed from the homepage.');
        }

        $featured = GalleryImage::where('is_featured', true)->where('is_active', true)->count();
        $message = $featured > self::HOMEPAGE_SLOTS
            ? "Featured. Note: {$featured} images are featured but the homepage only shows ".self::HOMEPAGE_SLOTS.'.'
            : "Featured on the homepage ({$featured} of ".self::HOMEPAGE_SLOTS.').';

        return back()->with('success', $message);
    }

    public function toggleActive(GalleryImage $gallery): RedirectResponse
    {
        Gate::authorize('manage-content');

        $gallery->update(['is_active' => !$gallery->is_active]);

        return back()->with('success', $gallery->is_active ? 'Image is visible in the public gallery.' : 'Image hidden from the public gallery.');
    }

    public function destroy(GalleryImage $gallery): RedirectResponse
    {
        Gate::authorize('delete-content');

        $categoryId = $gallery->category_id;

        if ($gallery->image_path) {
            Storage::disk('public')->delete($gallery->image_path);
        }
        if ($gallery->thumbnail_path) {
            Storage::disk('public')->delete($gallery->thumbnail_path);
        }

        $gallery->delete();

        // Return to the category rather than the overview, so deleting several in a row is quick
        return redirect()
            ->route('admin.gallery.index', ['category' => $categoryId ?? 'uncategorized'])
            ->with('success', 'Image deleted.');
    }

    private function present(GalleryImage $image): array
    {
        return [
            ...$image->only(['id', 'title', 'alt_text', 'event_label', 'category_id', 'is_active', 'is_featured', 'sort_order']),
            'taken_on' => $image->taken_on?->toDateString(),
            'image_url' => $image->image_url,
            'category' => $image->relationLoaded('category') ? $image->category?->only(['id', 'name']) : null,
            // Older uploads used the filename as alt text, which says nothing about the photo
            'needs_description' => blank($image->alt_text) || preg_match('/\.(jpe?g|png|gif|webp)$/i', $image->alt_text) === 1,
        ];
    }

    private function categoryList()
    {
        return ImageCategory::withCount('galleryImages')->orderBy('name')->get(['id', 'name']);
    }

    // The smallest of PHP's request and per-file limits, so the uploader can split large batches
    private function maxUploadBytes(): int
    {
        $toBytes = function (string $value): int {
            $value = trim($value);
            $number = (int) $value;

            return match (strtolower(substr($value, -1))) {
                'g' => $number * 1024 ** 3,
                'm' => $number * 1024 ** 2,
                'k' => $number * 1024,
                default => $number,
            };
        };

        return min($toBytes(ini_get('post_max_size') ?: '8M'), 64 * 1024 ** 2);
    }
}
