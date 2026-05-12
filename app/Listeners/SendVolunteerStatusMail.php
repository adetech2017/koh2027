<?php

namespace App\Listeners;

use App\Events\VolunteerStatusChanged;
use App\Mail\VolunteerStatusChangedMail;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Mail;

class SendVolunteerStatusMail implements ShouldQueue
{
    public function handle(VolunteerStatusChanged $event): void
    {
        if (in_array($event->newStatus, ['approved', 'active'])) {
            Mail::to($event->volunteer->email)->queue(
                new VolunteerStatusChangedMail($event->volunteer, $event->newStatus)
            );
        }
    }
}
