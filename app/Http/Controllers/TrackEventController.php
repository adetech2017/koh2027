<?php

namespace App\Http\Controllers;

use App\Support\Visitor;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;

/**
 * Receives click events from resources/js/Utils/tracking.js (sent with navigator.sendBeacon).
 */
class TrackEventController extends Controller
{
    public const NAMES = ['cta_click', 'volunteer_click', 'outbound_click', 'share', 'contact_click', 'download'];

    public function __invoke(Request $request): Response
    {
        $data = $request->validate([
            'name' => ['required', 'in:'.implode(',', self::NAMES)],
            'label' => ['nullable', 'string', 'max:255'],
            'path' => ['nullable', 'string', 'max:255'],
        ]);

        if (Visitor::shouldTrack($request)) {
            DB::table('site_events')->insert([
                'visitor_id' => Visitor::id($request),
                'name' => $data['name'],
                'label' => $data['label'] ?? null,
                'path' => $data['path'] ?? null,
                'created_at' => now(),
            ]);
        }

        return response()->noContent();
    }
}
