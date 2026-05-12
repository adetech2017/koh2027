<?php

namespace App\Providers;

use App\Events\VolunteerStatusChanged;
use App\Listeners\SendVolunteerStatusMail;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    protected $listen = [
        VolunteerStatusChanged::class => [
            SendVolunteerStatusMail::class,
        ],
    ];

    public function boot(): void
    {
        //
    }
}
