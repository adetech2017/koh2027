<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PlatformPillar;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class PlatformPillarController extends Controller
{
    // Must match the keys in resources/js/Utils/platformIcons.js, which the public pages render
    public const ICONS = [
        'heart', 'academic-cap', 'book-open', 'briefcase', 'banknotes', 'chart-bar', 'home', 'building-office-2',
        'building-library', 'scale', 'shield-check', 'user-group', 'bolt', 'truck', 'globe-alt', 'light-bulb',
        'wrench', 'sparkles',
    ];

    // HomeController shows this many active pillars
    public const HOMEPAGE_COUNT = 3;

    public function index(): Response
    {
        return Inertia::render('Admin/PlatformPillars/Index', [
            'pillars' => PlatformPillar::orderBy('sort_order')->orderBy('id')
                ->get(['id', 'title', 'slug', 'summary', 'icon', 'color', 'sort_order', 'is_active']),
            'homepageCount' => self::HOMEPAGE_COUNT,
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Admin/PlatformPillars/Create');
    }

    public function store(Request $request): RedirectResponse
    {
        Gate::authorize('manage-content');

        $validated = $this->validated($request);
        // New pillars go to the end
        $validated['sort_order'] = (PlatformPillar::max('sort_order') ?? 0) + 1;

        PlatformPillar::create($validated);

        return redirect()->route('admin.platform-pillars.index')->with('success', 'Pillar created.');
    }

    public function edit(PlatformPillar $platformPillar): Response
    {
        return Inertia::render('Admin/PlatformPillars/Edit', [
            'pillar' => $platformPillar,
        ]);
    }

    public function update(Request $request, PlatformPillar $platformPillar): RedirectResponse
    {
        Gate::authorize('manage-content');

        $platformPillar->update($this->validated($request));

        return redirect()->route('admin.platform-pillars.index')->with('success', 'Pillar saved.');
    }

    public function destroy(PlatformPillar $platformPillar): RedirectResponse
    {
        Gate::authorize('delete-content');
        $platformPillar->delete();

        return redirect()->route('admin.platform-pillars.index')->with('success', 'Pillar deleted.');
    }

    public function toggle(PlatformPillar $platformPillar): RedirectResponse
    {
        Gate::authorize('manage-content');

        $platformPillar->update(['is_active' => !$platformPillar->is_active]);

        return back()->with('success', $platformPillar->is_active ? 'Pillar is now showing on the site.' : 'Pillar hidden from the site.');
    }

    public function reorder(Request $request): RedirectResponse
    {
        Gate::authorize('manage-content');

        $validated = $request->validate([
            'ids' => ['required', 'array'],
            'ids.*' => ['integer', 'exists:platform_pillars,id'],
        ]);

        DB::transaction(function () use ($validated) {
            foreach (array_values($validated['ids']) as $position => $id) {
                PlatformPillar::whereKey($id)->update(['sort_order' => $position + 1]);
            }
        });

        return back()->with('success', 'Order saved.');
    }

    private function validated(Request $request): array
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:150'],
            'summary' => ['required', 'string', 'max:300'],
            'body' => ['required', 'string'],
            'icon' => ['required', 'string', 'in:'.implode(',', self::ICONS)],
            'color' => ['required', 'string', 'regex:/^#[A-Fa-f0-9]{6}$/'],
            'is_active' => ['boolean'],
        ], [
            'icon.in' => 'Choose one of the icons shown.',
            'color.regex' => 'Use a 6-digit hex colour like #27AE60.',
        ]);

        $validated['color'] = strtoupper($validated['color']);
        $validated['is_active'] = $request->boolean('is_active');

        return $validated;
    }
}
