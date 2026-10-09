<?php

namespace App\Http\Controllers;

use App\Http\Requests\ContactFormRequest;
use App\Mail\ContactReceivedMail;
use App\Models\Contact;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Mail;

class ContactController extends Controller
{
    public function store(ContactFormRequest $request): RedirectResponse
    {
        $contact = Contact::create([
            ...$request->validated(),
            'ip_address' => $request->ip(),
        ]);

        // The message is already saved; a mail hiccup shouldn't show the visitor an error page
        try {
            Mail::send(new ContactReceivedMail($contact));
        } catch (\Throwable $e) {
            report($e);
        }

        return back()->with('success', 'Your message has been sent. We will respond within 48 hours.');
    }
}
