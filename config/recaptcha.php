<?php

return [
    'enabled' => (bool) env('RECAPTCHA_ENABLED', false),
    'version' => env('RECAPTCHA_VERSION', 'v2'),
    'minimum_score' => (float) env('RECAPTCHA_MINIMUM_SCORE', 0.5),
    'site_key' => env('RECAPTCHA_SITE_KEY', ''),
    'secret_key' => env('RECAPTCHA_SECRET_KEY', ''),
    'hostnames' => array_values(array_filter(array_map('trim', explode(',', ((string) env('RECAPTCHA_ALLOWED_HOSTNAMES') ?: (parse_url(env('APP_URL', 'http://localhost'), PHP_URL_HOST) ?: 'localhost')))))),
];
