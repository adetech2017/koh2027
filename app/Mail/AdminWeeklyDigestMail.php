<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class AdminWeeklyDigestMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public int $newContacts,
        public int $newVolunteers,
        public int $newRsvps,
        public int $newSubscribers,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'KOH 2027 Campaign Weekly Digest',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.admin-weekly-digest',
            with: [
                'newContacts' => $this->newContacts,
                'newVolunteers' => $this->newVolunteers,
                'newRsvps' => $this->newRsvps,
                'newSubscribers' => $this->newSubscribers,
            ],
        );
    }
}
