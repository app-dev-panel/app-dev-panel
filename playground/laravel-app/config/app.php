<?php

declare(strict_types=1);

return [
    'name' => env('APP_NAME', 'ADP Laravel Playground'),
    'env' => env('APP_ENV', 'local'),
    'debug' => (bool) env('APP_DEBUG', true),
    'url' => env('APP_URL', 'http://localhost:8104'),
    'timezone' => 'UTC',
    'locale' => 'en',
    'fallback_locale' => 'en',
    'faker_locale' => 'en_US',
    'cipher' => 'AES-256-CBC',
    'key' => env('APP_KEY', 'base64:9eCnptXneX4SrBQ/Y32GNFCKvgj5BiZ7LIRhvupV/Xs='),
    'maintenance' => [
        'driver' => 'file',
    ],
];
