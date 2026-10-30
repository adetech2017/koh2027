<?php

namespace App\Mail;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Symfony\Component\Mailer\Envelope;
use Symfony\Component\Mailer\Exception\TransportException;
use Symfony\Component\Mailer\SentMessage;
use Symfony\Component\Mailer\Transport\AbstractTransport;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;

/**
 * Sends mail through Microsoft 365 with the Graph API, for tenants where SMTP password sign-in is retired.
 *
 * Needs an Entra ID app registration with the Mail.Send application permission. The message goes to
 * Graph as raw MIME, so HTML, attachments and headers arrive exactly as Laravel built them.
 */
class MicrosoftGraphTransport extends AbstractTransport
{
    public function __construct(
        private string $tenantId,
        private string $clientId,
        private string $clientSecret,
    ) {
        parent::__construct();
    }

    protected function doSend(SentMessage $message): void
    {
        $sender = $message->getEnvelope()->getSender()->getAddress();

        $response = Http::withToken($this->accessToken())
            ->withBody(base64_encode($this->mime($message)), 'text/plain')
            ->timeout(30)
            ->post('https://graph.microsoft.com/v1.0/users/'.rawurlencode($sender).'/sendMail');

        if (!$response->successful()) {
            throw new TransportException(sprintf(
                'Microsoft Graph refused the message from %s (HTTP %d): %s',
                $sender, $response->status(), $response->json('error.message') ?? $response->body(),
            ));
        }
    }

    /**
     * Symfony strips the Bcc header from the rendered message (SMTP sends it through the envelope instead),
     * but Graph reads recipients from the MIME headers, so blind copies are put back in for Graph to deliver.
     */
    private function mime(SentMessage $message): string
    {
        $mime = $message->toString();
        $original = $message->getOriginalMessage();

        if ($original instanceof Email && $original->getBcc()) {
            $bcc = implode(', ', array_map(fn (Address $address) => $address->toString(), $original->getBcc()));
            $mime = "Bcc: {$bcc}\r\n".$mime;
        }

        return $mime;
    }

    // App-only tokens last about an hour; reuse one until shortly before it expires
    private function accessToken(): string
    {
        $key = 'microsoft-graph-mail-token:'.$this->clientId;

        if ($token = Cache::get($key)) {
            return $token;
        }

        $response = Http::asForm()->timeout(15)->post("https://login.microsoftonline.com/{$this->tenantId}/oauth2/v2.0/token", [
            'client_id' => $this->clientId,
            'client_secret' => $this->clientSecret,
            'scope' => 'https://graph.microsoft.com/.default',
            'grant_type' => 'client_credentials',
        ]);

        if (!$response->successful() || !$response->json('access_token')) {
            throw new TransportException('Could not sign in to Microsoft Graph: '.($response->json('error_description') ?? $response->body()));
        }

        Cache::put($key, $response->json('access_token'), max(60, (int) $response->json('expires_in', 3600) - 300));

        return $response->json('access_token');
    }

    public function __toString(): string
    {
        return 'microsoft-graph';
    }
}
