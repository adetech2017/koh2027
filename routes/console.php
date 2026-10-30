<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('crm:weekly-digest')->weeklyOn(1, '08:00');
Schedule::command('analytics:prune')->dailyAt('03:00');
Schedule::command('fleet:sync-evaluations')->hourly();
// Daytime only, so nobody gets a reminder at 3am
Schedule::command('fleet:send-reminders')->hourlyAt(15)->between('8:00', '19:00')->timezone('Africa/Lagos');
Schedule::command('fleet:sync-templates')->everyFifteenMinutes();
