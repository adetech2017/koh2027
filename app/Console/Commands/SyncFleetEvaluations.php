<?php

namespace App\Console\Commands;

use App\Models\Fleet\UploadSession;
use App\Services\Fleet\ChunkedUploads;
use App\Services\Fleet\EvaluationSchedule;
use Illuminate\Console\Command;

/**
 * Opens new two-week evaluation windows, marks unanswered past ones as missed, and clears out
 * uploads that were started but never finished.
 */
class SyncFleetEvaluations extends Command
{
    protected $signature = 'fleet:sync-evaluations';

    protected $description = 'Open new evaluation windows, mark missed ones, and remove abandoned uploads';

    public function handle(EvaluationSchedule $schedule, ChunkedUploads $uploads): int
    {
        $count = $schedule->syncAll();

        $abandoned = UploadSession::whereNull('completed_at')->where('created_at', '<', now()->subDay())->get();
        foreach ($abandoned as $session) {
            $uploads->discard($session);
            $session->delete();
        }

        $this->info("Synced {$count} assignments; removed {$abandoned->count()} abandoned uploads.");

        return self::SUCCESS;
    }
}
