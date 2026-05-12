<?php

namespace App\Mail;

use App\Models\Contact;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ContactReceivedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public readonly Contact $contact)
    {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            to: [
                new Address(config('mail.campaign_address', 'campaign@kohforlakosstate.com'), 'KOH Campaign'),
            ],
            subject: 'New Contact: ' . $this->contact->subject,
        );
    }

    public function content(): Content
    {
        return new Content(view: 'mail.contact-received');
    }
}
