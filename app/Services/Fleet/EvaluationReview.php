<?php

namespace App\Services\Fleet;

use App\Models\Fleet\AuditLog;
use App\Models\Fleet\Evaluation;
use App\Models\Fleet\FleetAdmin;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * A reviewer's decision on an evaluation. Rejecting one while its two-week window is still
 * running reopens the window so the beneficiary can send a new video; after the window has
 * ended, the period is recorded as rejected.
 */
class EvaluationReview
{
    public function __construct(private FleetNotifier $notifier) {}

    public function decide(Evaluation $evaluation, string $decision, ?string $notes, FleetAdmin $reviewer): Evaluation
    {
        DB::transaction(function () use ($evaluation, $decision, $notes, $reviewer) {
            $evaluation = Evaluation::whereKey($evaluation->id)->lockForUpdate()->firstOrFail();

            if ($evaluation->review_status !== 'pending') {
                throw ValidationException::withMessages(['decision' => 'This evaluation has already been reviewed.']);
            }

            $evaluation->update([
                'review_status' => $decision === 'approve' ? 'approved' : 'rejected',
                'review_notes' => $notes,
                'reviewed_by' => $reviewer->id,
                'reviewed_at' => now(),
            ]);

            $period = $evaluation->period;
            $period->update(['status' => match (true) {
                $decision === 'approve' => 'approved',
                $period->ends_at->isFuture() => 'open',
                default => 'rejected',
            }]);

            AuditLog::record("evaluation.{$evaluation->review_status}", $evaluation, ['notes' => $notes], $reviewer);
        });

        $evaluation->refresh();
        if ($evaluation->review_status === 'rejected') {
            $this->notifier->evaluationRejected($evaluation->load('beneficiary', 'vehicle'));
        }

        return $evaluation;
    }
}
