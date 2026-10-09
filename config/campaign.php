<?php

// Public contact details shown on the website (footer, contact page). Set these in .env.
return [
    'contact_email' => env('CAMPAIGN_CONTACT_EMAIL', 'campaign@koh2027.ng'),
    // Hidden on the site until a real number is set
    'contact_phone' => env('CAMPAIGN_CONTACT_PHONE'),
    'volunteer_url' => env('CAMPAIGN_VOLUNTEER_URL', 'https://hamzatforlagos.com/volunteer'),
    'voter_registration_url' => env('CAMPAIGN_VOTER_REGISTRATION_URL', 'https://hamzatforlagos.com/register-voter'),
];
