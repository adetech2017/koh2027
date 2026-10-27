<?php

return [

    /*
    | The fleet app (campaign vehicle monitoring) is served from its own subdomain.
    | Locally, fleet.localhost resolves to 127.0.0.1 in every modern browser, so
    | `php artisan serve` answers it on the same port with no hosts-file changes.
    */
    'domain' => env('FLEET_DOMAIN', 'fleet.localhost'),

    // Kept separate from the main site's session cookie so the two never share a login
    'session_cookie' => env('FLEET_SESSION_COOKIE', 'koh_fleet_session'),

    'otp' => [
        'length' => 6,
        'ttl_minutes' => 10,
        'max_attempts' => 5,
    ],

    'whatsapp' => [
        // "cloud" sends through Meta's WhatsApp Cloud API; "log" writes messages to the log (local dev)
        'driver' => env('FLEET_WHATSAPP_DRIVER', 'log'),
        'api_version' => env('FLEET_WHATSAPP_API_VERSION', 'v21.0'),
        'phone_number_id' => env('FLEET_WHATSAPP_PHONE_NUMBER_ID'),
        'access_token' => env('FLEET_WHATSAPP_ACCESS_TOKEN'),
        'templates' => [
            // An approved "Authentication" category template with a copy-code button
            'otp' => env('FLEET_WHATSAPP_OTP_TEMPLATE', 'fleet_login_code'),
        ],
        'language' => env('FLEET_WHATSAPP_LANGUAGE', 'en'),
    ],

];
