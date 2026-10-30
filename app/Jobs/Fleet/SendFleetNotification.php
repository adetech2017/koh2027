<?php

namespace App\Jobs\Fleet;

use App\Models\Fleet\Notification;
use App\Services\Fleet\WhatsAppClient;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Str;

class SendFleetNotification implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    /** Seconds to wait before each retry */
    public array $backoff = [60, 300];

    public function __construct(public int $notificationId) {}

    public function handle(WhatsAppClient $whatsApp): void
    {
        $notification = Notification::with('beneficiary')->find($this->notificationId);
        if (!$notification || $notification->status !== 'pending') {
            return;
        }

        $beneficiary = $notification->beneficiary;
        if ($beneficiary->trashed() || !$beneficiary->isActive()) {
            $notification->update(['status' => 'skipped', 'error' => 'Beneficiary is suspended or removed.']);

            return;
        }

        $messageId = $whatsApp->sendTemplate($beneficiary->phone, $notification->template, [[
            'type' => 'body',
            'parameters' => array_map(fn ($value) => ['type' => 'text', 'text' => (string) $value], $notification->parameters),
        ]], $notification->language);

        $notification->update(['status' => 'sent', 'whatsapp_message_id' => $messageId, 'sent_at' => now(), 'error' => null]);
    }

    public function failed(?\Throwable $e): void
    {
        Notification::whereKey($this->notificationId)->update([
            'status' => 'failed',
            'error' => Str::limit($e?->getMessage() ?? 'Unknown error', 1000),
        ]);
    }
}
