<?php

namespace App\Services\Fleet;

use App\Models\Fleet\Assignment;
use App\Models\Fleet\Evaluation;
use App\Models\Fleet\EvaluationPeriod;
use App\Models\Fleet\Handover;
use App\Models\Fleet\MessageTemplate;
use App\Models\Fleet\Vehicle;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Evaluation compliance per vehicle out, the dashboard's evaluation figures, and odometer history.
 */
class FleetReports
{
    // A finished window that counts as done: a video arrived (whatever the review said, it was sent on time)
    private const ANSWERED = ['submitted', 'approved', 'rejected'];

    /**
     * One row per vehicle currently out, with how its beneficiary is keeping up.
     *
     * standing: overdue (the last finished window was missed or its video rejected, and nothing
     * accepted since), due_soon (this window ends within the reminder period with nothing sent), ok.
     */
    public function compliance(?string $lga = null): Collection
    {
        $soon = now()->addDays(MessageTemplate::reminderDays());
        $lastSubmitted = Evaluation::selectRaw('assignment_id, max(submitted_at) as last_at')->groupBy('assignment_id')->pluck('last_at', 'assignment_id');

        return Assignment::query()
            ->where('status', 'active')
            ->with(['vehicle', 'beneficiary', 'recall', 'evaluationPeriods' => fn ($q) => $q->orderBy('sequence')])
            ->when($lga, fn ($q) => $q->whereHas('beneficiary', fn ($b) => $b->where('lga', $lga)))
            ->get()
            ->map(function (Assignment $assignment) use ($soon, $lastSubmitted) {
                $periods = $assignment->evaluationPeriods;
                $current = $periods->first(fn (EvaluationPeriod $p) => $p->starts_at->isPast() && $p->ends_at->isFuture());
                $finished = $periods->filter(fn (EvaluationPeriod $p) => $p->ends_at->isPast());
                $lastFinished = $finished->last();
                $counts = $periods->countBy('status');

                $currentDone = $current && in_array($current->status, ['submitted', 'approved']);
                $standing = match (true) {
                    $lastFinished && in_array($lastFinished->status, ['missed', 'rejected']) && !$currentDone => 'overdue',
                    $current && $current->status === 'open' && $current->ends_at->lessThanOrEqualTo($soon) => 'due_soon',
                    default => 'ok',
                };

                return [
                    'assignment_id' => $assignment->id,
                    'vehicle' => ['id' => $assignment->vehicle->id, 'plate_number' => $assignment->vehicle->displayPlate()],
                    'beneficiary' => $assignment->beneficiary->toApi(),
                    'handed_over_at' => $assignment->handed_over_at->toIso8601String(),
                    'recalled' => (bool) $assignment->recall_id,
                    'current' => $current ? [
                        'status' => $current->status,
                        'ends_at' => $current->ends_at->toIso8601String(),
                    ] : null,
                    'approved' => $counts['approved'] ?? 0,
                    'awaiting_review' => $counts['submitted'] ?? 0,
                    'rejected' => $counts['rejected'] ?? 0,
                    'missed' => $counts['missed'] ?? 0,
                    // Of the windows that have finished, the share with a video sent in time
                    'on_time_rate' => $finished->count() ? round($finished->whereIn('status', self::ANSWERED)->count() / $finished->count(), 2) : null,
                    'last_submitted_at' => isset($lastSubmitted[$assignment->id]) ? Carbon::parse($lastSubmitted[$assignment->id])->toIso8601String() : null,
                    'standing' => $standing,
                ];
            })
            ->sortBy(fn ($row) => [['overdue' => 0, 'due_soon' => 1, 'ok' => 2][$row['standing']], $row['current']['ends_at'] ?? ''])
            ->values();
    }

    public function dashboard(): array
    {
        $active = fn ($q) => $q->where('status', 'active');
        $currentPeriods = EvaluationPeriod::whereHas('assignment', $active)
            ->where('starts_at', '<=', now())->where('ends_at', '>', now());

        return [
            'pending_review' => Evaluation::where('review_status', 'pending')->where('processing_status', 'ready')->count(),
            'this_window' => [
                'vehicles' => (clone $currentPeriods)->count(),
                'sent' => (clone $currentPeriods)->whereIn('status', ['submitted', 'approved'])->count(),
            ],
            'missed_30_days' => EvaluationPeriod::where('status', 'missed')->where('ends_at', '>=', now()->subDays(30))->count(),
            'notifications_failed_7_days' => \App\Models\Fleet\Notification::where('status', 'failed')->where('created_at', '>=', now()->subDays(7))->count(),
            'weekly' => $this->weekly(8),
        ];
    }

    /**
     * Windows that ended in each of the last $weeks weeks: how many had a video in time, how many were missed.
     */
    public function weekly(int $weeks): array
    {
        $start = now()->startOfWeek()->subWeeks($weeks - 1);
        $periods = EvaluationPeriod::where('ends_at', '>=', $start)->where('ends_at', '<=', now())
            ->whereIn('status', [...self::ANSWERED, 'missed'])
            ->get(['ends_at', 'status']);

        return collect(range(0, $weeks - 1))->map(function ($i) use ($start, $periods) {
            $from = $start->copy()->addWeeks($i);
            $inWeek = $periods->filter(fn ($p) => $p->ends_at->between($from, $from->copy()->endOfWeek()));

            return [
                'week_of' => $from->toDateString(),
                'on_time' => $inWeek->whereIn('status', self::ANSWERED)->count(),
                'missed' => $inWeek->where('status', 'missed')->count(),
            ];
        })->all();
    }

    /**
     * Every odometer reading for a vehicle across all its assignments, oldest first.
     */
    public function mileage(Vehicle $vehicle): array
    {
        $handovers = Handover::whereHas('assignment', fn ($q) => $q->where('vehicle_id', $vehicle->id))
            ->with('assignment.beneficiary')
            ->get()
            ->map(fn (Handover $h) => [
                'date' => $h->occurred_at->toIso8601String(),
                'km' => $h->odometer_km,
                'source' => $h->type,
                'beneficiary' => $h->assignment->beneficiary->full_name,
                'link' => "/handovers/{$h->id}",
            ]);

        $evaluations = Evaluation::where('vehicle_id', $vehicle->id)
            ->with('beneficiary')
            ->get()
            ->map(fn (Evaluation $e) => [
                'date' => $e->submitted_at->toIso8601String(),
                'km' => $e->mileage_km,
                'source' => 'evaluation',
                'review_status' => $e->review_status,
                'beneficiary' => $e->beneficiary->full_name,
                'link' => "/evaluations/{$e->id}",
            ]);

        return $handovers->concat($evaluations)->sortBy('date')->values()->all();
    }
}
