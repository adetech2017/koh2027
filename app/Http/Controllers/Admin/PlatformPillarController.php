<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PlatformPillar;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class PlatformPillarController extends Controller
{
    protected array $allowedIcons = ['briefcase', 'book-open', 'heart', 'lightning-bolt', 'shield', 'users', 'target', 'zap'];
    private string $colorPattern = '/^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/';

    public function index(): Response
    {
        $pillars = PlatformPillar::orderBy('sort_order')->paginate(20);

        return Inertia::render('Admin/PlatformPillars/Index', [
            'pillars' => $pillars,
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Admin/PlatformPillars/Create', [
            'allowedIcons' => $this->allowedIcons,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        Gate::authorize('manage-content');

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:150'],
            'summary' => ['required', 'string', 'max:300'],
            'body' => ['required', 'string'],
            'icon' => ['required', 'string', 'in:' . implode(',', $this->allowedIcons)],
            'color' => ['required', 'string', 'regex:/^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/'],
            'is_active' => ['boolean'],
            'sort_order' => ['required', 'integer'],
        ]);

        PlatformPillar::create($validated);

        return redirect()->route('admin.platform-pillars.index')->with('success', 'Platform pillar created successfully.');
    }

    public function edit(PlatformPillar $platformPillar): Response
    {
        return Inertia::render('Admin/PlatformPillars/Edit', [
            'pillar' => $platformPillar,
            'allowedIcons' => $this->allowedIcons,
        ]);
    }

    public function update(Request $request, PlatformPillar $platformPillar): RedirectResponse
    {
        Gate::authorize('manage-content');

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:150'],
            'summary' => ['required', 'string', 'max:300'],
            'body' => ['required', 'string'],
            'icon' => ['required', 'string', 'in:' . implode(',', $this->allowedIcons)],
            'color' => ['required', 'string', 'regex:/^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/'],
            'is_active' => ['boolean'],
            'sort_order' => ['required', 'integer'],
        ]);

        $platformPillar->update($validated);

        return redirect()->route('admin.platform-pillars.index')->with('success', 'Platform pillar updated successfully.');
    }

    public function destroy(PlatformPillar $platformPillar): RedirectResponse
    {
        Gate::authorize('delete-content');
        $platformPillar->delete();

        return redirect()->route('admin.platform-pillars.index')->with('success', 'Platform pillar deleted successfully.');
    }
}
