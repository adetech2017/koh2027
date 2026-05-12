<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\GalleryImage;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class GalleryController extends Controller
{
    public function index(): Response
    {
        $images = GalleryImage::orderBy('sort_order')->paginate(24);

        return Inertia::render('Admin/Gallery/Index', [
            'images' => $images,
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Admin/Gallery/Create');
    }

    public function store(Request $request): RedirectResponse
    {
        Gate::authorize('manage-content');

        $validated = $request->validate([
            'images' => ['required', 'array', 'min:1'],
            'images.*' => ['image', 'mimes:jpeg,png,jpg,gif,webp', 'max:2048'],
            'category' => ['required', 'string', 'max:100'],
        ]);

        $uploadedCount = 0;
        if ($request->hasFile('images')) {
            foreach ($request->file('images') as $index => $file) {
                $path = $file->store('gallery', 'public');
                GalleryImage::create([
                    'title' => $file->getClientOriginalName(),
                    'alt_text' => $file->getClientOriginalName(),
                    'image_path' => $path,
                    'category' => $validated['category'],
                    'sort_order' => $index,
                ]);
                $uploadedCount++;
            }
        }

        return redirect()->route('admin.gallery.index')->with('success', "Successfully uploaded $uploadedCount image" . ($uploadedCount !== 1 ? 's' : '') . '.');
    }

    public function edit(GalleryImage $gallery): Response
    {
        $galleryImages = GalleryImage::where('category', $gallery->category)->orderBy('sort_order')->get();

        return Inertia::render('Admin/Gallery/Edit', [
            'image' => [...$gallery->toArray(), 'image_url' => $gallery->image_url],
            'galleryImages' => $galleryImages,
        ]);
    }

    public function update(Request $request, GalleryImage $gallery): RedirectResponse
    {
        Gate::authorize('manage-content');

        $validated = $request->validate([
            'images' => ['nullable', 'array'],
            'images.*' => ['image', 'mimes:jpeg,png,jpg,gif,webp', 'max:2048'],
            'category' => ['required', 'string', 'max:100'],
        ]);

        $gallery->update(['category' => $validated['category']]);

        if ($request->hasFile('images')) {
            $maxSort = GalleryImage::where('category', $validated['category'])->max('sort_order') ?? 0;
            foreach ($request->file('images') as $index => $file) {
                $path = $file->store('gallery', 'public');
                GalleryImage::create([
                    'title' => $file->getClientOriginalName(),
                    'alt_text' => $file->getClientOriginalName(),
                    'image_path' => $path,
                    'category' => $validated['category'],
                    'sort_order' => $maxSort + $index + 1,
                ]);
            }
        }

        return redirect()->route('admin.gallery.index')->with('success', 'Gallery updated successfully.');
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
