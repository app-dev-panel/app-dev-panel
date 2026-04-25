---
title: Feature Matrix
description: "Collector and inspector support comparison across ADP adapters for Symfony, Laravel, Yii 3, Yii 2, and Spiral."
---

# Feature Matrix

ADP supports multiple PHP frameworks through adapters. Each adapter wires framework-specific hooks into the shared Kernel collectors. This page documents what is available in each adapter.

## Collector Support by Adapter

All collectors live in the Kernel and are framework-independent. Adapters register and wire them via framework-specific event hooks, proxies, or decorators.

### Universal Collectors

These collectors are registered in **all five full adapters**:

| Collector | Frontend Panel | Description |
|-----------|---------------|-------------|
| <class>AppDevPanel\Kernel\Collector\TimelineCollector</class> | Timeline | Performance timeline events |
| <class>AppDevPanel\Kernel\Collector\LogCollector</class> | Logs | PSR-3 log messages |
| <class>AppDevPanel\Kernel\Collector\EventCollector</class> | Events | PSR-14 dispatched events |
| <class>AppDevPanel\Kernel\Collector\ExceptionCollector</class> | Exceptions | Uncaught exceptions with stack traces |
| <class>AppDevPanel\Kernel\Collector\DeprecationCollector</class> | _(in Logs)_ | PHP deprecation warnings |
| <class>AppDevPanel\Kernel\Collector\ServiceCollector</class> | Services | DI container service resolutions |
| <class>AppDevPanel\Kernel\Collector\HttpClientCollector</class> | HTTP Client | PSR-18 outgoing HTTP requests |
| <class>AppDevPanel\Kernel\Collector\VarDumperCollector</class> | Var Dumper | `dump()` / `dd()` calls |
| <class>AppDevPanel\Kernel\Collector\EnvironmentCollector</class> | Environment | PHP and OS environment info |
| <class>AppDevPanel\Kernel\Collector\Stream\FilesystemStreamCollector</class> | Filesystem | File system stream operations |
| <class>AppDevPanel\Kernel\Collector\Stream\HttpStreamCollector</class> | _(hidden)_ | Raw HTTP stream data (sub-view of HTTP Client) |
| <class>AppDevPanel\Kernel\Collector\Web\RequestCollector</class> | Request | Incoming HTTP request/response (web entries) |
| <class>AppDevPanel\Kernel\Collector\Console\CommandCollector</class> | Request | Console command details (console entries) |
| <class>AppDevPanel\Kernel\Collector\Web\WebAppInfoCollector</class> | _(meta)_ | Web app summary for entry list |
| <class>AppDevPanel\Kernel\Collector\Console\ConsoleAppInfoCollector</class> | _(meta)_ | Console app summary for entry list |
| <class>AppDevPanel\Kernel\Collector\RouterCollector</class> | Router | HTTP route matching data |
| <class>AppDevPanel\Kernel\Collector\ValidatorCollector</class> | Validator | Validation operations and results |
| <class>AppDevPanel\Kernel\Collector\TranslatorCollector</class> | Translator | Translation lookups, missing translations |
| <class>AppDevPanel\Kernel\Collector\AuthorizationCollector</class> | Security | Authentication and authorization data |
| <class>AppDevPanel\Kernel\Collector\OpenTelemetryCollector</class> | OpenTelemetry | OpenTelemetry spans and traces |

### Collector Availability Matrix

| Collector | Yii 3 | Symfony | Laravel | Yii2 | Spiral | Frontend Panel |
|-----------|:-----:|:-------:|:-------:|:----:|:------:|---------------|
| Database | ✅ | ✅ | ✅ | ✅ | — | Database |
| Cache | ✅ | ✅ | ✅ | ✅ | ✅ (manual) | Cache |
| Mailer | ✅ | ✅ | ✅ | ✅ | ✅ (manual) | Mailer |
| Queue | ✅ | ✅ | ✅ | ✅ | ✅ (manual) | Queue |
| Redis | ✅ | ✅ | ✅ | ✅ | — | Redis |
| Elasticsearch | ✅ | ✅ | ✅ | ✅ | — | Elasticsearch |
| View | ✅ | — | — | ✅ | — | WebView |
| Templates | — | ✅ | ✅ | ✅ | ✅ (manual) | Templates |
| Code Coverage | ✅ | ✅ | ✅ | ✅ | — | Coverage |
| Asset Bundles | ✅ | ✅ | ✅ | ✅ | — | Asset Bundles |
| Middleware | ✅ | — | — | — | — | Middleware |
| Messenger | — | ✅ | — | — | — | Messenger |

::: info Spiral "manual"
Spiral collectors are wired but framework-side calls (`logCacheOperation()`,
`collectMessage()`, `logMessage()`, `beginRender()` …) must be made by the
application code. The MVP playground demonstrates each via `/test/fixtures/*`.
:::

### Collector Totals by Adapter

| Adapter | Universal | Additional | Total |
|---------|:---------:|:----------:|:-----:|
| Yii 3 | 20 | 10 | **30** |
| Symfony | 20 | 10 | **30** |
| Yii2 | 20 | 10 | **30** |
| Laravel | 20 | 10 | **30** |
| Spiral | 16 | 0 | **16** |

## Proxy / Interception Mechanisms

Each adapter uses different strategies to intercept framework internals and feed data into collectors:

| Interface | Yii 3 | Symfony | Laravel | Yii2 | Spiral |
|-----------|---------|---------|---------|------|--------|
| PSR-3 Logger | <class>AppDevPanel\Kernel\Collector\LoggerInterfaceProxy</class> | <class>AppDevPanel\Kernel\Collector\LoggerInterfaceProxy</class> | <class>AppDevPanel\Kernel\Collector\LoggerInterfaceProxy</class> | <class>AppDevPanel\Adapter\Yii2\Collector\DebugLogTarget</class> | <class>AppDevPanel\Kernel\Collector\LoggerInterfaceProxy</class> |
| PSR-14 Events | <class>AppDevPanel\Kernel\Collector\EventDispatcherInterfaceProxy</class> | <class>AppDevPanel\Adapter\Symfony\Proxy\SymfonyEventDispatcherProxy</class> | <class>AppDevPanel\Adapter\Laravel\Proxy\LaravelEventDispatcherProxy</class> | Wildcard `Event::on('*')` | <class>AppDevPanel\Kernel\Collector\EventDispatcherInterfaceProxy</class> |
| PSR-18 HTTP Client | <class>AppDevPanel\Kernel\Collector\HttpClientInterfaceProxy</class> | <class>AppDevPanel\Kernel\Collector\HttpClientInterfaceProxy</class> | <class>AppDevPanel\Kernel\Collector\HttpClientInterfaceProxy</class> | <class>AppDevPanel\Kernel\Collector\HttpClientInterfaceProxy</class> | <class>AppDevPanel\Kernel\Collector\HttpClientInterfaceProxy</class> |
| PSR-11 Container | <class>AppDevPanel\Adapter\Yii3\Proxy\ContainerInterfaceProxy</class> | Compiler pass | — | — |
| VarDumper | <class>AppDevPanel\Adapter\Yii3\Proxy\VarDumperHandlerInterfaceProxy</class> | Handler hook | Handler hook | Handler hook |
| Database | <class>AppDevPanel\Adapter\Yii3\Collector\Db\ConnectionInterfaceProxy</class> | DBAL middleware | Event listener | <class>AppDevPanel\Adapter\Yii2\Collector\DbProfilingTarget</class> |
| Mailer | <class>AppDevPanel\Adapter\Yii3\Collector\Mailer\MailerInterfaceProxy</class> | Event listener | Event listener | Event hook |
| Router | <class>AppDevPanel\Adapter\Yii3\Collector\Router\UrlMatcherInterfaceProxy</class> | — | <class>AppDevPanel\Adapter\Laravel\Collector\RouterDataExtractor</class> | <class>AppDevPanel\Adapter\Yii2\Proxy\UrlRuleProxy</class> |
| Validator | <class>AppDevPanel\Adapter\Yii3\Collector\Validator\ValidatorInterfaceProxy</class> | — | — | — |
| Queue | <class>AppDevPanel\Adapter\Yii3\Collector\Queue\QueueProviderInterfaceProxy</class> | — | Event listener | — |
| View/Templates | — | Twig profiler extension | <class>AppDevPanel\Adapter\Laravel\Collector\TemplateCollectorCompilerEngine</class> | `View::EVENT_AFTER_RENDER` |
| Cache | — | Decorated `CacheAdapter` | Event listener | — |
| Messenger | — | Messenger middleware | — | — |
| Asset Bundles | <class>AppDevPanel\Adapter\Yii3\Collector\Asset\AssetLoaderInterfaceProxy</class> | <class>AppDevPanel\Adapter\Symfony\EventSubscriber\AssetMapperSubscriber</class> | <class>AppDevPanel\Adapter\Laravel\EventListener\ViteAssetListener</class> | `View::EVENT_END_PAGE` |
| OpenTelemetry | <class>AppDevPanel\Kernel\Collector\SpanProcessorInterfaceProxy</class> | <class>AppDevPanel\Kernel\Collector\SpanProcessorInterfaceProxy</class> | <class>AppDevPanel\Kernel\Collector\SpanProcessorInterfaceProxy</class> | <class>AppDevPanel\Kernel\Collector\SpanProcessorInterfaceProxy</class> |
| Translator | <class>AppDevPanel\Adapter\Yii3\Collector\Translator\TranslatorInterfaceProxy</class> | <class>AppDevPanel\Adapter\Symfony\Proxy\SymfonyTranslatorProxy</class> | <class>AppDevPanel\Adapter\Laravel\Proxy\LaravelTranslatorProxy</class> | <class>AppDevPanel\Adapter\Yii2\Proxy\I18NProxy</class> |

## Inspector Features

Inspector provides live application introspection (not tied to debug entries). All API-based features are adapter-independent:

| Feature | Yii 3 | Symfony | Laravel | Yii2 | Cycle |
|---------|:-------:|:-------:|:-------:|:----:|:-----:|
| Configuration | ✅ | ✅ | ✅ | ✅ | — |
| Database Schema | ✅ | ✅ | ✅ | ✅ | ✅ |
| Routes | ✅ | ✅ | ✅ | ✅ | — |
| File Explorer | ✅ | ✅ | ✅ | ✅ | — |
| Git | ✅ | ✅ | ✅ | ✅ | — |
| Composer | ✅ | ✅ | ✅ | ✅ | — |
| Opcache | ✅ | ✅ | ✅ | ✅ | — |
| PHP Info | ✅ | ✅ | ✅ | ✅ | — |
| Commands | ✅ | ✅ | ✅ | ✅ | — |
| Container / DI | ✅ | ✅ | ✅ | ✅ | — |
| Cache | ✅ | ✅ | ✅ | ✅ | — |
| Translations | ✅ | ✅ | ✅ | ✅ | — |

## Feature Parity Gaps

Current differences between adapters:

| Feature | Yii 3 | Symfony | Laravel | Yii2 |
|---------|:-------:|:-------:|:-------:|:----:|
| View/template debugging | ✅ | ✅ | ✅ | ✅ |
| Code coverage | ✅ | ✅ | ✅ | ✅ |
| Asset bundle debugging | ✅ | ✅ | ✅ | ✅ |
| Middleware debugging | ✅ | ❌ | ❌ | ❌ |
| Message bus debugging | ❌ | ✅ | ❌ | ❌ |
| Container proxy | ✅ | ❌ | ❌ | ❌ |

::: tip
Feature parity is actively improving. If you need a specific collector for your framework, [contributions are welcome](/guide/contributing).
:::
