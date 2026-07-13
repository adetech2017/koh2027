<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Material;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class MaterialController extends Controller
{
    public function index(): Response
    {
        $materials = Material::orderBy('created_at', 'desc')->paginate(20)->through(function ($material) {
            return $material->append('thumbnail_url');
        });

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
        Gate::authorize('manage-content');

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:200'],
            'description' => ['required', 'string'],
            'category' => ['required', 'string', 'max:100'],
            'file_path' => ['required', 'file', 'mimes:pdf,doc,docx,ppt,pptx,xls,xlsx,zip', 'max:102400'],
            'thumbnail_path' => ['nullable', 'image', 'mimes:jpeg,png,jpg,gif,webp', 'max:2048'],
            'is_active' => ['boolean'],
        ]);

        $file = $request->file('file_path');
        $validated['file_path'] = $file->store('materials', 'local');
        $validated['file_name'] = $file->getClientOriginalName();
        $validated['file_type'] = $file->getClientOriginalExtension();
        $validated['file_size'] = $file->getSize();

        if ($request->hasFile('thumbnail_path')) {
            $validated['thumbnail_path'] = $request->file('thumbnail_path')->store('materials/thumbnails', 'public');
        } else {
            unset($validated['thumbnail_path']);
        }

        Material::create($validated);

        return redirect()->route('admin.materials.index')->with('success', 'Material created successfully.');
    }

    public function edit(Material $material): Response
    {
        return Inertia::render('Admin/Materials/Edit', [
            'material' => [...$material->toArray(), 'thumbnail_url' => $material->thumbnail_url],
        ]);
    }

    public function update(Request $request, Material $material): RedirectResponse
    {
        Gate::authorize('manage-content');

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:200'],
            'description' => ['required', 'string'],
            'category' => ['required', 'string', 'max:100'],
            'file_path' => ['nullable', 'file', 'mimes:pdf,doc,docx,ppt,pptx,xls,xlsx,zip', 'max:102400'],
            'thumbnail_path' => ['nullable', 'image', 'mimes:jpeg,png,jpg,gif,webp', 'max:2048'],
            'is_active' => ['boolean'],
        ]);

        if ($request->hasFile('file_path')) {
            Storage::disk('local')->delete($material->file_path);

            $file = $request->file('file_path');
            $validated['file_path'] = $file->store('materials', 'local');
            $validated['file_name'] = $file->getClientOriginalName();
            $validated['file_type'] = $file->getClientOriginalExtension();
            $validated['file_size'] = $file->getSize();
        } else {
            unset($validated['file_path']);
        }

        if ($request->hasFile('thumbnail_path')) {
            if ($material->thumbnail_path) {
                Storage::disk('public')->delete($material->thumbnail_path);
            }
            $validated['thumbnail_path'] = $request->file('thumbnail_path')->store('materials/thumbnails', 'public');
        } else {
            unset($validated['thumbnail_path']);
        }

        $material->update($validated);

        return redirect()->route('admin.materials.index')->with('success', 'Material updated successfully.');
    }

    public function destroy(Material $material): RedirectResponse
    {
        Gate::authorize('delete-content');

        Storage::disk('local')->delete($material->file_path);
        if ($material->thumbnail_path) {
            Storage::disk('public')->delete($material->thumbnail_path);
        }

        $material->delete();

        return redirect()->route('admin.materials.index')->with('success', 'Material deleted successfully.');
    }
}
