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

    // Handover photos, signatures and (later) evaluation videos. Private: served only through
    // the API to signed-in admins. Point FLEET_DISK at an S3/Spaces disk to move off the server.
    'disk' => env('FLEET_DISK', 'fleet'),

    'lgas' => [
        'Agege', 'Ajeromi-Ifelodun', 'Alimosho', 'Amuwo-Odofin', 'Apapa', 'Badagry', 'Epe',
        'Eti-Osa', 'Ibeju-Lekki', 'Ifako-Ijaiye', 'Ikeja', 'Ikorodu', 'Kosofe', 'Lagos Island',
        'Lagos Mainland', 'Mushin', 'Ojo', 'Oshodi-Isolo', 'Shomolu', 'Surulere',
    ],

    'vehicle_types' => [
        'car' => 'Car',
        'suv' => 'SUV',
        'bus' => 'Bus',
        'pickup' => 'Pickup',
        'motorcycle' => 'Motorcycle',
        'tricycle' => 'Tricycle (keke)',
    ],

    'id_types' => [
        'nin' => 'NIN',
        'voters_card' => "Voter's card",
        'drivers_licence' => "Driver's licence",
        'passport' => 'International passport',
    ],

    'fuel_levels' => [
        'empty' => 'Empty',
        'quarter' => '1/4',
        'half' => '1/2',
        'three_quarters' => '3/4',
        'full' => 'Full',
    ],

    // Checked at handover and again at return
    'handover_checklist' => [
        'body' => 'Body and paintwork',
        'glass' => 'Windscreen, windows and mirrors',
        'lights' => 'Lights and indicators',
        'tyres' => 'Tyres (including spare)',
        'interior' => 'Seats and interior',
        'branding' => 'Campaign branding intact',
        'tools' => 'Jack and wheel spanner',
        'extinguisher' => 'Fire extinguisher',
        'documents' => 'Vehicle papers',
    ],

    'evaluations' => [
        // Each assignment is split into back-to-back cycles from the handover date; one video per cycle
        'cycle_days' => (int) env('FLEET_EVALUATION_CYCLE_DAYS', 14),
        // Asked for 60–90 seconds; a little slack either side so a good video isn't refused
        'min_seconds' => 20,
        'max_seconds' => 180,
        'max_upload_mb' => 250,
        // Uploads go in pieces so a dropped mobile connection resumes instead of starting over
        'chunk_bytes' => 2 * 1024 * 1024,
        // How long after the on-screen code is issued the upload must start
        'challenge_minutes' => 20,
        // Device GPS and the video's own GPS further apart than this are flagged
        'location_mismatch_km' => 5,
        // Rough box around Lagos State; locations outside it are flagged for a reviewer
        'region' => ['min_lat' => 6.35, 'max_lat' => 6.75, 'min_lng' => 2.65, 'max_lng' => 4.40],
    ],

    'ffmpeg' => env('FLEET_FFMPEG', 'ffmpeg'),
    'ffprobe' => env('FLEET_FFPROBE', 'ffprobe'),

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
