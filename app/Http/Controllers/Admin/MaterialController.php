<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Material;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class MaterialController extends Controller
{
    public function index(): Response
    {
        $materials = Material::orderBy('created_at', 'desc')->paginate(20);

        return Inertia::render('Admin/Materials/Index', [
            'materials' => $materials,
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Admin/Materials/Create');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:200'],
            'description' => ['required', 'string'],
            'category' => ['required', 'string', 'max:100'],
            'file_path' => ['required', 'string'],
            'file_name' => ['required', 'string', 'max:200'],
            'file_type' => ['required', 'string', 'max:20'],
            'file_size' => ['required', 'integer'],
            'thumbnail_path' => ['nullable', 'string'],
            'is_active' => ['boolean'],
        ]);

        Material::create($validated);

        return redirect()->route('admin.materials.index')->with('success', 'Material created successfully.');
    }

    public function edit(Material $material): Response
    {
        return Inertia::render('Admin/Materials/Edit', [
            'material' => $material,
        ]);
    }

    public function update(Request $request, Material $material): RedirectResponse
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:200'],
            'description' => ['required', 'string'],
            'category' => ['required', 'string', 'max:100'],
            'file_path' => ['required', 'string'],
            'file_name' => ['required', 'string', 'max:200'],
            'file_type' => ['required', 'string', 'max:20'],
            'file_size' => ['required', 'integer'],
            'thumbnail_path' => ['nullable', 'string'],
            'is_active' => ['boolean'],
        ]);

        $material->update($validated);

        return redirect()->route('admin.materials.index')->with('success', 'Material updated successfully.');
    }

    public function destroy(Material $material): RedirectResponse
    {
        Gate::authorize('delete-content');
        $material->delete();

        return redirect()->route('admin.materials.index')->with('success', 'Material deleted successfully.');
    }
}
