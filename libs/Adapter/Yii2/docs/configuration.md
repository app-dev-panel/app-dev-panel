# Configuration Reference

## Module Configuration

```php
// config/web.php or config/main.php
return [
    'modules' => [
        'debug-panel' => [
            'class' => \AppDevPanel\Adapter\Yii2\Module::class,

            // Master switch (default: true)
            'enabled' => true,

            // Storage directory for debug data (default: @runtime/debug)
            'storagePath' => '@runtime/debug',

            // Maximum debug entries to keep (default: 50)
            'historySize' => 50,

            // Collector toggles (all default to true)
            'collectors' => [
                'request' => true,        // RequestCollector (Kernel, PSR-7)
                'exception' => true,      // ExceptionCollector (Kernel)
                'log' => true,            // LogCollector (Kernel, PSR-3)
                'event' => true,          // EventCollector (Kernel)
                'service' => true,        // ServiceCollector
                'http_client' => true,    // HttpClientCollector
                'timeline' => true,       // TimelineCollector
                'var_dumper' => true,      // VarDumperCollector
                'filesystem_stream' => true, // FilesystemStreamCollector
                'http_stream' => true,    // HttpStreamCollector
                'command' => true,        // CommandCollector
                'db' => true,             // DatabaseCollector (Kernel, fed by DbProfilingTarget)
                'mailer' => true,         // MailerCollector (Kernel, fed by BaseMailer events)
                'assets' => true,         // AssetBundleCollector (Kernel, fed by View events)
            ],

            // URL patterns to skip (wildcard)
            'ignoredRequests' => [
                '/debug/api/**',
                '/inspect/api/**',
                '/assets/**',
            ],

            // Console command patterns to skip (wildcard)
            'ignoredCommands' => [
                'help',
                'list',
                'cache/*',
                'asset/*',
            ],

            // Classes to exclude from object dumps
            'excludedClasses' => [],

            // IP addresses allowed to access the API (default: localhost only)
            'allowedIps' => ['127.0.0.1', '::1'],

            // Authentication token (empty = no auth)
            'authToken' => '',
        ],
    ],
];
```

## Auto-Bootstrap

The adapter auto-registers via composer's `extra.bootstrap`. No manual bootstrap configuration needed.

When `YII_DEBUG` is `true`, the module auto-enables. To explicitly disable:

```php
'modules' => [
    'debug-panel' => [
        'class' => \AppDevPanel\Adapter\Yii2\Module::class,
        'enabled' => false,
    ],
],
```

## Environment-Specific Configuration

```php
// config/web-local.php (gitignored, per-developer)
return [
    'modules' => [
        'debug-panel' => [
            'historySize' => 100,
            'allowedIps' => ['127.0.0.1', '::1', '192.168.1.*'],
        ],
    ],
];
```

## API Endpoints

Once installed, these endpoints are available:

| Path | Description |
|---|---|
| `GET /debug/api/` | API root |
| `GET /debug/api/entries` | List debug entries |
| `GET /debug/api/entry/{id}` | Single debug entry |
| `GET /debug/api/entry/{id}/{collector}` | Collector data for entry |
| `GET /inspect/api/params` | Application parameters |
| `GET /inspect/api/table` | Database tables |
| `GET /inspect/api/table/{name}` | Table records |
