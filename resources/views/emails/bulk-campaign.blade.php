@component('mail::message')
{{ $body }}

@component('mail::button', ['url' => config('app.url')])
Learn More
@endcomponent

Thanks,<br>
{{ config('app.name') }} Team
@endcomponent
