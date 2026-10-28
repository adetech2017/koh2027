<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
        <meta name="theme-color" content="#003D82">
        <meta name="robots" content="noindex, nofollow">
        <title>KOH Fleet</title>
        {{-- Lets beneficiaries add the app to their home screen --}}
        <link rel="manifest" href="/fleet/manifest.webmanifest">
        <link rel="icon" type="image/png" href="/fleet/icon-192.png">
        <link rel="apple-touch-icon" href="/fleet/icon-192.png">
        <meta name="apple-mobile-web-app-capable" content="yes">
        @viteReactRefresh
        @vite(['resources/fleet/css/fleet.css', 'resources/fleet/beneficiary/main.jsx'])
    </head>
    <body>
        <div id="root"></div>
    </body>
</html>
