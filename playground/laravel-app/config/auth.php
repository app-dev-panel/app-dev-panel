<?php

declare(strict_types=1);

return [
    'defaults' => [
        'guard' => 'main',
        'passwords' => 'users',
    ],

    'guards' => [
        'main' => [
            'driver' => 'session',
            'provider' => 'users',
        ],
    ],

    'providers' => [
        'users' => [
            'driver' => 'array',
        ],
    ],

    'passwords' => [
        'users' => [
            'provider' => 'users',
            'table' => 'password_reset_tokens',
            'expire' => 60,
            'throttle' => 60,
        ],
    ],

    'password_timeout' => 10800,
];
