<?php

namespace App\Http\Controllers\Admin;

use App\Models\Contact;
use App\Models\Tag;
use App\Models\Volunteer;
use Illuminate\Http\Request;

class TagController
{
    public function index()
    {
        return Tag::orderBy('name')->get(['id', 'name', 'slug', 'color']);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|unique:tags|max:100',
            'color' => 'nullable|string|regex:/^#[0-9A-Fa-f]{6}$/',
        ]);

        $tag = Tag::create([
            'name' => $validated['name'],
            'color' => $validated['color'] ?? '#003D82',
        ]);

        return response()->json($tag, 201);
    }

    public function attach(Request $request, string $type, int $id)
    {
        $validated = $request->validate(['tag_id' => 'required|exists:tags,id']);

        $modelClass = match ($type) {
            'contact' => Contact::class,
            'volunteer' => Volunteer::class,
            default => abort(404),
        };

        $model = $modelClass::findOrFail($id);
        $model->tags()->syncWithoutDetaching([$validated['tag_id']]);

        return back()->with('success', 'Tag added');
    }

    public function detach(Request $request, string $type, int $id, Tag $tag)
    {
        $modelClass = match ($type) {
            'contact' => Contact::class,
            'volunteer' => Volunteer::class,
            default => abort(404),
        };

        $model = $modelClass::findOrFail($id);
        $model->tags()->detach($tag->id);

        return back()->with('success', 'Tag removed');
    }
}
