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
        ]);

        $category = ImageCategory::create($validated);

        return response()->json($category);
    }

    public function update(Request $request, ImageCategory $category): RedirectResponse
    {
        Gate::authorize('manage-content');

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100', Rule::unique('image_categories', 'name')->ignore($category->id)],
        ]);

        $category->update($validated);

        return back()->with('success', 'Category renamed.');
    }

    public function destroy(ImageCategory $category): RedirectResponse
    {
        Gate::authorize('delete-content');

        // gallery_images.category_id is nullOnDelete, so its images move to "Uncategorized"
        $moved = $category->galleryImages()->count();
        $category->delete();

        return redirect()->route('admin.gallery.index', $moved ? ['category' => 'uncategorized'] : [])
            ->with('success', $moved ? "Category deleted. Its {$moved} images are now uncategorized." : 'Category deleted.');
    }
}
