<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Merchandise;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class MerchandiseController extends Controller
{
    public function index(): Response
    {
        $merchandise = Merchandise::orderBy('created_at', 'desc')->paginate(20);

        return Inertia::render('Admin/Merchandise/Index', [
            'merchandise' => $merchandise,
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Admin/Merchandise/Create');
    }

    public function store(Request $request): RedirectResponse
    {
        Gate::authorize('manage-content');

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:200'],
            'description' => ['required', 'string'],
            'category' => ['required', 'string', 'max:100'],
            'price' => ['required', 'decimal:0,2'],
            'currency' => ['required', 'in:NGN,USD'],
            'sizes' => ['nullable', 'json'],
            'colors' => ['nullable', 'json'],
            'sku' => ['required', 'string', 'unique:merchandise'],
            'stock_quantity' => ['required', 'integer'],
            'in_stock' => ['boolean'],
            'is_active' => ['boolean'],
            'is_featured' => ['boolean'],
            'sort_order' => ['required', 'integer'],
        ]);

        Merchandise::create($validated);

        return redirect()->route('admin.merchandise.index')->with('success', 'Product created successfully.');
    }

    public function edit(Merchandise $merchandise): Response
    {
        return Inertia::render('Admin/Merchandise/Edit', [
            'product' => $merchandise,
        ]);
    }

    public function update(Request $request, Merchandise $merchandise): RedirectResponse
    {
        Gate::authorize('manage-content');

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:200'],
            'description' => ['required', 'string'],
            'category' => ['required', 'string', 'max:100'],
            'price' => ['required', 'decimal:0,2'],
            'currency' => ['required', 'in:NGN,USD'],
            'sizes' => ['nullable', 'json'],
            'colors' => ['nullable', 'json'],
            'sku' => ['required', 'string', 'unique:merchandise,sku,' . $merchandise->id],
            'stock_quantity' => ['required', 'integer'],
            'in_stock' => ['boolean'],
            'is_active' => ['boolean'],
            'is_featured' => ['boolean'],
            'sort_order' => ['required', 'integer'],
        ]);

        $merchandise->update($validated);

        return redirect()->route('admin.merchandise.index')->with('success', 'Product updated successfully.');
    }

    public function destroy(Merchandise $merchandise): RedirectResponse
    {
        Gate::authorize('delete-content');

        foreach ($merchandise->images as $image) {
            if ($image->image_path) {
                \Illuminate\Support\Facades\Storage::disk('public')->delete($image->image_path);
            }
        }

        $merchandise->delete();

        return redirect()->route('admin.merchandise.index')->with('success', 'Product deleted successfully.');
    }
}
