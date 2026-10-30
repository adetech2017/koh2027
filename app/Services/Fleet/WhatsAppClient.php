<?php

namespace App\Services\Fleet;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
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
     * Log driver: nothing reaches Meta, and templates are treated as approved straight away.
     */
    public function isFake(): bool
    {
        return config('fleet.whatsapp.driver') === 'log';
    }

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
    public function sendTemplate(string $phone, string $template, array $components = [], ?string $language = null): ?string
    {
        $language ??= config('fleet.whatsapp.language');

        if ($this->isFake()) {
            Log::info('[fleet whatsapp] '.$template.' ('.$language.') → '.$phone, ['components' => $components]);

            return null;
        }

        if (!config('fleet.whatsapp.phone_number_id')) {
            throw new RuntimeException('WhatsApp is not configured: set FLEET_WHATSAPP_PHONE_NUMBER_ID.');
        }

        $response = $this->graph()->post(config('fleet.whatsapp.phone_number_id').'/messages', [
            'messaging_product' => 'whatsapp',
            'to' => ltrim($phone, '+'),
            'type' => 'template',
            'template' => [
                'name' => $template,
                'language' => ['code' => $language],
                'components' => $components,
            ],
        ]);

        $this->throwIfFailed($response, 'WhatsApp send failed');

        return $response->json('messages.0.id');
    }

    /**
     * Submit a body-only template for Meta's review.
     *
     * @param  array<int, string>  $examples  Sample values for {{1}}, {{2}}... (Meta requires them)
     * @return array{status: string, reason: ?string} Meta's status, e.g. PENDING or APPROVED
     */
    public function createTemplate(string $name, string $language, string $category, string $body, array $examples): array
    {
        $response = $this->graph()->post($this->businessAccountId().'/message_templates', [
            'name' => $name,
            'language' => $language,
            'category' => $category,
            'components' => [[
                'type' => 'BODY',
                'text' => $body,
                'example' => ['body_text' => [$examples]],
            ]],
        ]);

        $this->throwIfFailed($response, 'Meta did not accept the template');

        return ['status' => (string) $response->json('status', 'PENDING'), 'reason' => null];
    }

    /**
     * A template's status and wording as Meta has it, or null when there's no such template.
     *
     * @return array{status: string, reason: ?string, body: ?string}|null
     */
    public function findTemplate(string $name, string $language): ?array
    {
        $response = $this->graph()->get($this->businessAccountId().'/message_templates', [
            'name' => $name,
            'fields' => 'name,language,status,category,components,rejected_reason',
            'limit' => 100,
        ]);

        $this->throwIfFailed($response, 'Could not check the template with Meta');

        // The name filter also matches partial names, so pick the exact one
        $template = collect($response->json('data', []))
            ->first(fn ($t) => ($t['name'] ?? null) === $name && ($t['language'] ?? null) === $language);

        if (!$template) {
            return null;
        }

        $reason = $template['rejected_reason'] ?? null;

        return [
            'status' => (string) $template['status'],
            'reason' => $reason && $reason !== 'NONE' ? $reason : null,
            'body' => collect($template['components'] ?? [])->firstWhere('type', 'BODY')['text'] ?? null,
        ];
    }

    private function graph(): PendingRequest
    {
        $config = config('fleet.whatsapp');

        if (!$config['access_token']) {
            throw new RuntimeException('WhatsApp is not configured: set FLEET_WHATSAPP_ACCESS_TOKEN.');
        }

        return Http::withToken($config['access_token'])
            ->baseUrl("https://graph.facebook.com/{$config['api_version']}/")
            ->acceptJson()
            ->timeout(15);
    }

    private function businessAccountId(): string
    {
        return config('fleet.whatsapp.business_account_id')
            ?: throw new RuntimeException('Set FLEET_WHATSAPP_BUSINESS_ACCOUNT_ID (WhatsApp Manager → Account tools → Business account ID) to manage templates from here.');
    }

    private function throwIfFailed(Response $response, string $what): void
    {
        if ($response->failed()) {
            // error_user_msg is Meta's human-readable explanation when there is one
            $message = $response->json('error.error_user_msg') ?: $response->json('error.message', $response->body());

            throw new RuntimeException("{$what} ({$response->status()}): {$message}");
        }
    }
}
