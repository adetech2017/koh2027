<?php

namespace App\Mail;

use App\Models\NewsletterSubscriber;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class NewsletterConfirmationMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public readonly NewsletterSubscriber $subscriber)
    {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            to: new Address($this->subscriber->email, $this->subscriber->name ?? ''),
            subject: 'Confirm Your Subscription — KOH Campaign 2027',
        );
    }

    public function content(): Content
    {
        return new Content(view: 'mail.newsletter-confirmation');
    }
}
