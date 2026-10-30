<?php

namespace App\Services\Fleet;

use App\Models\Fleet\MessageTemplate;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Keeps the admin's templates in step with Meta. New wording is submitted for review as a draft;
 * the approved wording keeps being sent until Meta approves the new one, then it takes over.
 */
class MessageTemplates
{
    // Meta's statuses → ours
    private const STATUSES = [
        'APPROVED' => 'approved',
        'PENDING' => 'pending',
        'IN_APPEAL' => 'pending',
        'REJECTED' => 'rejected',
        'PAUSED' => 'paused',
    ];

    // Meta's rejection codes, in plain words
    private const REASONS = [
        'ABUSIVE_CONTENT' => 'Meta found the content abusive.',
        'INVALID_FORMAT' => 'Meta says the format is invalid (check the blanks and their examples).',
        'PROMOTIONAL' => 'Meta thinks this is marketing, not a utility message. Make it about the vehicle or the account.',
        'TAG_CONTENT_MISMATCH' => "The wording doesn't match the category. Make it about the vehicle or the account.",
        'SCAM' => 'Meta flagged the content as a possible scam.',
    ];

    public function __construct(private WhatsAppClient $whatsApp) {}

    /**
     * Send new wording to Meta. A name Meta already has can't be reused for different wording,
     * so a change to an existing template goes up under the next name: _v2, _v3...
     *
     * @param  array<int, array{label: string, example: string}>|null  $variables  custom templates: {{2}} onwards
     */
    public function submit(MessageTemplate $template, string $body, ?array $variables = null): MessageTemplate
    {
        if ($template->status === 'not_checked' && !$this->whatsApp->isFake()) {
            $this->refresh($template);
        }

        $template->fill([
            'draft_name' => $template->status === 'not_submitted' ? $template->name : $this->nextName($template->name),
            'draft_body' => trim($body),
            'draft_variables' => $template->isAutomatic() ? null : $variables,
        ]);

        if ($this->whatsApp->isFake()) {
            $result = ['status' => 'APPROVED', 'reason' => null];
        } else {
            $result = $this->whatsApp->createTemplate(
                $template->draft_name,
                $template->language,
                $template->category,
                $template->draft_body,
                array_column($template->variableList(draft: true), 'example'),
            );
        }

        $template->draft_submitted_at = now();
        $this->applyDraft($template, $result);
        $template->save();

        return $template;
    }

    /**
     * Ask Meta about the template (and any draft) and record what it says.
     */
    public function refresh(MessageTemplate $template): MessageTemplate
    {
        if ($this->whatsApp->isFake()) {
            if ($template->status === 'not_checked') {
                $template->status = 'approved';
            }
            if ($template->draft_status === 'pending') {
                $this->promote($template);
            }
            $template->checked_at = now();
            $template->save();

            return $template;
        }

        $current = $this->whatsApp->findTemplate($template->name, $template->language);

        if (!$current) {
            $template->fill(['status' => 'not_submitted', 'status_reason' => null]);
        } else {
            $template->fill(['status' => $this->status($current['status']), 'status_reason' => $this->reason($current['reason'])]);

            // Meta's wording is what actually gets delivered, e.g. for a template made in WhatsApp Manager
            if ($current['body'] !== null) {
                $template->body = $current['body'];
                $this->checkFits($template);
            }
        }

        if ($template->draft_status === 'pending') {
            $this->applyDraft($template, $this->whatsApp->findTemplate($template->draft_name, $template->language) ?? ['status' => 'PENDING', 'reason' => null]);
        }

        $template->checked_at = now();
        $template->save();

        return $template;
    }

    /**
     * For the scheduler: anything waiting on Meta. Returns how many were checked.
     */
    public function refreshPending(): int
    {
        $count = 0;

        MessageTemplate::where('draft_status', 'pending')->orWhere('status', 'pending')->each(function (MessageTemplate $template) use (&$count) {
            try {
                $this->refresh($template);
                $count++;
            } catch (Throwable $e) {
                Log::warning('[fleet whatsapp] template check failed: '.$e->getMessage(), ['template' => $template->name]);
            }
        });

        return $count;
    }

    public function discardDraft(MessageTemplate $template): MessageTemplate
    {
        $template->fill(['draft_name' => null, 'draft_body' => null, 'draft_variables' => null, 'draft_status' => null, 'draft_reason' => null, 'draft_submitted_at' => null])->save();

        return $template;
    }

    /**
     * @param  array{status: string, reason: ?string}  $result
     */
    private function applyDraft(MessageTemplate $template, array $result): void
    {
        $status = $this->status($result['status']);

        if ($status === 'approved') {
            $this->promote($template);
        } elseif (in_array($status, ['rejected', 'disabled', 'paused'], true)) {
            $template->fill(['draft_status' => 'rejected', 'draft_reason' => $this->reason($result['reason']) ?? 'Meta did not approve it.']);
        } else {
            $template->fill(['draft_status' => 'pending', 'draft_reason' => null]);
        }
    }

    private function promote(MessageTemplate $template): void
    {
        $template->fill([
            'name' => $template->draft_name,
            'body' => $template->draft_body,
            'variables' => $template->isAutomatic() ? null : $template->draft_variables,
            'status' => 'approved',
            'status_reason' => null,
        ]);
        $this->discardDraft($template);
    }

    /**
     * A template made by hand in WhatsApp Manager may not have the blanks this message fills.
     */
    private function checkFits(MessageTemplate $template): void
    {
        preg_match_all('/\{\{(\d+)\}\}/', $template->body, $matches);
        $has = count(array_unique($matches[1]));
        $needs = count($template->variableList());

        if ($has !== $needs) {
            $template->fill([
                'status' => 'mismatch',
                'status_reason' => "The wording in Meta has {$has} blank(s) but this message fills {$needs}, so WhatsApp would refuse to send it.",
            ]);
        }
    }

    private function nextName(string $name): string
    {
        $base = preg_replace('/_v\d+$/', '', $name);
        $version = preg_match('/_v(\d+)$/', $name, $m) ? (int) $m[1] : 1;

        do {
            $candidate = $base.'_v'.++$version;
        } while (MessageTemplate::where('name', $candidate)->orWhere('draft_name', $candidate)->exists());

        return $candidate;
    }

    private function status(string $metaStatus): string
    {
        return self::STATUSES[strtoupper($metaStatus)] ?? 'disabled';
    }

    private function reason(?string $code): ?string
    {
        return $code ? (self::REASONS[$code] ?? $code) : null;
    }
}
