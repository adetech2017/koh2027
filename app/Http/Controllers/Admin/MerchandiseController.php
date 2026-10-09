<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Merchandise;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class MerchandiseController extends Controller
{
    public function index(Request $request): Response
    {
        $filter = in_array($request->query('filter'), ['live', 'hidden']) ? $request->query('filter') : null;
        $search = trim((string) $request->query('search'));

        $products = Merchandise::query()
            ->with('primaryImage')
            ->withCount('images')
            ->when($filter === 'live', fn ($q) => $q->where('is_active', true))
            ->when($filter === 'hidden', fn ($q) => $q->where('is_active', false))
            ->when($search !== '', fn ($q) => $q->where(fn ($q) => $q
                ->where('name', 'like', "%{$search}%")
                ->orWhere('category', 'like', "%{$search}%")))
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get()
            ->map(fn ($product) => [
                ...$product->only(['id', 'name', 'slug', 'category', 'is_active', 'is_featured', 'sort_order', 'images_count']),
                'image_url' => $product->primaryImage?->image_url,
            ]);

        return Inertia::render('Admin/Merchandise/Index', [
            'products' => $products,
            'counts' => [
                'all' => Merchandise::count(),
                'live' => Merchandise::where('is_active', true)->count(),
                'hidden' => Merchandise::where('is_active', false)->count(),
                'no_photo' => Merchandise::doesntHave('images')->count(),
            ],
            'filters' => ['filter' => $filter, 'search' => $search],
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Admin/Merchandise/Create', [
            'categories' => $this->categories(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        Gate::authorize('manage-content');

        $validated = $this->validated($request);
        $validated['sort_order'] = (Merchandise::max('sort_order') ?? 0) + 1;
        // These are approved designs, not products for sale; the price column is still NOT NULL
        $validated['price'] = 0;

        $product = Merchandise::create($validated);

        // Photos are managed on the edit page, so continue there
        return redirect()->route('admin.merchandise.edit', $product)
            ->with('success', 'Design created. Now add some photos.');
    }

    public function edit(Merchandise $merchandise): Response
    {
        $merchandise->load('images');

        return Inertia::render('Admin/Merchandise/Edit', [
            'product' => $merchandise,
            'images' => $merchandise->images->map(fn ($image) => [
                ...$image->only(['id', 'image_alt', 'is_primary', 'sort_order']),
                'image_url' => $image->image_url,
            ]),
            'categories' => $this->categories(),
        ]);
    }

    public function update(Request $request, Merchandise $merchandise): RedirectResponse
    {
        Gate::authorize('manage-content');

        $merchandise->update($this->validated($request, $merchandise));

        return back()->with('success', 'Design saved.');
    }

    public function toggle(Merchandise $merchandise): RedirectResponse
    {
        Gate::authorize('manage-content');

        $merchandise->update(['is_active' => !$merchandise->is_active]);

        return back()->with('success', $merchandise->is_active ? "{$merchandise->name} is now showing on the website." : "{$merchandise->name} hidden from the website.");
    }

    public function reorder(Request $request): RedirectResponse
    {
        Gate::authorize('manage-content');

        $validated = $request->validate([
            'ids' => ['required', 'array'],
            'ids.*' => ['integer', 'exists:merchandises,id'],
        ]);

        DB::transaction(function () use ($validated) {
            foreach (array_values($validated['ids']) as $position => $id) {
                Merchandise::whereKey($id)->update(['sort_order' => $position + 1]);
            }
        });

        return back()->with('success', 'Order saved.');
    }

    public function destroy(Merchandise $merchandise): RedirectResponse
    {
        Gate::authorize('delete-content');

        foreach ($merchandise->images as $image) {
            if ($image->image_path) {
                Storage::disk('public')->delete($image->image_path);
            }
        }

        $merchandise->delete();

        return redirect()->route('admin.merchandise.index')->with('success', 'Design deleted.');
    }

    private function validated(Request $request, ?Merchandise $product = null): array
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:200'],
            'description' => ['required', 'string'],
            'category' => ['required', 'string', 'max:100'],
            // Sent as arrays; the model's array cast encodes them (sending JSON strings double-encoded them)
            'sizes' => ['nullable', 'array', 'max:30'],
            'sizes.*' => ['string', 'max:30', 'distinct'],
            'colors' => ['nullable', 'array', 'max:30'],
            'colors.*' => ['string', 'max:30', 'distinct'],
            'is_active' => ['boolean'],
            'is_featured' => ['boolean'],
        ], [
            'sizes.*.distinct' => 'Each size can only be listed once.',
            'colors.*.distinct' => 'Each colour can only be listed once.',
        ]);

        foreach (['is_active', 'is_featured'] as $flag) {
            $validated[$flag] = $request->boolean($flag);
        }
        $validated['sizes'] = array_values(array_filter($validated['sizes'] ?? [])) ?: null;
        $validated['colors'] = array_values(array_filter($validated['colors'] ?? [])) ?: null;
        $validated['category'] = str($validated['category'])->trim()->lower()->toString();

        return $validated;
    }

    private function categories(): array
    {
        return Merchandise::distinct()->orderBy('category')->pluck('category')->filter()->values()->all();
    }
}
