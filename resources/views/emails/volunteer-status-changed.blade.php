@component('mail::message')
# Volunteer Status Updated

Hello {{ $volunteer->name }},

Your volunteer status has been updated to **{{ ucfirst($status) }}**.

@if($status === 'approved' || $status === 'active')
Thank you for applying! We're excited to have you as an active volunteer with the KOH 2027 campaign. You can now access volunteer opportunities and events.
@elseif($status === 'rejected')
Thank you for your interest. Unfortunately, your application could not be approved at this time. Please feel free to apply again in the future.
@else
Your volunteer status has been updated. If you have any questions, please contact our team.
@endif

@component('mail::button', ['url' => config('app.url')])
View Profile
@endcomponent

Thanks,<br>
{{ config('app.name') }} Team
@endcomponent
