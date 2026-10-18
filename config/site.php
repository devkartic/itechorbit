<?php

return [
    'owner_id' => (int) env('SITE_OWNER_ID', 1),
    'description' => 'Laravel, PHP, API and full-stack engineering services for reliable, scalable software.',
    'verification' => env('GOOGLE_SITE_VERIFICATION', '6ZjlWVJoAFb6SKF0esemqyCpzyk7_gVrQCcYvUJGmAM'),
    // Existing integrations are preserved as optional configuration; enable after consent review.
    'analytics_id' => env('GOOGLE_ANALYTICS_ID'),
    'adsense_id' => env('GOOGLE_ADSENSE_ID'),
];
