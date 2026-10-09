<x-mail::message>
@if ($isTest)
<x-mail::panel>
This is a test. Only you received it.
</x-mail::panel>
@endif

{{ $greeting }}

{!! $body !!}

@if ($ctaText && $ctaUrl)
<x-mail::button :url="$ctaUrl">
{{ $ctaText }}
</x-mail::button>
@endif

Thanks,<br>
{{ config('mail.from.name') }}

<x-mail::subcopy>
@if ($unsubscribeUrl)
You're receiving this because you subscribed to campaign updates. [Unsubscribe]({{ $unsubscribeUrl }})
@else
You're receiving this because you volunteered with the campaign. To stop these emails, reply and let us know.
@endif
</x-mail::subcopy>
</x-mail::message>
