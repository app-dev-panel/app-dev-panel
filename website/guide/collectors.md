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

Captures translation lookups during request execution. Each translation call is recorded as a `TranslationRecord` with the following fields:

| Field | Type | Description |
|-------|------|-------------|
| `category` | `string` | Translation domain/category (e.g. `messages`, `app`, `validation`) |
| `locale` | `string` | Target locale (e.g. `en`, `de`, `fr`) |
| `message` | `string` | Message ID / translation key |
| `translation` | `?string` | Translated text, or `null` if missing |
| `missing` | `bool` | Whether the translation was not found |
| `fallbackLocale` | `?string` | Fallback locale used, if any |

### Collected Data

`getCollected()` returns:

```php
[
    'translations' => [...],  // list of TranslationRecord arrays
    'missingCount' => 1,      // number of missing translations
    'totalCount' => 4,        // total translation lookups
    'locales' => ['en', 'de'], // unique locales used
    'categories' => ['messages'], // unique categories used
]
```

### Summary

`getSummary()` returns `['translator' => ['total' => N, 'missing' => N]]`.

### Automatic Interception

When framework adapter proxies are installed, translation calls are captured automatically — no manual instrumentation needed. See [Proxies](/guide/proxies) for the translator proxy in each framework:

| Framework | Proxy | Intercepted Interface |
|-----------|-------|-----------------------|
| Symfony | `SymfonyTranslatorProxy` | `Symfony\Contracts\Translation\TranslatorInterface` |
| Laravel | `LaravelTranslatorProxy` | `Illuminate\Contracts\Translation\Translator` |
| Yiisoft | `TranslatorInterfaceProxy` | `Yiisoft\Translator\TranslatorInterface` |
| Yii 2 | `I18NProxy` | `yii\i18n\I18N` |
