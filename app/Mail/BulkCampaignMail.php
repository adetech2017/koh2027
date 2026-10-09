<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Mail\Mailables\Headers;
use Illuminate\Queue\SerializesModels;

class BulkCampaignMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * @param  array{subject: string, body: string, cta_text: ?string, cta_url: ?string}  $content
     * @param  array{email: string, first_name: ?string, unsubscribe_url: ?string, is_test?: bool}  $recipient
     */
    public function __construct(
        public array $content,
        public array $recipient,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            to: [$this->recipient['email']],
            subject: $this->personalise($this->content['subject']),
        );
    }

    // One-click unsubscribe header: mail apps show an "Unsubscribe" button, and bulk senders are expected to include it
    public function headers(): Headers
    {
        return new Headers(text: array_filter([
            'List-Unsubscribe' => $this->recipient['unsubscribe_url'] ? "<{$this->recipient['unsubscribe_url']}>" : null,
        ]));
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.bulk-campaign',
            with: [
                'greeting' => $this->recipient['first_name'] ? "Hi {$this->recipient['first_name']}," : 'Hello,',
                'body' => $this->formatBody($this->personalise($this->content['body'])),
                'ctaText' => $this->content['cta_text'] ?? null,
                'ctaUrl' => $this->content['cta_url'] ?? null,
                'unsubscribeUrl' => $this->recipient['unsubscribe_url'],
                'isTest' => $this->recipient['is_test'] ?? false,
            ],
        );
    }

    private function personalise(string $text): string
    {
        return str_replace('{first_name}', $this->recipient['first_name'] ?: 'friend', $text);
    }

    /**
     * Escape any HTML the author typed, keep simple Markdown (**bold**, [links](https://…)),
     * and keep single line breaks, which Markdown would otherwise merge into one paragraph.
     */
    private function formatBody(string $body): string
    {
        $escaped = e(str_replace("\r\n", "\n", trim($body)));

        return preg_replace('/(?<!\n)\n(?!\n)/', "  \n", $escaped);
    }
}
