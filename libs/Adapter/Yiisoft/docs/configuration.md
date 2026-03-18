# Yii Adapter Configuration

## Overview

The Yii adapter uses Yii 3's config plugin system for zero-config installation.
All settings are in `params.php` under the `app-dev-panel/yiisoft` key.

## Full Configuration Reference

```php
'app-dev-panel/yiisoft' => [
    // Master switch to enable/disable the entire debugger
    'enabled' => true,

    // Collectors active in all contexts (web + console)
    'collectors.common' => [
        LogCollector::class,
        EventCollector::class,
        ServiceCollector::class,
        HttpClientCollector::class,
        FilesystemStreamCollector::class,
        HttpStreamCollector::class,
        ExceptionCollector::class,
        VarDumperCollector::class,
        TimelineCollector::class,
    ],

    // Additional collectors for web requests only
    'collectors.web' => [
        RequestCollector::class,
        WebAppInfoCollector::class,
    ],

    // Additional collectors for console commands only
    'collectors.console' => [
        CommandCollector::class,
        ConsoleAppInfoCollector::class,
    ],

    // Services to intercept with ServiceProxy
    // Format: 'ServiceClass' => ['method1', 'method2']
    'trackedServices' => [],

    // URL patterns to ignore (regex)
    // Requests matching these patterns won't generate debug entries
    'ignoredRequests' => [],

    // Console command patterns to ignore
    'ignoredCommands' => [],

    // Object serialization settings
    'dumper' => [
        // Classes to skip during serialization
        'excludedClasses' => [],
    ],

    // Logging verbosity per namespace
    'logLevel' => [
        'AppDevPanel\\' => 0,  // Don't log ADP's own calls
    ],

    // Storage configuration
    'storage' => [
        'path' => '@runtime/debug',  // Uses Yii aliases
        'historySize' => 50,         // Max entries before GC
        'exclude' => [],             // Collectors to exclude from storage
    ],
],
```

## API Configuration

```php
'app-dev-panel/yiisoft-api' => [
    'enabled' => true,
    'allowedIPs' => ['127.0.0.1', '::1'],
    'allowedHosts' => [],
    'middlewares' => [DebugHeaders::class],
    'inspector' => [
        'commandMap' => [
            'tests' => [
                PHPUnitCommand::class,
                CodeceptionCommand::class,
            ],
            'analyse' => [
                PsalmCommand::COMMAND_NAME => PsalmCommand::class,
            ],
        ],
    ],
],
```

## DI Configuration Files

| File | Context | Purpose |
|------|---------|---------|
| `di.php` | Common | Storage, Debugger, proxy configs, collector definitions |
| `di-web.php` | Web | Web-specific collectors, RequestCollector config |
| `di-console.php` | Console | Console-specific collectors |
| `di-providers.php` | Common | Registers DebugServiceProvider |

## Event Configuration Files

| File | Context | Mapped Events |
|------|---------|---------------|
| `events-web.php` | Web | ApplicationStartup, BeforeRequest, AfterRequest, AfterEmit, ApplicationError |
| `events-console.php` | Console | ApplicationStartup, ApplicationShutdown, ConsoleCommandEvent, ConsoleErrorEvent, ConsoleTerminateEvent |

## Overriding Configuration

In your application's `params.php`, merge your overrides:

```php
return [
    'app-dev-panel/yiisoft' => [
        'storage' => [
            'historySize' => 100,  // Keep more entries
        ],
        'ignoredRequests' => [
            '/health',             // Don't debug health checks
            '/metrics',
        ],
    ],
];
```
