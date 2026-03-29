---
title: Collectors
---

# Collectors

Collectors are the core data-gathering mechanism in ADP. Each collector implements `CollectorInterface` and is responsible for capturing a specific type of runtime data during the application lifecycle.

## Built-in Collectors

### Core Collectors

| Collector | Data Collected |
|-----------|---------------|
| `LogCollector` | PSR-3 log messages (level, message, context) |
| `EventCollector` | PSR-14 dispatched events and listeners |
| `ExceptionCollector` | Uncaught exceptions with stack traces |
| `HttpClientCollector` | PSR-18 outgoing HTTP requests and responses |
| `DatabaseCollector` | SQL queries, execution time, transactions |
| `ElasticsearchCollector` | Elasticsearch requests, timing, hits count |
| `CacheCollector` | Cache get/set/delete operations with hit/miss rates |
| [`RedisCollector`](/guide/redis) | Redis commands with timing and error tracking |
| `MailerCollector` | Sent email messages |
| `TranslatorCollector` | Translation lookups, missing translations |
| `QueueCollector` | Message queue operations (push, consume, fail) |
| `ServiceCollector` | DI container service resolutions |
| `RouterCollector` | HTTP route matching data |
| `MiddlewareCollector` | Middleware stack execution and timing |
| `ValidatorCollector` | Validation operations and results |
| `SecurityCollector` | Authentication and authorization data |
| `TemplateCollector` | Template rendering (Twig, Blade, etc.) |
| `ViewCollector` | View rendering with captured output |
| `VarDumperCollector` | Manual `dump()` / `dd()` calls |
| `TimelineCollector` | Cross-collector performance timeline |
| `EnvironmentCollector` | PHP and OS environment info |
| `DeprecationCollector` | PHP deprecation warnings |
| `OpenTelemetryCollector` | OpenTelemetry spans and traces |
| `AssetBundleCollector` | Frontend asset bundles (Yii) |
| `FilesystemStreamCollector` | Filesystem stream operations |
| `HttpStreamCollector` | HTTP stream wrapper operations |
| `CodeCoverageCollector` | Per-request PHP line coverage (requires pcov or xdebug) |

### Web-Specific

| Collector | Data Collected |
|-----------|---------------|
| `RequestCollector` | Incoming HTTP request and response details |
| `WebAppInfoCollector` | PHP version, memory, execution time |

### Console-Specific

| Collector | Data Collected |
|-----------|---------------|
| `CommandCollector` | Console command executions |
| `ConsoleAppInfoCollector` | Console application metadata |

## CollectorInterface

Every collector implements five methods:

```php
interface CollectorInterface
{
    public function getId(): string;       // Unique ID (typically FQCN)
    public function getName(): string;     // Human-readable short name
    public function startup(): void;       // Called at request start
    public function shutdown(): void;      // Called at request end
    public function getCollected(): array; // Returns all collected data
}
```

The `Debugger` calls `startup()` on all registered collectors at the beginning of a request, and `shutdown()` followed by `getCollected()` at the end.

## Creating a Custom Collector

```php
<?php

declare(strict_types=1);

namespace App\Debug;

use AppDevPanel\Kernel\Collector\CollectorInterface;

final class MetricsCollector implements CollectorInterface
{
    private array $metrics = [];

    public function getId(): string
    {
        return self::class;
    }

    public function getName(): string
    {
        return 'metrics';
    }

    public function startup(): void
    {
        $this->metrics = [];
    }

    public function shutdown(): void
    {
        // Finalize data if needed
    }

    public function getCollected(): array
    {
        return $this->metrics;
    }

    public function record(string $name, float $value): void
    {
        $this->metrics[] = ['name' => $name, 'value' => $value];
    }
}
```

Register the collector in your framework adapter's DI configuration so the `Debugger` picks it up automatically.

## Data Flow

Collectors receive data in two ways:

1. **Via proxies** -- PSR interface proxies (e.g., `LoggerInterfaceProxy`) intercept calls and feed data to their paired collector automatically.
2. **Via direct calls** -- Adapter hooks or application code call methods on the collector directly (e.g., `DatabaseCollector` receives query data from framework-specific database hooks).

## SummaryCollectorInterface

Collectors can also implement `SummaryCollectorInterface` to provide summary data displayed in the debug entry list without loading full collector data.

## TranslatorCollector

Captures translation lookups during request execution, including missing translation detection. Implements `SummaryCollectorInterface`.

See the dedicated [Translator](/guide/translator) page for full details: TranslationRecord fields, collected data structure, missing detection logic, framework proxy integrations, and configuration examples.
