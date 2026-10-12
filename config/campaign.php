<?php

// Public contact details shown on the website (footer, contact page). Set these in .env.
return [
    'contact_email' => env('CAMPAIGN_CONTACT_EMAIL', 'campaign@koh2027.ng'),
    // Hidden on the site until a real number is set
    'contact_phone' => env('CAMPAIGN_CONTACT_PHONE'),
    'address' => env('CAMPAIGN_ADDRESS', 'Campaign Office, GRA, Ikeja, Lagos'),
];
