<?php

namespace App\Http\Controllers\Api;

use App\Models\Event;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ApiEventController
{
    public function index(Request $request): JsonResponse
    {
        $query = Event::upcoming();

        if ($request->filled('lga')) {
            $query->where('lga', $request->lga);
        }

        $limit = min((int) $request->query('limit', 20), 50);
        $events = $query->take($limit)->get(['id', 'title', 'slug', 'lga', 'region', 'event_type', 'starts_at', 'venue_name', 'address', 'image_path', 'rsvp_enabled']);

        return response()->json([
            'data' => $events->map(fn ($e) => [...$e->toArray(), 'image_url' => $e->image_url]),
            'count' => $events->count(),
        ]);
    }
}
