# ADP Feature Matrix: Adapters, Playgrounds & Frontend

Analysis date: 2026-03-19 (updated)

## 1. Core Kernel Collectors

All 17 Kernel collectors are available to all adapters. Adapters wire framework-specific event hooks to feed them.

| # | Collector | Frontend Panel | Has Sidebar Meta |
|---|-----------|----------------|:----------------:|
| 1 | `TimelineCollector` | `TimelinePanel` | ✅ |
| 2 | `LogCollector` | `LogPanel` | ✅ |
| 3 | `EventCollector` | `EventPanel` | ✅ |
| 4 | `ExceptionCollector` | `ExceptionPanel` | ✅ |
| 5 | `ServiceCollector` | `ServicesPanel` | ✅ |
| 6 | `HttpClientCollector` | `HttpClientPanel` | ✅ |
| 7 | `VarDumperCollector` | `VarDumperPanel` | ✅ |
| 8 | `EnvironmentCollector` | `EnvironmentPanel` | ✅ |
| 9 | `FilesystemStreamCollector` | `FilesystemPanel` | ✅ |
| 10 | `HttpStreamCollector` | **No panel** (hidden from sidebar) | ✅ |
| 11 | `RequestCollector` (web) | `RequestPanel` | ✅ |
| 12 | `WebAppInfoCollector` (web) | meta only (IndexPage) | ✅ |
| 13 | `CommandCollector` (console) | meta only | ✅ |
| 14 | `ConsoleAppInfoCollector` (console) | meta only | ✅ |
| 15 | `DatabaseCollector` | `DatabasePanel` | ✅ |
| 16 | `MailerCollector` | `MailerPanel` | ✅ |
| 17 | `AssetBundleCollector` | `AssetBundlePanel` | ✅ |

## 2. Adapter-Specific Collectors

| Collector | Yiisoft | Symfony | Laravel | Yii2 | Frontend Panel |
|-----------|:-------:|:-------:|:-------:|:----:|----------------|
| Middleware | ✅ `MiddlewareCollector` | — | — | — | `MiddlewarePanel` |
| Queue | ✅ `QueueCollector` | — | ✅ `QueueListener` | — | `QueuePanel` |
| Router | ✅ `RouterCollector` | — | ✅ `RouterDataExtractor` | — | `RouterPanel` |
| Validator | ✅ `ValidatorCollector` | — | — | — | `ValidatorPanel` |
| WebView | ✅ `WebViewCollector` | — | — | — | `WebViewPanel` |
| Twig templates | — | ✅ `TwigCollector` | — | — | `TwigPanel` |
| Security | ✅ (manual) | ✅ `SecuritySubscriber` | ✅ `SecurityListener` | ✅ `SecurityListener` | `SecurityPanel` |
| Cache | — | ✅ `CacheCollector` | ✅ `CacheListener` | — | `CachePanel` |
| Messenger | — | ✅ `MessengerCollector` | — | — | `MessengerPanel` |

### How Core Collectors Are Fed

| Core Collector | Yiisoft | Symfony | Laravel | Yii2 |
|----------------|---------|---------|---------|------|
| `DatabaseCollector` | `CommandInterfaceProxy` (paired start/end/error) | DBAL middleware → `logQuery()` | `DatabaseListener` (QueryExecuted event) | `DbProfilingTarget` → `logQuery()` |
| `MailerCollector` | `MailerInterfaceProxy` (normalizes `MessageInterface`) | `MessageEvent` listener (normalizes `Email`) | `MailListener` (MessageSent event) | `BaseMailer::EVENT_AFTER_SEND` (normalizes in Module) |
| `AssetBundleCollector` | — | — | — | `View::EVENT_END_PAGE` (normalizes in Module) |
| `CacheCollector` | — | decorated `CacheAdapter` | `CacheListener` (CacheHit/CacheMissed/KeyWritten/KeyForgotten) | — |
| `QueueCollector` | `QueueProviderInterfaceProxy` + `QueueWorkerInterfaceProxy` | — | `QueueListener` (JobProcessing/JobProcessed/JobFailed) | — |
| `HttpClientCollector` | `HttpClientInterfaceProxy` | `HttpClientInterfaceProxy` | `HttpClientListener` (RequestSending/ResponseReceived/ConnectionFailed) | `HttpClientInterfaceProxy` |
| `SecurityCollector` | Manual calls only | `SecuritySubscriber` (LoginSuccess/Failure, Logout, SwitchUser, Vote) | `SecurityListener` (Authenticated, Login, Logout, Failed) | `SecurityListener` (User::AFTER_LOGIN/LOGOUT + session user) |

### Totals

| Adapter | Kernel | Adapter-Specific | Total | With Panel | Without Panel |
|---------|:------:|:----------------:|:-----:|:----------:|:-------------:|
| Yiisoft | 17 | 5 | **22** | 21 | 1 (HttpStream) |
| Symfony | 17 | 4 | **21** | 20 | 1 (HttpStream) |
| Laravel | 17 | 2 | **19** | 18 | 1 (HttpStream) |
| Yii2 | 17 | 0 | **17** | 16 | 1 (HttpStream) |
| Cycle | 0 | 0 | **0** | — | — |

## 3. Proxy/Interception

| Interface/Component | Yiisoft | Symfony | Laravel | Yii2 |
|---------------------|---------|---------|---------|------|
| PSR-3 Logger | `LoggerInterfaceProxy` | `LoggerInterfaceProxy` | `LoggerInterfaceProxy` (`$app->extend`) | `DebugLogTarget` (Yii logger → PSR-3) |
| PSR-14 EventDispatcher | `EventDispatcherInterfaceProxy` | `SymfonyEventDispatcherProxy` | `LaravelEventDispatcherProxy` | `Event::on('*', '*')` wildcard |
| PSR-18 HttpClient | `HttpClientInterfaceProxy` | `HttpClientInterfaceProxy` | `HttpClientInterfaceProxy` (`$app->extend`) | `HttpClientInterfaceProxy` |
| PSR-11 Container | `ContainerInterfaceProxy` | — (compiler pass) | — | — |
| VarDumper | `VarDumperHandlerInterfaceProxy` | handler hook in `HttpSubscriber` | handler hook in `DebugMiddleware` | handler hook in `Module` |
| Database | `ConnectionInterfaceProxy` + `CommandInterfaceProxy` | DBAL middleware | `DatabaseListener` (event) | `DbProfilingTarget` (log target) |
| Mailer | `MailerInterfaceProxy` | `MessageEvent` listener | `MailListener` (event) | `BaseMailer` event |
| Router | `UrlMatcherInterfaceProxy` | — | `RouterDataExtractor` | — |
| Validator | `ValidatorInterfaceProxy` | — | — | — |
| Queue | `QueueProviderInterfaceProxy` + `QueueWorkerInterfaceProxy` + `QueueDecorator` | — | `QueueListener` (event) | — |
| View/Templates | — | Twig profiler extension | — | View event |
| Cache | — | decorated `CacheAdapter` | `CacheListener` (event) | — |
| Messenger | — | Messenger middleware | — | — |
| Security | — | direct event collection | — | — |
| Assets | — | — | — | `View::EVENT_END_PAGE` → core `AssetBundleCollector` |

## 4. Inspector Features

| Feature | Yiisoft | Symfony | Laravel | Yii2 | Cycle | Frontend Page |
|---------|:-------:|:-------:|:-------:|:----:|:-----:|:-------------:|
| Configuration | ✅ params | ✅ `SymfonyConfigProvider` | ✅ `LaravelConfigProvider` | ✅ `Yii2ConfigProvider` | — | ✅ |
| Database Schema | ✅ `DbSchemaProvider` | ✅ `DoctrineSchemaProvider` | ✅ `LaravelSchemaProvider` | ✅ `Yii2DbSchemaProvider` | ✅ `CycleSchemaProvider` | ✅ |
| Routes | ✅ `UrlMatcherInterfaceProxy` | ✅ `SymfonyRouteCollectionAdapter` | ✅ `LaravelRouteCollectionAdapter` | ✅ `Yii2RouteAdapter` | — | ✅ |
| File Explorer | ✅ (API) | ✅ (API) | ✅ (API) | ✅ (API) | — | ✅ |
| Git | ✅ (API) | ✅ (API) | ✅ (API) | ✅ (API) | — | ✅ |
| Composer | ✅ (API) | ✅ (API) | ✅ (API) | ✅ (API) | — | ✅ |
| Opcache | ✅ (API) | ✅ (API) | ✅ (API) | ✅ (API) | — | ✅ |
| PHP Info | ✅ (API) | ✅ (API) | ✅ (API) | ✅ (API) | — | ✅ |
| Commands | ✅ (API) | ✅ (API) | ✅ (API) | ✅ (API) | — | ✅ |
| Container/DI | ✅ (API) | ✅ (API) | ✅ (API) | ✅ (API) | — | ✅ |
| Cache | ✅ (API) | ✅ (API) | ✅ (API) | ✅ (API) | — | ✅ |
| Translations | ✅ (API) | ✅ (API) | ✅ (API) | ✅ (API) | — | ✅ |

## 5. Playground Configuration

| Aspect | yiisoft-app | symfony-basic-app | laravel-app | yii2-basic-app |
|--------|-------------|-------------------|-------------|----------------|
| Port | 8101 | 8102 | 8104 | 8103 |
| Registration | Config plugin (auto) | Bundle (dev/test only) | ServiceProvider (auto) | Bootstrap + Module |
| Request format | Native PSR-7 | HttpFoundation → PSR-7 | HttpFoundation → PSR-7 | Yii Request → PSR-7 |
| Event dispatch | PSR-14 (native) | Symfony EventDispatcher | Illuminate Events | Yii Events |
| DI integration | di.php configs | Extension + CompilerPass | ServiceProvider | Module::registerServices() |
| Config format | params.php | YAML (app_dev_panel.yaml) | PHP array (publishable) | PHP array (module props) |
| Test fixtures | 15+ actions | 15+ actions | 15+ actions | 15+ actions |
| Storage path | @runtime/debug | var/debug/ | storage/debug/ | @runtime/debug/ |
| History size | 50 | 50 | 50 | 50 |

## 6. Known Gaps

### Missing panel (1)

`HttpStreamCollector` — hidden from sidebar, data accessible via HttpClientPanel sub-view. Low priority.

### Playground gaps

- Yiisoft playground: missing test fixtures for Queue, Router, Validator, WebView

### Feature parity gaps between adapters

| Feature | Yiisoft | Symfony | Laravel | Yii2 |
|---------|:-------:|:-------:|:-------:|:----:|
| Queue monitoring | ✅ | ❌ | ✅ | ❌ |
| Route debugging | ✅ | ❌ | ✅ | ❌ |
| Validation debugging | ✅ | ❌ | ❌ | ❌ |
| View/template debugging | ✅ WebView | ✅ Twig | ❌ | ❌ |
| Middleware debugging | ✅ | ❌ | ❌ | ❌ |
| Security debugging | ❌ | ✅ | ❌ | ❌ |
| Cache debugging | ❌ | ✅ | ✅ | ❌ |
| Message bus debugging | ❌ | ✅ Messenger | ❌ | ❌ |
| Container proxy | ✅ | ❌ | ❌ | ❌ |
| Asset bundle debugging | ✅ (via core) | ✅ (via core) | ❌ | ✅ (via core) |
| Database debugging | ✅ (via core) | ✅ (via core) | ✅ (via core) | ✅ (via core) |
| Mailer debugging | ✅ (via core) | ✅ (via core) | ✅ (via core) | ✅ (via core) |
