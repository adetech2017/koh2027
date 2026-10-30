<?php

namespace App\Services\Fleet;

use App\Jobs\Fleet\SendFleetNotification;
use App\Models\Fleet\Assignment;
use App\Models\Fleet\Beneficiary;
use App\Models\Fleet\Evaluation;
use App\Models\Fleet\EvaluationPeriod;
use App\Models\Fleet\FleetAdmin;
use App\Models\Fleet\MessageTemplate;
use App\Models\Fleet\Notification;
use Illuminate\Support\Carbon;

/**
 * WhatsApp messages to beneficiaries. Each one is recorded with a unique reference before it's
 * queued, so a reminder run twice (or a retried request) never sends the same message twice.
 *
 * The automatic messages use the templates admins manage under Messages; the values are filled
 * in the order MessageTemplate::AUTOMATIC lists. A message whose template is switched off isn't sent.
 */
class FleetNotifier
{
    /** @var array<string, MessageTemplate|null> */
    private array $templates = [];

    public function evaluationDue(EvaluationPeriod $period): ?Notification
    {
        $assignment = $period->assignment;

        return $this->automatic($assignment->beneficiary, 'evaluation_due', "evaluation_due:period:{$period->id}", [
            $this->firstName($assignment->beneficiary),
            $assignment->vehicle->displayPlate(),
            $this->date($period->ends_at),
        ]);
    }

    public function evaluationMissed(EvaluationPeriod $period): ?Notification
    {
        $assignment = $period->assignment;

        return $this->automatic($assignment->beneficiary, 'evaluation_missed', "evaluation_missed:period:{$period->id}", [
            $this->firstName($assignment->beneficiary),
            $assignment->vehicle->displayPlate(),
            $this->date($period->ends_at),
        ]);
    }

    public function evaluationRejected(Evaluation $evaluation): ?Notification
    {
        return $this->automatic($evaluation->beneficiary, 'evaluation_rejected', "evaluation_rejected:evaluation:{$evaluation->id}", [
            $this->firstName($evaluation->beneficiary),
            $evaluation->vehicle->displayPlate(),
            self::clean($evaluation->review_notes, 300),
        ]);
    }

    public function vehicleRecalled(Assignment $assignment): ?Notification
    {
        return $this->automatic($assignment->beneficiary, 'vehicle_recalled', "vehicle_recalled:assignment:{$assignment->id}:recall:{$assignment->recall_id}", [
            $this->firstName($assignment->beneficiary),
            $assignment->vehicle->displayPlate(),
            $this->date($assignment->recall->return_by),
        ]);
    }

    /**
     * A message an admin sends: {{1}} is the beneficiary's first name, $values fill {{2}} onwards.
     */
    public function custom(MessageTemplate $template, Beneficiary $beneficiary, array $values, string $batch, FleetAdmin $admin): ?Notification
    {
        return $this->queue($template, $beneficiary, 'custom', "custom:{$batch}:{$beneficiary->id}", [
            $this->firstName($beneficiary),
            ...array_map(fn ($value) => self::clean($value, 500), $values),
        ], ['sent_by' => $admin->id, 'batch' => $batch]);
    }

    /**
     * WhatsApp refuses template values with line breaks, tabs or long runs of spaces.
     */
    public static function clean(?string $value, int $limit): string
    {
        return str($value)->squish()->limit($limit)->toString();
    }

    private function automatic(Beneficiary $beneficiary, string $key, string $reference, array $parameters): ?Notification
    {
        $template = $this->templates[$key] ??= MessageTemplate::where('key', $key)->first();

        return $template?->canSend() ? $this->queue($template, $beneficiary, $key, $reference, $parameters) : null;
    }

    private function queue(MessageTemplate $template, Beneficiary $beneficiary, string $type, string $reference, array $parameters, array $extra = []): ?Notification
    {
        $notification = Notification::firstOrCreate(['reference' => $reference], [
            'beneficiary_id' => $beneficiary->id,
            'type' => $type,
            'message_template_id' => $template->id,
            'template' => $template->name,
            'language' => $template->language,
            'parameters' => $parameters,
            'body' => $template->render($parameters),
            ...$extra,
        ]);

        if (!$notification->wasRecentlyCreated) {
            return null;
        }

        SendFleetNotification::dispatch($notification->id);

        return $notification;
    }

    private function firstName(Beneficiary $beneficiary): string
    {
        return explode(' ', trim($beneficiary->full_name))[0];
    }

    private function date(Carbon $date): string
    {
        return $date->timezone('Africa/Lagos')->format('D j M Y');
    }
}
