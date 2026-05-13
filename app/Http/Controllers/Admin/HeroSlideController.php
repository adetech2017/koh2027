<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\HeroSlide;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class HeroSlideController extends Controller
{
    public function index(): Response
    {
        $slides = HeroSlide::orderBy('sort_order')->paginate(20)->through(function ($slide) {
            return $slide->append('image_url');
        });

        return Inertia::render('Admin/HeroSlides/Index', [
            'slides' => $slides,
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Admin/HeroSlides/Create');
    }

    public function store(Request $request): RedirectResponse
    {
        Gate::authorize('manage-content');

        $validated = $request->validate([
            'tagline' => ['required', 'string', 'max:100'],
            'headline' => ['required', 'string', 'max:200'],
            'subtitle' => ['required', 'string', 'max:300'],
            'cta_text' => ['nullable', 'string', 'max:50'],
            'cta_style' => ['nullable', 'in:primary,secondary'],
            'image_path' => ['required', 'image', 'mimes:jpeg,png,jpg,gif,webp', 'max:2048'],
            'is_active' => ['boolean'],
            'sort_order' => ['required', 'integer'],
        ]);

        if ($request->hasFile('image_path')) {
            $path = $request->file('image_path')->store('hero-slides', 'public');
            $validated['image_path'] = $path;
        }

        HeroSlide::create($validated);

        return redirect()->route('admin.hero-slides.index')->with('success', 'Hero slide created successfully.');
    }

    public function edit(HeroSlide $heroSlide): Response
    {
        return Inertia::render('Admin/HeroSlides/Edit', [
            'slide' => [...$heroSlide->toArray(), 'image_url' => $heroSlide->image_url],
        ]);
    }

    public function update(Request $request, HeroSlide $heroSlide): RedirectResponse
    {
        Gate::authorize('manage-content');

        $validated = $request->validate([
            'tagline' => ['required', 'string', 'max:100'],
            'headline' => ['required', 'string', 'max:200'],
            'subtitle' => ['required', 'string', 'max:300'],
            'cta_text' => ['nullable', 'string', 'max:50'],
            'cta_style' => ['nullable', 'in:primary,secondary'],
            'image_path' => ['nullable', 'image', 'mimes:jpeg,png,jpg,gif,webp', 'max:2048'],
            'is_active' => ['boolean'],
            'sort_order' => ['required', 'integer'],
        ]);

        if ($request->hasFile('image_path')) {
            if ($heroSlide->image_path) {
                \Illuminate\Support\Facades\Storage::disk('public')->delete($heroSlide->image_path);
            }
            $path = $request->file('image_path')->store('hero-slides', 'public');
            $validated['image_path'] = $path;
        } else {
            unset($validated['image_path']);
        }

        $heroSlide->update($validated);

        return redirect()->route('admin.hero-slides.index')->with('success', 'Hero slide updated successfully.');
    }

    public function destroy(HeroSlide $heroSlide): RedirectResponse
    {
        Gate::authorize('delete-content');

        if ($heroSlide->image_path) {
            \Illuminate\Support\Facades\Storage::disk('public')->delete($heroSlide->image_path);
        }

        $heroSlide->delete();

        return redirect()->route('admin.hero-slides.index')->with('success', 'Hero slide deleted successfully.');
    }

    public function reorder(Request $request): RedirectResponse
    {
        $slides = $request->validate([
            'slides' => ['required', 'array'],
            'slides.*.id' => ['required', 'exists:hero_slides,id'],
            'slides.*.sort_order' => ['required', 'integer'],
        ]);

        foreach ($slides['slides'] as $slide) {
            HeroSlide::find($slide['id'])->update(['sort_order' => $slide['sort_order']]);
        }

        return back()->with('success', 'Slides reordered successfully.');
    }
}
