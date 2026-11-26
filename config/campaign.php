<?php

// Public contact details shown on the website (footer, contact page). Set these in .env.
return [
    'contact_email' => env('CAMPAIGN_CONTACT_EMAIL', 'campaign@koh2027.ng'),
    // Hidden on the site until a real number is set
    'contact_phone' => env('CAMPAIGN_CONTACT_PHONE'),
    'address' => env('CAMPAIGN_ADDRESS', 'Campaign Office, GRA, Ikeja, Lagos'),

    // Election day countdowns: when polls open, in Lagos time (WAT, UTC+1).
    // An election is dropped from the site once its day is over; leave a date empty to hide it.
    'elections' => [
        [
            'key' => 'presidential',
            'label' => 'Presidential Election',
            'starts_at' => env('CAMPAIGN_PRESIDENTIAL_ELECTION_AT', '2027-01-16 08:30'),
        ],
        [
            'key' => 'governorship',
            'label' => 'Governorship Election',
            'starts_at' => env('CAMPAIGN_GOVERNORSHIP_ELECTION_AT', '2027-02-06 08:30'),
        ],
    ],
];
