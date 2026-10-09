<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <title inertia>{{ config('app.name') }}</title>

        {{--
            Link previews (WhatsApp, Facebook, X, LinkedIn) are built from the server's HTML;
            those crawlers don't run JavaScript, so these can't come from Vue's <Head>.
            Pages can pass their own via Inertia::render(...)->withViewData(['meta' => [...]]).
        --}}
        @php
            $meta = array_merge([
                'title' => 'Kadri Obafemi Hamzat for Lagos 2027',
                'description' => 'The official campaign of Kadri Obafemi Hamzat (KOH) for Governor of Lagos State. Read The Lagos Promise manifesto, find events near you and join the movement.',
                'image' => null,
            ], $meta ?? []);
        @endphp
        <meta name="description" content="{{ $meta['description'] }}">
        <link rel="canonical" href="{{ url()->current() }}">
        <meta property="og:type" content="website">
        <meta property="og:site_name" content="KOH 2027">
        <meta property="og:title" content="{{ $meta['title'] }}">
        <meta property="og:description" content="{{ $meta['description'] }}">
        <meta property="og:url" content="{{ url()->current() }}">
        @if ($meta['image'])
            <meta property="og:image" content="{{ $meta['image'] }}">
        @endif
        <meta name="twitter:card" content="{{ $meta['image'] ? 'summary_large_image' : 'summary' }}">
        <meta name="twitter:site" content="@OfficialKOH2027">
        <meta name="theme-color" content="#003D82">

        @vite(['resources/css/app.css', 'resources/js/app.js'])
        @inertiaHead
    </head>
    <body>
        @inertia
    </body>
</html>
