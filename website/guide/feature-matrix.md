---
title: Feature Matrix
---

# Feature Matrix

ADP supports multiple PHP frameworks through adapters. Each adapter wires framework-specific hooks into the shared Kernel collectors. This page documents what is available in each adapter.

## Core Collectors

All 17 Kernel collectors are available to every adapter. Adapters wire framework-specific event hooks to feed data into them.

| Collector | Frontend Panel | Description |
|-----------|---------------|-------------|
| `TimelineCollector` | Timeline | Performance timeline events |
| `LogCollector` | Logs | PSR-3 log messages |
| `EventCollector` | Events | PSR-14 dispatched events |
| `ExceptionCollector` | Exceptions | Uncaught exceptions with stack traces |
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
| `DatabaseCollector` | Database | SQL queries, execution time, transactions |
| `MailerCollector` | Mailer | Sent email messages |
| `AssetBundleCollector` | Asset Bundles | Registered frontend asset bundles |

## Adapter-Specific Collectors

Beyond the shared Kernel collectors, each adapter provides framework-specific collectors:

| Collector | Yiisoft | Symfony | Laravel | Yii2 | Frontend Panel |
|-----------|:-------:|:-------:|:-------:|:----:|---------------|
| Middleware | ✅ | — | — | — | Middleware |
| Queue | ✅ | — | ✅ | — | Queue |
| Router | ✅ | — | ✅ | — | Router |
| Validator | ✅ | — | — | — | Validator |
| WebView | ✅ | — | — | — | WebView |
| Twig Templates | — | ✅ | — | — | Twig |
| Security | ✅ | ✅ | ✅ | ✅ | Security |
| Cache | — | ✅ | ✅ | — | Cache |
| Messenger | — | ✅ | — | — | Messenger |

### Collector Totals by Adapter

| Adapter | Kernel | Adapter-Specific | Total |
|---------|:------:|:----------------:|:-----:|
| Yiisoft | 17 | 5 | **22** |
| Symfony | 17 | 4 | **21** |
| Laravel | 17 | 2 | **19** |
| Yii2 | 17 | 0 | **17** |

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
| Router | `UrlMatcherInterfaceProxy` | — | `RouterDataExtractor` | — |
| Validator | `ValidatorInterfaceProxy` | — | — | — |
| Queue | `QueueProviderInterfaceProxy` | — | Event listener | — |
| View/Templates | — | Twig profiler extension | — | View event |
| Cache | — | Decorated `CacheAdapter` | Event listener | — |
| Messenger | — | Messenger middleware | — | — |

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

Current differences between adapters in adapter-specific functionality:

| Feature | Yiisoft | Symfony | Laravel | Yii2 |
|---------|:-------:|:-------:|:-------:|:----:|
| Queue monitoring | ✅ | ❌ | ✅ | ❌ |
| Route debugging | ✅ | ❌ | ✅ | ❌ |
| Validation debugging | ✅ | ❌ | ❌ | ❌ |
| View/template debugging | ✅ | ✅ | ❌ | ❌ |
| Middleware debugging | ✅ | ❌ | ❌ | ❌ |
| Cache debugging | ❌ | ✅ | ✅ | ❌ |
| Message bus debugging | ❌ | ✅ | ❌ | ❌ |
| Container proxy | ✅ | ❌ | ❌ | ❌ |

::: tip
Feature parity is actively improving. If you need a specific collector for your framework, [contributions are welcome](/guide/contributing).
:::
