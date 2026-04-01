---
title: Collectors
---

# Collectors

Collectors are the core data-gathering mechanism in ADP. Each collector implements `CollectorInterface` and is responsible for capturing a specific type of runtime data during the application lifecycle.

## Built-in Collectors

### Core Collectors

| Collector | Data Collected |
|-----------|---------------|
| [`LogCollector`](/guide/collectors/log) | PSR-3 log messages (level, message, context) |
| [`EventCollector`](/guide/collectors/event) | PSR-14 dispatched events and listeners |
| [`ExceptionCollector`](/guide/collectors/exception) | Uncaught exceptions with stack traces |
| [`HttpClientCollector`](/guide/collectors/http-client) | PSR-18 outgoing HTTP requests and responses |
| [`DatabaseCollector`](/guide/collectors/database) | SQL queries, execution time, transactions |
| [`ElasticsearchCollector`](/guide/collectors/elasticsearch) | Elasticsearch requests, timing, hits count |
| [`CacheCollector`](/guide/collectors/cache) | Cache get/set/delete operations with hit/miss rates |
| [`RedisCollector`](/guide/collectors/redis) | Redis commands with timing and error tracking |
| [`MailerCollector`](/guide/collectors/mailer) | Sent email messages |
| [`TranslatorCollector`](/guide/collectors/translator) | Translation lookups, missing translations |
| [`QueueCollector`](/guide/collectors/queue) | Message queue operations (push, consume, fail) |
| [`ServiceCollector`](/guide/collectors/service) | DI container service resolutions |
| [`RouterCollector`](/guide/collectors/router) | HTTP route matching data |
| [`MiddlewareCollector`](/guide/collectors/middleware) | Middleware stack execution and timing |
| [`ValidatorCollector`](/guide/collectors/validator) | Validation operations and results |
| [`AuthorizationCollector`](/guide/collectors/authorization) | Authentication and authorization data |
| [`TemplateCollector`](/guide/collectors/template) | Template/view rendering with timing, output capture, and duplicate detection |
| [`VarDumperCollector`](/guide/collectors/var-dumper) | Manual `dump()` / `dd()` calls |
| [`TimelineCollector`](/guide/collectors/timeline) | Cross-collector performance timeline |
| [`EnvironmentCollector`](/guide/collectors/environment) | PHP and OS environment info |
| [`DeprecationCollector`](/guide/collectors/deprecation) | PHP deprecation warnings |
| [`OpenTelemetryCollector`](/guide/collectors/opentelemetry) | OpenTelemetry spans and traces |
| [`AssetBundleCollector`](/guide/collectors/asset-bundle) | Frontend asset bundles (Yii) |
| [`FilesystemStreamCollector`](/guide/collectors/filesystem-stream) | Filesystem stream operations |
| [`HttpStreamCollector`](/guide/collectors/http-stream) | HTTP stream wrapper operations |
| `CodeCoverageCollector` | Per-request PHP line coverage (requires pcov or xdebug) |

### Web-Specific

| Collector | Data Collected |
|-----------|---------------|
| [`RequestCollector`](/guide/collectors/request) | Incoming HTTP request and response details |
| [`WebAppInfoCollector`](/guide/collectors/web-app-info) | PHP version, memory, execution time |

### Console-Specific

| Collector | Data Collected |
|-----------|---------------|
| [`CommandCollector`](/guide/collectors/command) | Console command executions |
| [`ConsoleAppInfoCollector`](/guide/collectors/console-app-info) | Console application metadata |

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

## Code Coverage Collector

`CodeCoverageCollector` captures per-request PHP line coverage using [pcov](https://github.com/krakjoe/pcov) or [xdebug](https://xdebug.org/) as the coverage driver.

::: warning Prerequisites
Requires the **pcov** extension (recommended) or **xdebug** with coverage mode enabled. Without either, the collector returns an empty result with `driver: null`.
:::

### How It Works

1. On `startup()`, the collector detects the available driver and starts coverage collection
2. Your application code runs normally — every executed PHP line is tracked
3. On `shutdown()`, coverage is stopped and raw data is processed into per-file statistics
4. Files matching `excludePaths` (default: `vendor`) are filtered out

### Enabling

Code coverage is **opt-in** (disabled by default) due to performance overhead.

:::tabs key:framework
== Symfony
```yaml
# config/packages/app_dev_panel.yaml
app_dev_panel:
    collectors:
        code_coverage: true
```
== Laravel
```php
// config/app-dev-panel.php
'collectors' => [
    'code_coverage' => true,
],
```
== Yii 2
```php
// config/web.php — modules.debug-panel
'collectors' => [
    'code_coverage' => true,
],
```
:::

### Output Format

```json
{
    "driver": "pcov",
    "files": {
        "/app/src/Controller/HomeController.php": {
            "coveredLines": 12,
            "executableLines": 15,
            "percentage": 80.0
        }
    },
    "summary": {
        "totalFiles": 42,
        "coveredLines": 340,
        "executableLines": 500,
        "percentage": 68.0
    }
}
```

### Inspector Endpoint

The inspector also provides a live coverage endpoint at `GET /inspect/api/coverage` that performs a one-shot coverage collection. See [Inspector Endpoints](/api/inspector) for details.
