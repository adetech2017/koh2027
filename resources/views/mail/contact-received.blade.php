<x-mail::message>
# New Contact Submission

You have received a new contact form submission.

**Name:** {{ $contact->name }}

**Email:** {{ $contact->email }}

**Phone:** {{ $contact->phone ?? 'Not provided' }}

**Subject:** {{ $contact->subject }}

**Message:**

{{ $contact->message }}

---

**Received at:** {{ $contact->created_at->format('F j, Y \a\t g:i A') }}

</x-mail::message>
