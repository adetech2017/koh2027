<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Material;
use App\Services\ManifestoTextExtractor;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class MaterialController extends Controller
{
    private const DEFAULT_CATEGORIES = ['manifesto', 'policy', 'brochure', 'flyer', 'faq'];
    private const FILE_TYPES = 'pdf,doc,docx,ppt,pptx,xls,xlsx,zip';

    public function __construct(private ManifestoTextExtractor $extractor) {}

    public function index(Request $request): Response
    {
        $category = $request->query('category');

        $materials = Material::query()
            ->when($category, fn ($q) => $q->where('category', $category))
            ->orderBy('category')
            ->orderBy('title')
            ->get()
            ->map(fn ($material) => $this->present($material));

        return Inertia::render('Admin/Materials/Index', [
            'materials' => $materials,
            'categories' => Material::distinct()->orderBy('category')->pluck('category'),
            'filters' => ['category' => $category],
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Admin/Materials/Create', [
            'categories' => $this->categories(),
            'maxUploadBytes' => $this->maxUploadBytes(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        Gate::authorize('manage-content');

        $validated = $this->validated($request, fileRequired: true);
        $validated = array_merge($validated, $this->storeFile($request));
        if ($request->hasFile('thumbnail')) {
            $validated['thumbnail_path'] = $request->file('thumbnail')->store('materials/thumbnails', 'public');
        }

        $material = Material::create($validated);
        $this->extractAfterResponse($material);

        return redirect()->route('admin.materials.index')->with('success', 'Document uploaded.');
    }

    public function edit(Material $material): Response
    {
        return Inertia::render('Admin/Materials/Edit', [
            'material' => $this->present($material),
            'categories' => $this->categories(),
            'maxUploadBytes' => $this->maxUploadBytes(),
        ]);
    }

    public function update(Request $request, Material $material): RedirectResponse
    {
        Gate::authorize('manage-content');

        $validated = $this->validated($request, fileRequired: false);
        $replacingFile = $request->hasFile('file');
        $wasEligible = ManifestoTextExtractor::eligible($material);

        if ($replacingFile) {
            Storage::disk('local')->delete($material->file_path);
            $validated = array_merge($validated, $this->storeFile($request));
        }

        if ($request->hasFile('thumbnail') || $request->boolean('remove_thumbnail')) {
            if ($material->thumbnail_path) {
                Storage::disk('public')->delete($material->thumbnail_path);
            }
            $validated['thumbnail_path'] = $request->hasFile('thumbnail')
                ? $request->file('thumbnail')->store('materials/thumbnails', 'public')
                : null;
        }

        $material->update($validated);

        // A new file, or a change that makes the document eligible, needs fresh text for the assistant
        if ($replacingFile || (!$wasEligible && ManifestoTextExtractor::eligible($material))) {
            $material->forceFill(['extracted_text' => null, 'extracted_at' => null])->save();
            $this->extractAfterResponse($material);
        }

        return redirect()->route('admin.materials.index')->with('success', 'Document saved.');
    }

    public function destroy(Material $material): RedirectResponse
    {
        Gate::authorize('delete-content');

        Storage::disk('local')->delete($material->file_path);
        if ($material->thumbnail_path) {
            Storage::disk('public')->delete($material->thumbnail_path);
        }

        $material->delete();

        return redirect()->route('admin.materials.index')->with('success', 'Document deleted.');
    }

    public function toggle(Material $material): RedirectResponse
    {
        Gate::authorize('manage-content');

        $material->update(['is_active' => !$material->is_active]);

        return back()->with('success', $material->is_active ? 'Document is now downloadable on the site.' : 'Document hidden from the site.');
    }

    // Lets staff check the file without counting a public download (works for hidden documents too)
    public function download(Material $material): StreamedResponse
    {
        abort_unless(Storage::disk('local')->exists($material->file_path), 404, 'File not found.');

        return Storage::disk('local')->download($material->file_path, $material->file_name);
    }

    public function extract(Material $material): RedirectResponse
    {
        Gate::authorize('manage-content');

        if (!ManifestoTextExtractor::eligible($material)) {
            return back()->with('error', 'Only manifesto PDFs are read by the assistant.');
        }

        return $this->extractor->extract($material)
            ? back()->with('success', "Text extracted from \"{$material->title}\". The assistant can now use it.")
            : back()->with('error', 'Could not read text from this PDF. It may be a scanned image or the file may be missing.');
    }

    private function validated(Request $request, bool $fileRequired): array
    {
        $maxKb = intdiv($this->maxUploadBytes(), 1024);

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:200'],
            'description' => ['required', 'string', 'max:500'],
            'category' => ['required', 'string', 'max:100'],
            'file' => [$fileRequired ? 'required' : 'nullable', 'file', 'mimes:'.self::FILE_TYPES, "max:{$maxKb}"],
            'thumbnail' => ['nullable', 'image', 'mimes:jpeg,png,jpg,gif,webp', 'max:2048'],
            'is_active' => ['boolean'],
        ], [
            'file.max' => 'The file is larger than the server accepts ('.round($maxKb / 1024).' MB).',
            'file.mimes' => 'Upload a PDF, Word, PowerPoint, Excel or ZIP file.',
        ]);

        return [
            'title' => $validated['title'],
            'description' => $validated['description'],
            'category' => str($validated['category'])->trim()->lower()->toString(),
            'is_active' => $request->boolean('is_active'),
        ];
    }

    private function storeFile(Request $request): array
    {
        $file = $request->file('file');

        return [
            'file_path' => $file->store('materials', 'local'),
            'file_name' => $file->getClientOriginalName(),
            'file_type' => strtolower($file->getClientOriginalExtension()),
            'file_size' => $file->getSize(),
        ];
    }

    private function extractAfterResponse(Material $material): void
    {
        if (ManifestoTextExtractor::eligible($material)) {
            // Parsing a large PDF takes a few seconds; do it after the admin gets their response
            dispatch(fn () => app(ManifestoTextExtractor::class)->extract($material->fresh()))->afterResponse();
        }
    }

    private function present(Material $material): array
    {
        $eligible = ManifestoTextExtractor::eligible($material);
        $pillar = preg_match('/pillar\s*(\d)/i', $material->title, $m) ? (int) $m[1] : null;

        return [
            ...$material->only(['id', 'title', 'description', 'category', 'file_name', 'file_type', 'file_size', 'download_count', 'is_active', 'thumbnail_path', 'updated_at']),
            'thumbnail_url' => $material->thumbnail_url,
            'file_missing' => !Storage::disk('local')->exists($material->file_path),
            // How Pages/Materials.vue will place it: by category and a "Pillar N" in the title
            'public_role' => $material->category !== 'manifesto' ? 'category' : ($pillar ? 'pillar' : 'full'),
            'pillar_number' => $pillar,
            'assistant' => match (true) {
                !$eligible && $material->category === 'manifesto' && $material->file_type === 'pdf' => 'skipped',
                !$eligible => 'not_applicable',
                filled($material->extracted_text) => 'ready',
                default => 'missing',
            },
            'extracted_at' => $material->extracted_at,
        ];
    }

    private function categories(): array
    {
        return collect(self::DEFAULT_CATEGORIES)
            ->merge(Material::distinct()->pluck('category'))
            ->filter()->unique()->sort()->values()->all();
    }

    // The real ceiling is PHP's request limits, not the old hard-coded 100 MB
    private function maxUploadBytes(): int
    {
        $toBytes = function (string $value): int {
            $value = trim($value);
            $number = (int) $value;

            return match (strtolower(substr($value, -1))) {
                'g' => $number * 1024 ** 3,
                'm' => $number * 1024 ** 2,
                'k' => $number * 1024,
                default => $number,
            };
        };

        $limits = array_filter([
            $toBytes(ini_get('upload_max_filesize') ?: '2M'),
            // Leave room for the other form fields in the request
            $toBytes(ini_get('post_max_size') ?: '8M') - 1024 * 1024,
            200 * 1024 ** 2,
        ], fn ($v) => $v > 0);

        return min($limits);
    }
}
