<?php

namespace App\Console\Commands;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

#[Signature('analytics:prune {--days=400 : Keep this many days of raw traffic data}')]
#[Description('Delete old page views and click events so the analytics tables stay small')]
class PruneSiteAnalytics extends Command
{
    public function handle(): int
    {
        $cutoff = now()->subDays((int) $this->option('days'));

        $views = DB::table('page_views')->where('created_at', '<', $cutoff)->delete();
        $events = DB::table('site_events')->where('created_at', '<', $cutoff)->delete();

        $this->info("Deleted {$views} page views and {$events} click events older than {$cutoff->toDateString()}.");

        return self::SUCCESS;
    }
}
