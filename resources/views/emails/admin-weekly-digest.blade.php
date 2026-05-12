@component('mail::message')
# Weekly CRM Digest

Hello,

Here's your weekly summary of KOH 2027 campaign activity:

## New Registrations (Past 7 Days)

- **Volunteers:** {{ $newVolunteers }}
- **Contacts:** {{ $newContacts }}
- **Newsletter Subscribers:** {{ $newSubscribers }}
- **Event RSVPs:** {{ $newRsvps }}

@component('mail::button', ['url' => config('app.url') . '/admin'])
View Dashboard
@endcomponent

Keep up the great work!<br>
{{ config('app.name') }} Team
@endcomponent
