<?php

return [
    'owner_id' => (int) env('SITE_OWNER_ID', 1),
    'description' => 'Laravel, PHP, API and full-stack engineering services for reliable, scalable software.',
    'verification' => env('GOOGLE_SITE_VERIFICATION'),
    'tag_manager_id' => env('GOOGLE_TAG_MANAGER_ID'),
    'analytics_id' => env('GOOGLE_ANALYTICS_ID'),
    // AdSense remains optional configuration; no advertising script is loaded.
    'adsense_id' => env('GOOGLE_ADSENSE_ID'),
];
