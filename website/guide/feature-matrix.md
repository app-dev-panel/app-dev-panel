---
title: Feature Matrix
---

# Feature Matrix

ADP supports multiple PHP frameworks through adapters. Each adapter wires framework-specific hooks into the shared Kernel collectors. This page documents what is available in each adapter.

## Collector Support by Adapter

All collectors live in the Kernel and are framework-independent. Adapters register and wire them via framework-specific event hooks, proxies, or decorators.

### Universal Collectors

These collectors are registered in **all four adapters**:

| Collector | Frontend Panel | Description |
|-----------|---------------|-------------|
| `TimelineCollector` | Timeline | Performance timeline events |
| `LogCollector` | Logs | PSR-3 log messages |
| `EventCollector` | Events | PSR-14 dispatched events |
| `ExceptionCollector` | Exceptions | Uncaught exceptions with stack traces |
| `DeprecationCollector` | _(in Logs)_ | PHP deprecation warnings |
| `ServiceCollector` | Services | DI container service resolutions |
| `HttpClientCollector` | HTTP Client | PSR-18 outgoing HTTP requests |
| `VarDumperCollector` | Var Dumper | `dump()` / `dd()` calls |
| `EnvironmentCollector` | Environment | PHP and OS environment info |
| `FilesystemStreamCollector` | Filesystem | File system stream operations |
| `HttpStreamCollector` | _(hidden)_ | Raw HTTP stream data (sub-view of HTTP Client) |
| `RequestCollector` | Request | Incoming HTTP request/response (web entries) |
| `CommandCollector` | Request | Console command details (console entries) |
| `WebAppInfoCollector` | _(meta)_ | Web app summary for entry list |
| `ConsoleAppInfoCollector` | _(meta)_ | Console app summary for entry list |
| `RouterCollector` | Router | HTTP route matching data |
| `ValidatorCollector` | Validator | Validation operations and results |
| `TranslatorCollector` | Translator | Translation lookups, missing translations |
| `AuthorizationCollector` | Security | Authentication and authorization data |
| `OpenTelemetryCollector` | OpenTelemetry | OpenTelemetry spans and traces |

### Collector Availability Matrix

| Collector | Yiisoft | Symfony | Laravel | Yii2 | Frontend Panel |
|-----------|:-------:|:-------:|:-------:|:----:|---------------|
| Database | ✅ | ✅ | ✅ | ✅ | Database |
| Cache | ✅ | ✅ | ✅ | ✅ | Cache |
| Mailer | ✅ | ✅ | ✅ | ✅ | Mailer |
| Queue | ✅ | ✅ | ✅ | ✅ | Queue |
| Redis | ✅ | ✅ | ✅ | ✅ | Redis |
| Elasticsearch | ✅ | ✅ | ✅ | ✅ | Elasticsearch |
| View | ✅ | — | — | ✅ | WebView |
| Templates | — | ✅ | — | ✅ | Templates |
| Code Coverage | — | ✅ | ✅ | ✅ | Coverage |
| Asset Bundles | — | — | — | ✅ | Asset Bundles |
| Middleware | ✅ | — | — | — | Middleware |
| Messenger | — | ✅ | — | — | Messenger |

### Collector Totals by Adapter

| Adapter | Universal | Additional | Total |
|---------|:---------:|:----------:|:-----:|
| Yiisoft | 20 | 5 | **25** |
| Symfony | 20 | 5 | **25** |
| Yii2 | 20 | 7 | **27** |
| Laravel | 20 | 4 | **24** |

## Proxy / Interception Mechanisms

Each adapter uses different strategies to intercept framework internals and feed data into collectors:

| Interface | Yiisoft | Symfony | Laravel | Yii2 |
|-----------|---------|---------|---------|------|
| PSR-3 Logger | `LoggerInterfaceProxy` | `LoggerInterfaceProxy` | `LoggerInterfaceProxy` | `DebugLogTarget` |
| PSR-14 Events | `EventDispatcherInterfaceProxy` | `SymfonyEventDispatcherProxy` | `LaravelEventDispatcherProxy` | Wildcard `Event::on('*')` |
| PSR-18 HTTP Client | `HttpClientInterfaceProxy` | `HttpClientInterfaceProxy` | `HttpClientInterfaceProxy` | `HttpClientInterfaceProxy` |
| PSR-11 Container | `ContainerInterfaceProxy` | Compiler pass | — | — |
| VarDumper | `VarDumperHandlerInterfaceProxy` | Handler hook | Handler hook | Handler hook |
| Database | `ConnectionInterfaceProxy` | DBAL middleware | Event listener | `DbProfilingTarget` |
| Mailer | `MailerInterfaceProxy` | Event listener | Event listener | Event hook |
| Router | `UrlMatcherInterfaceProxy` | — | `RouterDataExtractor` | `UrlRuleProxy` |
| Validator | `ValidatorInterfaceProxy` | — | — | — |
| Queue | `QueueProviderInterfaceProxy` | — | Event listener | — |
| View/Templates | — | Twig profiler extension | — | `View::EVENT_AFTER_RENDER` |
| Cache | — | Decorated `CacheAdapter` | Event listener | — |
| Messenger | — | Messenger middleware | — | — |
| Translator | `TranslatorInterfaceProxy` | `SymfonyTranslatorProxy` | `LaravelTranslatorProxy` | `I18NProxy` |

## Inspector Features

Inspector provides live application introspection (not tied to debug entries). All API-based features are adapter-independent:

| Feature | Yiisoft | Symfony | Laravel | Yii2 | Cycle |
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

| Feature | Yiisoft | Symfony | Laravel | Yii2 |
|---------|:-------:|:-------:|:-------:|:----:|
| View/template debugging | ✅ | ✅ | ❌ | ✅ |
| Code coverage | ❌ | ✅ | ✅ | ✅ |
| Middleware debugging | ✅ | ❌ | ❌ | ❌ |
| Message bus debugging | ❌ | ✅ | ❌ | ❌ |
| Asset bundle debugging | ❌ | ❌ | ❌ | ✅ |
| Container proxy | ✅ | ❌ | ❌ | ❌ |

::: tip
Feature parity is actively improving. If you need a specific collector for your framework, [contributions are welcome](/guide/contributing).
:::
