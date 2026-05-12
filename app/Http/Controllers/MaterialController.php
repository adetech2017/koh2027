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
        $materials = Material::active()->get();
        $grouped = $materials->groupBy('category');

        return Inertia::render('Materials', [
            'materials' => $grouped->map(fn ($group) => $group->map(fn ($m) => [...$m->toArray(), 'thumbnail_url' => $m->thumbnail_url])),
            'categories' => $grouped->keys(),
        ]);
    }

    public function download(Request $request, int $id): StreamedResponse
    {
        $material = Material::where('id', $id)->where('is_active', true)->firstOrFail();

        if (!Storage::disk('local')->exists($material->file_path)) {
            abort(404, 'File not found.');
        }

        $material->increment('download_count');

        return Storage::disk('local')->download($material->file_path, $material->file_name);
    }
}
