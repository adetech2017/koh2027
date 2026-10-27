<?php

namespace App\Services\Fleet;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

/**
 * Sends template messages through Meta's WhatsApp Cloud API. Business-initiated messages
 * must use a template Meta has approved; free-form text only works inside a 24-hour
 * window after the user messages us, which login codes and reminders can't rely on.
 *
 * With FLEET_WHATSAPP_DRIVER=log (the default), messages are written to the log instead.
 */
class WhatsAppClient
{
    /**
     * Send a login code using an "Authentication" template. Meta requires the code in the
     * body and again as the copy-code button's parameter.
     */
    public function sendOtp(string $phone, string $code): void
    {
        $this->sendTemplate($phone, config('fleet.whatsapp.templates.otp'), [
            [
                'type' => 'body',
                'parameters' => [['type' => 'text', 'text' => $code]],
            ],
            [
                'type' => 'button',
                'sub_type' => 'url',
                'index' => '0',
                'parameters' => [['type' => 'text', 'text' => $code]],
            ],
        ]);
    }

    /**
     * @param  array<int, array<string, mixed>>  $components
     * @return string|null The WhatsApp message id
     */
    public function sendTemplate(string $phone, string $template, array $components = []): ?string
    {
        $config = config('fleet.whatsapp');

        if ($config['driver'] === 'log') {
            Log::info('[fleet whatsapp] '.$template.' → '.$phone, ['components' => $components]);

            return null;
        }

        if (!$config['phone_number_id'] || !$config['access_token']) {
            throw new RuntimeException('WhatsApp is not configured: set FLEET_WHATSAPP_PHONE_NUMBER_ID and FLEET_WHATSAPP_ACCESS_TOKEN.');
        }

        $response = Http::withToken($config['access_token'])
            ->timeout(15)
            ->post("https://graph.facebook.com/{$config['api_version']}/{$config['phone_number_id']}/messages", [
                'messaging_product' => 'whatsapp',
                'to' => ltrim($phone, '+'),
                'type' => 'template',
                'template' => [
                    'name' => $template,
                    'language' => ['code' => $config['language']],
                    'components' => $components,
                ],
            ]);

        if ($response->failed()) {
            throw new RuntimeException('WhatsApp send failed ('.$response->status().'): '.$response->json('error.message', $response->body()));
        }

        return $response->json('messages.0.id');
    }
}
