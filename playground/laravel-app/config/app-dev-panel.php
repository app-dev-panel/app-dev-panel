<?php

declare(strict_types=1);

return [
    'enabled' => true,

    'storage' => [
        'path' => storage_path('debug'),
        'history_size' => 50,
    ],

    'collectors' => [
        'environment' => true,
        'request' => true,
        'exception' => true,
        'log' => true,
        'event' => true,
        'service' => true,
        'http_client' => true,
        'timeline' => true,
        'var_dumper' => true,
        'filesystem_stream' => true,
        'http_stream' => true,
        'command' => true,
        'database' => true,
        'cache' => true,
        'mailer' => true,
        'queue' => true,
        'validator' => true,
        'router' => true,
    ],

    'ignored_requests' => [
        '/debug/api/**',
        '/inspect/api/**',
    ],

    'ignored_commands' => [
        'completion',
        'help',
        'list',
        'debug:*',
        'cache:*',
    ],

    'dumper' => [
        'excluded_classes' => [],
    ],

    'api' => [
        'enabled' => true,
        'allowed_ips' => ['127.0.0.1', '::1'],
        'auth_token' => '',
    ],
];
