<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ImageCategory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

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
}
