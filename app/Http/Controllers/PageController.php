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
        return Inertia::render('About', compact('pillars'))->withViewData(['meta' => [
            'title' => 'About Kadri Obafemi Hamzat — KOH 2027',
            'description' => 'Over 30 years of public and private sector leadership. Learn about Kadri Obafemi Hamzat and his vision for Lagos.',
        ]]);
    }

    public function platforms(Request $request): Response
    {
        $pillars = PlatformPillar::active()->get(['id', 'title', 'slug', 'summary', 'body', 'icon', 'color']);

        // Each pillar's PDF on the Manifesto page is titled "… Pillar N: …", in pillar order
        $documents = \App\Models\Material::active()
            ->where('category', 'manifesto')
            ->get(['id', 'title', 'file_size'])
            ->mapWithKeys(fn ($m) => preg_match('/pillar\s*(\d+)/i', $m->title, $match)
                ? [(int) $match[1] => ['id' => $m->id, 'file_size' => $m->file_size]]
                : []);

        return Inertia::render('Platforms', [
            'pillars' => $pillars->values()->map(fn ($pillar, $i) => [
                ...$pillar->toArray(),
                'document' => $documents[$i + 1] ?? null,
            ]),
        ])->withViewData(['meta' => [
            'title' => 'Our Platform: The Lagos Promise — KOH 2027',
            'description' => 'The seven pillars of The Lagos Promise, the plan of Kadri Obafemi Hamzat for Lagos State.',
        ]]);
    }

    public function contact(Request $request): Response
    {
        return Inertia::render('Contact')->withViewData(['meta' => [
            'title' => 'Contact the Campaign — KOH 2027',
            'description' => 'Get in touch with the Kadri Obafemi Hamzat campaign for Lagos State.',
        ]]);
    }

    public function privacy(Request $request): Response
    {
        return Inertia::render('Privacy');
    }
}
