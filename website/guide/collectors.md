---
title: Collectors
---

# Collectors

Collectors are the core data-gathering mechanism in ADP. Each collector implements `CollectorInterface` and is responsible for capturing a specific type of runtime data during the application lifecycle.

## Built-in Collectors

| Collector | Data Collected |
|-----------|---------------|
| `LogCollector` | PSR-3 log messages (level, message, context) |
| `EventCollector` | PSR-14 dispatched events and listeners |
| `HttpClientCollector` | PSR-18 outgoing HTTP requests and responses |
| `DatabaseCollector` | SQL queries, execution time, transactions |
| `ExceptionCollector` | Uncaught exceptions with stack traces |
| `RequestCollector` | Incoming HTTP request and response details |
| `ServiceCollector` | DI container service resolutions |
| `AssetBundleCollector` | Registered frontend asset bundles |
| `CommandCollector` | Console command executions |
| `CacheCollector` | Cache get/set/delete operations |
| `MailerCollector` | Sent email messages |
| `TimelineCollector` | Performance timeline events |
| `TranslatorCollector` | Translation lookups, missing translations |
| `ValidatorCollector` | Validation calls and results |
| `EnvironmentCollector` | PHP and OS environment info |

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
