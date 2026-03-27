<?php

return [
    'paths' => [
        'api/*',
        'sanctum/csrf-cookie',
    ],

    'allowed_methods' => ['*'],

    'allowed_origins' => array_values(array_unique(array_filter(array_merge(
        [
            'http://localhost',
            'http://localhost:3000',
            'http://localhost:3001',
            'http://127.0.0.1',
            'http://127.0.0.1:3000',
            'http://127.0.0.1:3001',
            'https://platform.driip.io',
        ],
        array_map(
            'trim',
            explode(',', (string) env('CORS_ALLOWED_ORIGINS', ''))
        )
    )))),

    'allowed_origins_patterns' => [],

    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    'max_age' => 0,

    'supports_credentials' => true,
];
