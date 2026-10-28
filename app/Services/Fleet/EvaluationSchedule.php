<?php

namespace App\Services\Fleet;

use App\Models\Fleet\Assignment;
use App\Models\Fleet\EvaluationPeriod;

/**
 * Lays out each assignment's two-week windows from the handover date and keeps their status
 * current: past windows with nothing submitted become "missed"; when the vehicle comes back,
 * the open window is "closed".
 */
class EvaluationSchedule
{
    /**
     * Creates any windows that have started, settles finished ones, and returns the window
     * running now (null once the vehicle has been returned).
     */
    public function sync(Assignment $assignment): ?EvaluationPeriod
    {
        $cycleDays = config('fleet.evaluations.cycle_days');
        $until = $assignment->returned_at ?? now();
        $last = $assignment->evaluationPeriods()->max('sequence') ?? 0;

        // Create every window that has started (normally one at a time; more if the job didn't run)
        for ($sequence = $last + 1; ; $sequence++) {
            $startsAt = $assignment->handed_over_at->copy()->addDays($cycleDays * ($sequence - 1));
            if ($startsAt->greaterThan($until)) {
                break;
            }

            $assignment->evaluationPeriods()->create([
                'sequence' => $sequence,
                'starts_at' => $startsAt,
                'ends_at' => $startsAt->copy()->addDays($cycleDays),
                'status' => 'open',
            ]);
        }

        $assignment->evaluationPeriods()->where('status', 'open')->where('ends_at', '<=', now())->update(['status' => 'missed']);

        if (!$assignment->isActive()) {
            $assignment->evaluationPeriods()->where('status', 'open')->update(['status' => 'closed']);

            return null;
        }

        return $assignment->evaluationPeriods()
            ->where('starts_at', '<=', now())
            ->where('ends_at', '>', now())
            ->first();
    }

    /**
     * Run from the scheduler for every vehicle that's out (and any just returned).
     */
    public function syncAll(): int
    {
        $count = 0;

        Assignment::where('status', 'active')
            ->orWhereHas('evaluationPeriods', fn ($q) => $q->where('status', 'open'))
            ->chunkById(200, function ($assignments) use (&$count) {
                foreach ($assignments as $assignment) {
                    $this->sync($assignment);
                    $count++;
                }
            });

        return $count;
    }
}
