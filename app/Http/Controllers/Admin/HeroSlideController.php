<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\HeroSlide;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class HeroSlideController extends Controller
{
    public function index(): Response
    {
        // A slider only ever has a handful of slides, so show them all in display order
        return Inertia::render('Admin/HeroSlides/Index', [
            'slides' => HeroSlide::orderBy('sort_order')->orderBy('id')->get(),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Admin/HeroSlides/Create');
    }

    public function store(Request $request): RedirectResponse
    {
        Gate::authorize('manage-content');

        $validated = $this->validated($request, imageRequired: true);
        $validated['image_path'] = $request->file('image_path')->store('hero-slides', 'public');
        // New slides go to the end of the slider
        $validated['sort_order'] = (HeroSlide::max('sort_order') ?? 0) + 1;

        HeroSlide::create($validated);

        return redirect()->route('admin.hero-slides.index')->with('success', 'Hero slide created successfully.');
    }

    public function edit(HeroSlide $heroSlide): Response
    {
        return Inertia::render('Admin/HeroSlides/Edit', [
            'slide' => $heroSlide,
        ]);
    }

    public function update(Request $request, HeroSlide $heroSlide): RedirectResponse
    {
        Gate::authorize('manage-content');

        $validated = $this->validated($request, imageRequired: false);

        if ($request->hasFile('image_path')) {
            if ($heroSlide->image_path) {
                Storage::disk('public')->delete($heroSlide->image_path);
            }
            $validated['image_path'] = $request->file('image_path')->store('hero-slides', 'public');
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
            Storage::disk('public')->delete($heroSlide->image_path);
        }

        $heroSlide->delete();

        return redirect()->route('admin.hero-slides.index')->with('success', 'Hero slide deleted successfully.');
    }

    public function toggle(HeroSlide $heroSlide): RedirectResponse
    {
        Gate::authorize('manage-content');

        $heroSlide->update(['is_active' => !$heroSlide->is_active]);

        return back()->with('success', $heroSlide->is_active ? 'Slide is now showing on the homepage.' : 'Slide hidden from the homepage.');
    }

    public function reorder(Request $request): RedirectResponse
    {
        Gate::authorize('manage-content');

        $validated = $request->validate([
            'ids' => ['required', 'array'],
            'ids.*' => ['integer', 'exists:hero_slides,id'],
        ]);

        DB::transaction(function () use ($validated) {
            foreach (array_values($validated['ids']) as $position => $id) {
                HeroSlide::whereKey($id)->update(['sort_order' => $position + 1]);
            }
        });

        return back()->with('success', 'Slide order saved.');
    }

    private function validated(Request $request, bool $imageRequired): array
    {
        $validated = $request->validate([
            'tagline' => ['required', 'string', 'max:100'],
            'headline' => ['required', 'string', 'max:200'],
            'subtitle' => ['required', 'string', 'max:300'],
            'cta_text' => ['nullable', 'required_with:cta_url', 'string', 'max:50'],
            // Internal paths like /volunteer or full https:// links
            'cta_url' => ['nullable', 'required_with:cta_text', 'string', 'max:255', 'regex:#^(/|https?://)#i'],
            'cta_style' => ['required', 'in:primary,secondary'],
            'image_path' => [$imageRequired ? 'required' : 'nullable', 'image', 'mimes:jpeg,png,jpg,gif,webp', 'max:2048'],
            'image_alt' => ['nullable', 'string', 'max:200'],
            'is_active' => ['boolean'],
        ], [
            'cta_url.regex' => 'The link must start with / (a page on this site) or https://.',
            'cta_url.required_with' => 'Add a link for the button, or clear the button text.',
            'cta_text.required_with' => 'Add button text, or clear the link.',
        ]);

        $validated['is_active'] = $request->boolean('is_active');

        return $validated;
    }
}
