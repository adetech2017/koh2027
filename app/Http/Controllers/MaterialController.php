<?php

namespace App\Http\Controllers;

use App\Models\Material;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class MaterialController extends Controller
{
    public function index(Request $request): Response
    {
        // Only what the page shows: toArray() used to send each PDF's full extracted text
        // (about 340 KB in total) and internal file paths to every visitor
        $materials = Material::active()
            ->get(['id', 'title', 'description', 'category', 'file_type', 'file_size', 'thumbnail_path', 'download_count'])
            ->map(fn ($m) => [
                ...$m->only(['id', 'title', 'description', 'category', 'file_type', 'file_size', 'download_count']),
                'thumbnail_url' => $m->thumbnail_url,
            ]);
        $grouped = $materials->groupBy('category');

        return Inertia::render('Materials', [
            'materials' => $grouped,
            'categories' => $grouped->keys(),
            // Pillar names, summaries, icons and colours come from Admin → Platform
            'pillars' => \App\Models\PlatformPillar::active()->get(['id', 'title', 'slug', 'summary', 'icon', 'color']),
        ])->withViewData(['meta' => [
            'title' => 'The Lagos Promise — Manifesto | KOH 2027',
            'description' => 'Download The Lagos Promise, the manifesto of Kadri Obafemi Hamzat for Lagos State, in full or pillar by pillar.',
        ]]);
    }

    public function download(Request $request, int $id): StreamedResponse
    {
        $material = Material::where('id', $id)->where('is_active', true)->firstOrFail();

        if (!Storage::disk('local')->exists($material->file_path)) {
            abort(404, 'File not found.');
        }

        $material->increment('download_count');

        if (\App\Support\Visitor::shouldTrack($request)) {
            \Illuminate\Support\Facades\DB::table('site_events')->insert([
                'visitor_id' => \App\Support\Visitor::id($request),
                'name' => 'download',
                'label' => substr($material->title, 0, 255),
                'path' => '/materials',
                'created_at' => now(),
            ]);
        }

        return Storage::disk('local')->download($material->file_path, $material->file_name);
    }
}
