<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Merchandise;
use App\Models\MerchandiseImage;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;

class MerchandiseImageController extends Controller
{
    public function store(Request $request, Merchandise $merchandise): RedirectResponse
    {
        Gate::authorize('manage-content');

        $validated = $request->validate([
            'image_path' => ['required', 'image', 'mimes:jpeg,png,jpg,gif,webp', 'max:2048'],
            'image_alt' => ['required', 'string', 'max:200'],
            'is_primary' => ['boolean'],
            'sort_order' => ['required', 'integer'],
        ]);

        if ($request->hasFile('image_path')) {
            $path = $request->file('image_path')->store('merchandise', 'public');
            $validated['image_path'] = $path;
        }

        $validated['merchandise_id'] = $merchandise->id;
        $image = MerchandiseImage::create($validated);

        return back()->with('success', 'Image added successfully.');
    }

    public function destroy(MerchandiseImage $image): RedirectResponse
    {
        Gate::authorize('delete-content');

        if ($image->image_path) {
            Storage::disk('public')->delete($image->image_path);
        }

        $image->delete();

        return back()->with('success', 'Image deleted successfully.');
    }

    public function setPrimary(MerchandiseImage $image): RedirectResponse
    {
        MerchandiseImage::where('merchandise_id', $image->merchandise_id)->update(['is_primary' => false]);
        $image->update(['is_primary' => true]);

        return back()->with('success', 'Primary image updated successfully.');
    }
}
