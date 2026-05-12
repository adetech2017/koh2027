<?php

namespace App\Http\Controllers\Admin;

use App\Models\Event;
use App\Services\ExportService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class ExportController
{
    public function __construct(private ExportService $exportService) {}

    public function contacts(Request $request)
    {
        Gate::authorize('manage-content');

        $filters = $request->only('status');

        return $this->exportService->exportContacts($filters);
    }

    public function volunteers(Request $request)
    {
        Gate::authorize('manage-content');

        $filters = $request->only('status', 'lga');

        return $this->exportService->exportVolunteers($filters);
    }

    public function subscribers(Request $request)
    {
        Gate::authorize('manage-content');

        $filters = $request->only('status');

        return $this->exportService->exportSubscribers($filters);
    }

    public function rsvps(Request $request, int $eventId)
    {
        Gate::authorize('manage-content');

        Event::findOrFail($eventId);

        return $this->exportService->exportRsvps($eventId);
    }
}
