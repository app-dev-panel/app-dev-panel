---
title: Collectors
description: "Overview of ADP collectors — the core mechanism for capturing logs, events, queries, and other runtime data."
---

# Collectors

Collectors are the core data-gathering mechanism in ADP. Each collector implements <class>AppDevPanel\Kernel\Collector\CollectorInterface</class> and is responsible for capturing a specific type of runtime data during the application lifecycle.

## Built-in Collectors

### Core Collectors

| Collector | Data Collected | Guide |
|-----------|---------------|-------|
| <class>AppDevPanel\Kernel\Collector\LogCollector</class> | PSR-3 log messages (level, message, context) | [Log](/guide/collectors/log) |
| <class>AppDevPanel\Kernel\Collector\EventCollector</class> | PSR-14 dispatched events and listeners | [Event](/guide/collectors/event) |
| <class>AppDevPanel\Kernel\Collector\ExceptionCollector</class> | Uncaught exceptions with stack traces | [Exception](/guide/collectors/exception) |
| <class>AppDevPanel\Kernel\Collector\HttpClientCollector</class> | PSR-18 outgoing HTTP requests and responses | [HTTP Client](/guide/collectors/http-client) |
| <class>AppDevPanel\Kernel\Collector\DatabaseCollector</class> | SQL queries, execution time, transactions | [Database](/guide/collectors/database) |
| <class>AppDevPanel\Kernel\Collector\ElasticsearchCollector</class> | Elasticsearch requests, timing, hits count | [Elasticsearch](/guide/collectors/elasticsearch) |
| <class>AppDevPanel\Kernel\Collector\CacheCollector</class> | Cache get/set/delete operations with hit/miss rates | [Cache](/guide/collectors/cache) |
| <class>AppDevPanel\Kernel\Collector\RedisCollector</class> | Redis commands with timing and error tracking | [Redis](/guide/collectors/redis) |
| <class>AppDevPanel\Kernel\Collector\MailerCollector</class> | Sent email messages | [Mailer](/guide/collectors/mailer) |
| <class>AppDevPanel\Kernel\Collector\TranslatorCollector</class> | Translation lookups, missing translations | [Translator](/guide/collectors/translator) |
| <class>AppDevPanel\Kernel\Collector\QueueCollector</class> | Message queue operations (push, consume, fail) | [Queue](/guide/collectors/queue) |
| <class>AppDevPanel\Kernel\Collector\ServiceCollector</class> | DI container service resolutions | [Service](/guide/collectors/service) |
| <class>AppDevPanel\Kernel\Collector\RouterCollector</class> | HTTP route matching data | [Router](/guide/collectors/router) |
| <class>AppDevPanel\Kernel\Collector\MiddlewareCollector</class> | Middleware stack execution and timing | [Middleware](/guide/collectors/middleware) |
| <class>AppDevPanel\Kernel\Collector\ValidatorCollector</class> | Validation operations and results | [Validator](/guide/collectors/validator) |
| <class>AppDevPanel\Kernel\Collector\AuthorizationCollector</class> | Authentication and authorization data | [Authorization](/guide/collectors/authorization) |
| <class>AppDevPanel\Kernel\Collector\TemplateCollector</class> | Template/view rendering with timing, output capture, and duplicate detection | [Template](/guide/collectors/template) |
| <class>AppDevPanel\Kernel\Collector\VarDumperCollector</class> | Manual `dump()` / `dd()` calls | [VarDumper](/guide/collectors/var-dumper) |
| <class>AppDevPanel\Kernel\Collector\TimelineCollector</class> | Cross-collector performance timeline | [Timeline](/guide/collectors/timeline) |
| <class>AppDevPanel\Kernel\Collector\EnvironmentCollector</class> | PHP and OS environment info | [Environment](/guide/collectors/environment) |
| <class>AppDevPanel\Kernel\Collector\DeprecationCollector</class> | PHP deprecation warnings | [Deprecation](/guide/collectors/deprecation) |
| <class>AppDevPanel\Kernel\Collector\OpenTelemetryCollector</class> | OpenTelemetry spans and traces | [OpenTelemetry](/guide/collectors/opentelemetry) |
| <class>AppDevPanel\Kernel\Collector\AssetBundleCollector</class> | Frontend asset bundles (Yii) | [Asset Bundle](/guide/collectors/asset-bundle) |
| <class>AppDevPanel\Kernel\Collector\Stream\FilesystemStreamCollector</class> | Filesystem stream operations | [Filesystem Stream](/guide/collectors/filesystem-stream) |
| <class>AppDevPanel\Kernel\Collector\Stream\HttpStreamCollector</class> | HTTP stream wrapper operations | [HTTP Stream](/guide/collectors/http-stream) |
| <class>AppDevPanel\Kernel\Collector\CodeCoverageCollector</class> | Per-request PHP line coverage (requires pcov or xdebug) | [Coverage](/guide/inspector/coverage) |

### Web-Specific

| Collector | Data Collected | Guide |
|-----------|---------------|-------|
| <class>AppDevPanel\Kernel\Collector\Web\RequestCollector</class> | Incoming HTTP request and response details | [Request](/guide/collectors/request) |
| <class>AppDevPanel\Kernel\Collector\Web\WebAppInfoCollector</class> | PHP version, memory, execution time | [Web App Info](/guide/collectors/web-app-info) |

### Console-Specific

| Collector | Data Collected | Guide |
|-----------|---------------|-------|
| <class>AppDevPanel\Kernel\Collector\Console\CommandCollector</class> | Console command executions | [Command](/guide/collectors/command) |
| <class>AppDevPanel\Kernel\Collector\Console\ConsoleAppInfoCollector</class> | Console application metadata | [Console App Info](/guide/collectors/console-app-info) |

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

The <class>AppDevPanel\Kernel\Debugger</class> calls `startup()` on all registered collectors at the beginning of a request, and `shutdown()` followed by `getCollected()` at the end.

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

1. **Via proxies** -- PSR interface proxies (e.g., <class>AppDevPanel\Kernel\Collector\LoggerInterfaceProxy</class>) intercept calls and feed data to their paired collector automatically.
2. **Via direct calls** -- Adapter hooks or application code call methods on the collector directly (e.g., <class>AppDevPanel\Kernel\Collector\DatabaseCollector</class> receives query data from framework-specific database hooks).

## SummaryCollectorInterface

Collectors can also implement <class>AppDevPanel\Kernel\Collector\SummaryCollectorInterface</class> to provide summary data displayed in the debug entry list without loading full collector data.

## TranslatorCollector

Captures translation lookups during request execution, including missing translation detection. Implements <class>AppDevPanel\Kernel\Collector\SummaryCollectorInterface</class>.

See the dedicated [Translator](/guide/translator) page for full details: TranslationRecord fields, collected data structure, missing detection logic, framework proxy integrations, and configuration examples.

## Code Coverage Collector

<class>AppDevPanel\Kernel\Collector\CodeCoverageCollector</class> captures per-request PHP line coverage using [pcov](https://github.com/krakjoe/pcov) or [xdebug](https://xdebug.org/) as the coverage driver.

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
