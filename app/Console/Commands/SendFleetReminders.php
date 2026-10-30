<?php

namespace App\Console\Commands;

use App\Models\Fleet\EvaluationPeriod;
use App\Models\Fleet\MessageTemplate;
use App\Services\Fleet\EvaluationSchedule;
use App\Services\Fleet\FleetNotifier;
use Illuminate\Console\Command;

/**
 * WhatsApp reminders: a few days before an evaluation is due, and once when one has been missed.
 * Safe to run as often as you like: each message is only ever sent once.
 */
class SendFleetReminders extends Command
{
    protected $signature = 'fleet:send-reminders';

    protected $description = 'Send WhatsApp reminders for due and missed vehicle evaluations';

    public function handle(EvaluationSchedule $schedule, FleetNotifier $notifier): int
    {
        // Make sure windows are current before deciding who to remind
        $schedule->syncAll();

        $due = $missed = 0;
        $active = fn ($q) => $q->where('status', 'active');

        EvaluationPeriod::with('assignment.beneficiary', 'assignment.vehicle')
            ->where('status', 'open')
            ->where('ends_at', '>', now())
            ->where('ends_at', '<=', now()->addDays(MessageTemplate::reminderDays()))
            ->whereHas('assignment', $active)
            ->each(function (EvaluationPeriod $period) use ($notifier, &$due) {
                $notifier->evaluationDue($period) && $due++;
            });

        // Only recent misses: an old miss isn't worth a message (e.g. the first time this runs)
        EvaluationPeriod::with('assignment.beneficiary', 'assignment.vehicle')
            ->where('status', 'missed')
            ->where('ends_at', '>=', now()->subDays(2))
            ->whereHas('assignment', $active)
            ->each(function (EvaluationPeriod $period) use ($notifier, &$missed) {
                $notifier->evaluationMissed($period) && $missed++;
            });

        $this->info("Queued {$due} due reminders and {$missed} missed notices.");

        return self::SUCCESS;
    }
}
