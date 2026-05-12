<?php

namespace App\Mail;

use App\Models\Volunteer;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class VolunteerWelcomeMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public readonly Volunteer $volunteer)
    {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            to: new Address($this->volunteer->email, $this->volunteer->full_name),
            subject: 'Welcome to the KOH 2027 Volunteer Team!',
        );
    }

    public function content(): Content
    {
        return new Content(view: 'mail.volunteer-welcome');
    }
}
