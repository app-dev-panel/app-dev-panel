<?php

declare(strict_types=1);

return [
    'enabled' => (bool) env('APP_DEV_PANEL_ENABLED', env('APP_DEBUG', true)),

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
        'deprecation' => true,
        'filesystem_stream' => true,
        'http_stream' => true,
        'command' => true,
        'database' => true,
        'cache' => true,
        'mailer' => true,
        'queue' => true,
        'validator' => true,
        'router' => true,
        'opentelemetry' => true,
        'translator' => true,
        'code_coverage' => false,
    ],

    'ignored_requests' => [
        '/debug/**',
        '/inspect/**',
        '/telescope/**',
    ],

    'ignored_commands' => [
        'completion',
        'help',
        'list',
        'debug:*',
        'cache:*',
        'config:*',
        'schedule:*',
    ],

    'dumper' => [
        'excluded_classes' => [],
    ],

    /*
     * Remote-to-local path mapping for Docker/Vagrant environments.
     * Keys are remote (container) prefixes, values are local (host) prefixes.
     * Example: ['/app' => '/home/user/project']
     */
    'path_mapping' => [],

    'panel' => [
        'static_url' => '',  // Base URL for panel assets (empty = GitHub Pages default). Use http://localhost:3000 for Vite dev with HMR.
    ],

    'api' => [
        'enabled' => true,
        'allowed_ips' => ['127.0.0.1', '::1'],
        'auth_token' => env('APP_DEV_PANEL_TOKEN', ''),
    ],
];
