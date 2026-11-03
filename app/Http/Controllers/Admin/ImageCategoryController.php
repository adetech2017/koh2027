<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ImageCategory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class ImageCategoryController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        Gate::authorize('manage-content');

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100', 'unique:image_categories,name'],
            'event_date' => ['nullable', 'date'],
        ], [
            'name.unique' => 'An album with this name already exists.',
        ]);

        $category = ImageCategory::create($validated);

        return response()->json($category);
    }

    public function update(Request $request, ImageCategory $category): RedirectResponse
    {
        Gate::authorize('manage-content');

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100', Rule::unique('image_categories', 'name')->ignore($category->id)],
            'event_date' => ['nullable', 'date'],
            'description' => ['nullable', 'string', 'max:1000'],
        ], [
            'name.unique' => 'An album with this name already exists.',
        ]);

        $category->update($validated);

        return back()->with('success', 'Album saved.');
    }

    public function destroy(ImageCategory $category): RedirectResponse
    {
        Gate::authorize('delete-content');

        // gallery_images.category_id is nullOnDelete, so its images move to "No album"
        $moved = $category->galleryImages()->count();
        $category->delete();

        return redirect()->route('admin.gallery.index', $moved ? ['category' => 'uncategorized'] : [])
            ->with('success', $moved ? "Album deleted. Its {$moved} photos are now in \"No album\"." : 'Album deleted.');
    }
}
