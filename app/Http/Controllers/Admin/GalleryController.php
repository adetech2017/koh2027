<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\GalleryImage;
use App\Models\ImageCategory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class GalleryController extends Controller
{
    public function index(): Response
    {
        $categories = ImageCategory::with(['galleryImages' => fn ($query) => $query->orderBy('sort_order')])
            ->orderBy('name')
            ->get();

        return Inertia::render('Admin/Gallery/Index', [
            'categories' => $categories,
        ]);
    }

    public function create(Request $request): Response
    {
        return Inertia::render('Admin/Gallery/Create', [
            'categories' => $this->categoryList(),
            'selectedCategoryId' => $request->integer('category_id') ?: null,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        Gate::authorize('manage-content');

        $validated = $request->validate([
            'images' => ['required', 'array', 'min:1'],
            'images.*' => ['image', 'mimes:jpeg,png,jpg,gif,webp', 'max:2048'],
            'category_id' => ['required', 'integer', 'exists:image_categories,id'],
        ]);

        $uploadedCount = 0;
        if ($request->hasFile('images')) {
            foreach ($request->file('images') as $index => $file) {
                $path = $file->store('gallery', 'public');
                GalleryImage::create([
                    'title' => $file->getClientOriginalName(),
                    'alt_text' => $file->getClientOriginalName(),
                    'image_path' => $path,
                    'category_id' => $validated['category_id'],
                    'sort_order' => $index,
                ]);
                $uploadedCount++;
            }
        }

        return redirect()->route('admin.gallery.index')->with('success', "Successfully uploaded $uploadedCount image" . ($uploadedCount !== 1 ? 's' : '') . '.');
    }

    public function edit(GalleryImage $gallery): Response
    {
        $gallery->load('category');

        $galleryImages = GalleryImage::where('category_id', $gallery->category_id)->orderBy('sort_order')->get();

        return Inertia::render('Admin/Gallery/Edit', [
            'image' => [...$gallery->toArray(), 'image_url' => $gallery->image_url],
            'galleryImages' => $galleryImages,
            'categories' => $this->categoryList(),
        ]);
    }

    private function categoryList()
    {
        return ImageCategory::withCount('galleryImages')->orderBy('name')->get();
    }

    public function update(Request $request, GalleryImage $gallery): RedirectResponse
    {
        Gate::authorize('manage-content');

        $validated = $request->validate([
            'images' => ['nullable', 'array'],
            'images.*' => ['image', 'mimes:jpeg,png,jpg,gif,webp', 'max:2048'],
            'category_id' => ['required', 'integer', 'exists:image_categories,id'],
        ]);

        $gallery->update(['category_id' => $validated['category_id']]);

        if ($request->hasFile('images')) {
            $maxSort = GalleryImage::where('category_id', $validated['category_id'])->max('sort_order') ?? 0;
            foreach ($request->file('images') as $index => $file) {
                $path = $file->store('gallery', 'public');
                GalleryImage::create([
                    'title' => $file->getClientOriginalName(),
                    'alt_text' => $file->getClientOriginalName(),
                    'image_path' => $path,
                    'category_id' => $validated['category_id'],
                    'sort_order' => $maxSort + $index + 1,
                ]);
            }
        }

        return redirect()->route('admin.gallery.index')->with('success', 'Gallery updated successfully.');
    }

    public function toggleFeatured(GalleryImage $gallery): RedirectResponse
    {
        Gate::authorize('manage-content');

        $gallery->update(['is_featured' => !$gallery->is_featured]);

        return back();
    }

    public function destroy(GalleryImage $gallery): RedirectResponse
    {
        Gate::authorize('delete-content');

        if ($gallery->image_path) {
            \Illuminate\Support\Facades\Storage::disk('public')->delete($gallery->image_path);
        }

        if ($gallery->thumbnail_path) {
            \Illuminate\Support\Facades\Storage::disk('public')->delete($gallery->thumbnail_path);
        }

        $gallery->delete();

        return redirect()->route('admin.gallery.index')->with('success', 'Gallery image deleted successfully.');
    }
}
