<?php

namespace App\Http\Controllers\Admin;

use App\Models\ConstituentNote;
use App\Models\Contact;
use App\Models\Volunteer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class NotesController
{
    public function store(Request $request, string $type, int $id)
    {
        $validated = $request->validate(['body' => 'required|string|max:5000']);

        $modelClass = match ($type) {
            'contact' => Contact::class,
            'volunteer' => Volunteer::class,
            default => abort(404),
        };

        $model = $modelClass::findOrFail($id);

        ConstituentNote::create([
            'notable_type' => $modelClass,
            'notable_id' => $id,
            'author_id' => auth()->id(),
            'body' => $validated['body'],
        ]);

        return back()->with('success', 'Note added successfully');
    }

    public function destroy(ConstituentNote $note)
    {
        Gate::authorize('delete', $note);

        $note->delete();

        return back()->with('success', 'Note deleted successfully');
    }
}
