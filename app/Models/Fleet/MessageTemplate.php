<?php

namespace App\Models\Fleet;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A WhatsApp template. Automatic ones are sent by the app (reminders, rejections, recalls) and
 * fill their variables in the order listed in AUTOMATIC; custom ones are sent by admins, with
 * {{1}} always the beneficiary's first name and the rest typed in when sending.
 */
class MessageTemplate extends Model
{
    protected $table = 'fleet_message_templates';

    protected $guarded = [];

    public const FIRST_NAME = ['label' => 'First name', 'example' => 'Adebayo'];

    private const PLATE = ['label' => 'Plate number', 'example' => 'LSD 123 AB'];

    public const AUTOMATIC = [
        'evaluation_due' => [
            'description' => "Sent a few days before a two-weekly video check is due, if the beneficiary hasn't sent it yet.",
            'variables' => [self::FIRST_NAME, self::PLATE, ['label' => 'Due date', 'example' => 'Tue 1 Dec 2026']],
        ],
        'evaluation_missed' => [
            'description' => 'Sent once, the day after a two-week window ends with no video.',
            'variables' => [self::FIRST_NAME, self::PLATE, ['label' => 'Date it was due', 'example' => 'Tue 1 Dec 2026']],
        ],
        'evaluation_rejected' => [
            'description' => 'Sent when a reviewer rejects a video, with the reason they gave.',
            'variables' => [self::FIRST_NAME, self::PLATE, ['label' => "Reviewer's reason", 'example' => 'The odometer is not shown in the video']],
        ],
        'vehicle_recalled' => [
            'description' => 'Sent to each beneficiary in a recall when it is created.',
            'variables' => [self::FIRST_NAME, self::PLATE, ['label' => 'Return-by date', 'example' => 'Tue 1 Dec 2026']],
        ],
    ];

    protected function casts(): array
    {
        return [
            'variables' => 'array',
            'draft_variables' => 'array',
            'settings' => 'array',
            'enabled' => 'boolean',
            'draft_submitted_at' => 'datetime',
            'checked_at' => 'datetime',
        ];
    }

    public function editor(): BelongsTo
    {
        return $this->belongsTo(FleetAdmin::class, 'updated_by');
    }

    /**
     * How many days before the due date the reminder goes out (compliance's "due soon" uses it too).
     */
    public static function reminderDays(): int
    {
        return (int) (static::where('key', 'evaluation_due')->first()?->settings['days_before'] ?? 3);
    }

    public function isAutomatic(): bool
    {
        return $this->key !== null;
    }

    /**
     * Every variable in order, {{1}} first: [{ label, example }]
     */
    public function variableList(bool $draft = false): array
    {
        if ($this->isAutomatic()) {
            return self::AUTOMATIC[$this->key]['variables'];
        }

        return [self::FIRST_NAME, ...(($draft ? $this->draft_variables : $this->variables) ?? [])];
    }

    /**
     * Admins can only send a custom template Meta has approved; automatic ones are sent whatever
     * the app last heard from Meta, since they may have been set up in WhatsApp Manager directly.
     */
    public function canSend(): bool
    {
        return $this->enabled && $this->body !== null && ($this->isAutomatic() || $this->status === 'approved');
    }

    public function render(array $values, ?string $body = null): string
    {
        return preg_replace_callback('/\{\{(\d+)\}\}/', fn ($m) => $values[$m[1] - 1] ?? $m[0], $body ?? $this->body ?? '');
    }

    /**
     * Why Meta would refuse this wording, or null when it's fine.
     */
    public static function wordingError(string $body, int $variables): ?string
    {
        $body = trim($body);
        $expected = $variables === 1 ? '{{1}}' : '{{1}} to {{'.$variables.'}}';

        if (mb_strlen($body) > 1024) {
            return 'Keep the message under 1,024 characters.';
        }
        // Anything brace-like left once the proper blanks are taken out, e.g. {{ 1 }} or {{name}}
        if (preg_match('/\{\{|\}\}/', preg_replace('/\{\{\d+\}\}/', '', $body))) {
            return 'Write the blanks as {{1}}, {{2}} and so on, with no spaces or words inside the braces.';
        }

        preg_match_all('/\{\{(\d+)\}\}/', $body, $matches);
        $used = array_map('intval', $matches[1]);
        sort($used);
        if ($used !== range(1, $variables)) {
            return "Use each of {$expected} exactly once.";
        }
        if (str_starts_with($body, '{{') || str_ends_with($body, '}}')) {
            return "WhatsApp doesn't allow a message to start or end with a blank. Add some words before the first one or after the last.";
        }

        return null;
    }

    public function toApi(): array
    {
        $numbered = fn (array $variables) => array_map(
            fn ($variable, $i) => $variable + ['number' => $i + 1],
            $variables,
            array_keys($variables),
        );

        return [
            'id' => $this->id,
            'key' => $this->key,
            'automatic' => $this->isAutomatic(),
            'title' => $this->title,
            'description' => $this->isAutomatic() ? self::AUTOMATIC[$this->key]['description'] : null,
            'name' => $this->name,
            'language' => $this->language,
            'category' => $this->category,
            'body' => $this->body,
            'variables' => $numbered($this->variableList()),
            'settings' => (object) ($this->settings ?? []),
            'enabled' => $this->enabled,
            'status' => $this->status,
            'status_reason' => $this->status_reason,
            'checked_at' => $this->checked_at?->toIso8601String(),
            'can_send' => $this->canSend(),
            'draft' => $this->draft_status ? [
                'name' => $this->draft_name,
                'body' => $this->draft_body,
                'variables' => $numbered($this->variableList(draft: true)),
                'status' => $this->draft_status,
                'reason' => $this->draft_reason,
                'submitted_at' => $this->draft_submitted_at?->toIso8601String(),
            ] : null,
            'updated_by' => $this->editor?->name,
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
