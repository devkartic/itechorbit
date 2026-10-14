<?php

return [
    'ssr' => [
        'enabled' => (bool) env('INERTIA_SSR_ENABLED', true),
        'runtime' => 'node', 'url' => env('INERTIA_SSR_URL', 'http://127.0.0.1:13714'),
        'ensure_bundle_exists' => true,
        'throw_on_error' => (bool) env('INERTIA_SSR_THROW_ON_ERROR', env('APP_ENV') === 'production'),
    ],
    'pages' => ['ensure_pages_exist' => true, 'paths' => [resource_path('js/pages')], 'extensions' => ['tsx']],
    'testing' => ['ensure_pages_exist' => true],
    // Web Crypto requires HTTPS (or a browser-trusted loopback origin).
    // Keep production privacy protection; custom HTTP development hosts cannot encrypt.
    'history' => ['encrypt' => (bool) env('INERTIA_HISTORY_ENCRYPT', env('APP_ENV') === 'production')],
];
