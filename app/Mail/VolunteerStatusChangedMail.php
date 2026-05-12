<?php

namespace App\Mail;

use App\Models\Volunteer;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class VolunteerStatusChangedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Volunteer $volunteer,
        public string $newStatus,
    ) {}

    public function envelope(): Envelope
    {
        $statusLabel = match ($this->newStatus) {
            'approved' => 'approved as a volunteer',
            'active' => 'activated as a volunteer',
            default => $this->newStatus,
        };

        return new Envelope(
            subject: "Your KOH 2027 Campaign Volunteer Status: {$statusLabel}",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.volunteer-status-changed',
            with: [
                'volunteer' => $this->volunteer,
                'newStatus' => $this->newStatus,
            ],
        );
    }
}
