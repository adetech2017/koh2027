<?php

namespace App\Http\Controllers;

use App\Models\PlatformPillar;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PageController extends Controller
{
    public function about(Request $request): Response
    {
        $pillars = PlatformPillar::active()->get(['id', 'title', 'slug', 'summary', 'icon', 'color']);
        return Inertia::render('About', compact('pillars'));
    }

    public function platforms(Request $request): Response
    {
        $pillars = PlatformPillar::active()->get();
        return Inertia::render('Platforms', compact('pillars'));
    }

    public function contact(Request $request): Response
    {
        return Inertia::render('Contact');
    }

    public function privacy(Request $request): Response
    {
        return Inertia::render('Privacy');
    }
}
