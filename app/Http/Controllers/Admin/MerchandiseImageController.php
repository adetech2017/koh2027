<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Merchandise;
use App\Models\MerchandiseImage;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class MerchandiseImageController extends Controller
{
    public function store(Request $request, Merchandise $merchandise): RedirectResponse
    {
        Gate::authorize('manage-content');

        $request->validate([
            'images' => ['required', 'array', 'min:1', 'max:10'],
            'images.*' => ['image', 'mimes:jpeg,png,jpg,gif,webp', 'max:2048'],
        ], [
            'images.*.max' => 'Each photo must be 2 MB or smaller.',
            'images.*.mimes' => 'Photos must be JPG, PNG, GIF or WebP.',
            'images.max' => 'Upload up to 10 photos at a time.',
        ]);

        $nextSort = ($merchandise->images()->max('sort_order') ?? -1) + 1;
        $hasPrimary = $merchandise->images()->where('is_primary', true)->exists();

        foreach ($request->file('images') as $offset => $file) {
            MerchandiseImage::create([
                'merchandise_id' => $merchandise->id,
                'image_path' => $file->store('merchandise', 'public'),
                // image_alt is NOT NULL; the product name is a sensible default the admin can refine
                'image_alt' => $merchandise->name,
                // The first photo of a product becomes its shop thumbnail
                'is_primary' => !$hasPrimary && $offset === 0,
                'sort_order' => $nextSort + $offset,
            ]);
        }

        $count = count($request->file('images'));

        return back()->with('success', "Added {$count} photo".($count !== 1 ? 's' : '').'.');
    }

    public function update(Request $request, MerchandiseImage $image): RedirectResponse
    {
        Gate::authorize('manage-content');

        $validated = $request->validate([
            'image_alt' => ['required', 'string', 'max:200'],
        ]);

        $image->update($validated);

        return back()->with('success', 'Photo description saved.');
    }

    public function destroy(MerchandiseImage $image): RedirectResponse
    {
        Gate::authorize('manage-content');

        if ($image->image_path) {
            Storage::disk('public')->delete($image->image_path);
        }

        $wasPrimary = $image->is_primary;
        $productId = $image->merchandise_id;
        $image->delete();

        // Keep a thumbnail in the shop by promoting the next photo
        if ($wasPrimary) {
            MerchandiseImage::where('merchandise_id', $productId)->orderBy('sort_order')->first()?->update(['is_primary' => true]);
        }

        return back()->with('success', 'Photo removed.');
    }

    public function setPrimary(MerchandiseImage $image): RedirectResponse
    {
        Gate::authorize('manage-content');

        DB::transaction(function () use ($image) {
            MerchandiseImage::where('merchandise_id', $image->merchandise_id)->update(['is_primary' => false]);
            $image->update(['is_primary' => true]);
        });

        return back()->with('success', 'Shop thumbnail updated.');
    }

    public function reorder(Request $request, Merchandise $merchandise): RedirectResponse
    {
        Gate::authorize('manage-content');

        $validated = $request->validate([
            'ids' => ['required', 'array'],
            'ids.*' => ['integer', Rule::exists('merchandise_images', 'id')->where('merchandise_id', $merchandise->id)],
        ]);

        DB::transaction(function () use ($validated) {
            foreach (array_values($validated['ids']) as $position => $id) {
                MerchandiseImage::whereKey($id)->update(['sort_order' => $position]);
            }
        });

        return back();
    }
}
