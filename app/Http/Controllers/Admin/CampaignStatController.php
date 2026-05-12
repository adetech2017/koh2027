<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CampaignStat;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class CampaignStatController extends Controller
{
    public function index(): Response
    {
        $stats = CampaignStat::orderBy('sort_order')->paginate(20);

        return Inertia::render('Admin/CampaignStats/Index', [
            'stats' => $stats,
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Admin/CampaignStats/Create');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'label' => ['required', 'string', 'max:100'],
            'value' => ['required', 'string', 'max:100'],
            'icon' => ['required', 'string', 'max:50'],
            'color' => ['required', 'string', 'max:50'],
            'is_active' => ['boolean'],
            'sort_order' => ['required', 'integer'],
        ]);

        CampaignStat::create($validated);

        return redirect()->route('admin.campaign-stats.index')->with('success', 'Campaign stat created successfully.');
    }

    public function edit(CampaignStat $campaignStat): Response
    {
        return Inertia::render('Admin/CampaignStats/Edit', [
            'stat' => $campaignStat,
        ]);
    }

    public function update(Request $request, CampaignStat $campaignStat): RedirectResponse
    {
        $validated = $request->validate([
            'label' => ['required', 'string', 'max:100'],
            'value' => ['required', 'string', 'max:100'],
            'icon' => ['required', 'string', 'max:50'],
            'color' => ['required', 'string', 'max:50'],
            'is_active' => ['boolean'],
            'sort_order' => ['required', 'integer'],
        ]);

        $campaignStat->update($validated);

        return redirect()->route('admin.campaign-stats.index')->with('success', 'Campaign stat updated successfully.');
    }

    public function destroy(CampaignStat $campaignStat): RedirectResponse
    {
        Gate::authorize('delete-content');
        $campaignStat->delete();

        return redirect()->route('admin.campaign-stats.index')->with('success', 'Campaign stat deleted successfully.');
    }
}
