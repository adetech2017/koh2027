<?php

namespace App\Events;

use App\Models\Volunteer;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class VolunteerStatusChanged
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public Volunteer $volunteer,
        public string $newStatus,
        public string $oldStatus,
    ) {}
}
