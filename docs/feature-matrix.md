# ADP Feature Matrix: Adapters, Playgrounds & Frontend

Analysis date: 2026-03-19 (updated)

## 1. Core Kernel Collectors

All 15 Kernel collectors are supported by all three adapters (Yiisoft, Symfony, Yii2) and all three playgrounds.

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
| 15 | `AssetBundleCollector` | `AssetBundlePanel` | ✅ |

## 2. Adapter-Specific Collectors

| Collector | Yiisoft | Symfony | Yii2 | Frontend Panel | Data Format OK |
|-----------|:-------:|:-------:|:----:|----------------|:--------------:|
| Database (Yiisoft DB) | ✅ `DatabaseCollector` | — | — | `DatabasePanel` | ✅ reference |
| Database (Doctrine) | — | ✅ `DoctrineCollector` | — | `DatabasePanel` | ❌ needs normalization |
| Database (Yii2) | — | — | ✅ `DbCollector` | `DatabasePanel` | ❌ needs normalization |
| Middleware | ✅ `MiddlewareCollector` | — | — | `MiddlewarePanel` | ✅ |
| Mailer (Yiisoft) | ✅ `MailerCollector` | — | — | `MailerPanel` | ✅ reference |
| Mailer (Symfony) | — | ✅ `MailerCollector` | — | `MailerPanel` | ❌ needs enrichment |
| Mailer (Yii2) | — | — | ✅ `MailerCollector` | `MailerPanel` | ❌ needs enrichment |
| Queue | ✅ `QueueCollector` | — | — | `QueuePanel` | ✅ |
| Router | ✅ `RouterCollector` | — | — | `RouterPanel` | ✅ |
| Validator | ✅ `ValidatorCollector` | — | — | `ValidatorPanel` | ✅ |
| WebView | ✅ `WebViewCollector` | — | — | `WebViewPanel` | ✅ |
| Twig templates | — | ✅ `TwigCollector` | — | `TwigPanel` | ✅ |
| Security | — | ✅ `SecurityCollector` | — | `SecurityPanel` | ✅ |
| Cache | — | ✅ `CacheCollector` | — | `CachePanel` | ✅ |
| Messenger | — | ✅ `MessengerCollector` | — | `MessengerPanel` | ✅ |
| Asset Bundles | — | — | via core `AssetBundleCollector` | `AssetBundlePanel` | ✅ |

### Totals

| Adapter | Kernel | Adapter-Specific | Total | With Panel | Without Panel |
|---------|:------:|:----------------:|:-----:|:----------:|:-------------:|
| Yiisoft | 15 | 7 | **22** | 21 | 1 (HttpStream) |
| Symfony | 15 | 6 | **21** | 20 | 1 (HttpStream) |
| Yii2 | 15 | 2 | **17** | 16 | 1 (HttpStream) |
| Cycle | 0 | 0 | **0** | — | — |

## 3. Proxy/Interception

| Interface/Component | Yiisoft | Symfony | Yii2 |
|---------------------|---------|---------|------|
| PSR-3 Logger | `LoggerInterfaceProxy` | `LoggerInterfaceProxy` | `DebugLogTarget` (Yii logger → PSR-3) |
| PSR-14 EventDispatcher | `EventDispatcherInterfaceProxy` | `SymfonyEventDispatcherProxy` | `Event::on('*', '*')` wildcard |
| PSR-18 HttpClient | `HttpClientInterfaceProxy` | `HttpClientInterfaceProxy` | `HttpClientInterfaceProxy` |
| PSR-11 Container | `ContainerInterfaceProxy` | — (compiler pass) | — |
| VarDumper | `VarDumperHandlerInterfaceProxy` | handler hook in `HttpSubscriber` | handler hook in `Module` |
| Database | `ConnectionInterfaceProxy` + `CommandInterfaceProxy` | DBAL middleware | DB event listeners |
| Mailer | `MailerInterfaceProxy` | `MessageEvent` listener | `BaseMailer` event |
| Router | `UrlMatcherInterfaceProxy` | — | — |
| Validator | `ValidatorInterfaceProxy` | — | — |
| Queue | `QueueProviderInterfaceProxy` + `QueueWorkerInterfaceProxy` + `QueueDecorator` | — | — |
| View/Templates | — | Twig profiler extension | View event |
| Cache | — | decorated `CacheAdapter` | — |
| Messenger | — | Messenger middleware | — |
| Security | — | direct event collection | — |
| Assets | — | — | `View::EVENT_END_PAGE` → core `AssetBundleCollector` |

## 4. Inspector Features

| Feature | Yiisoft | Symfony | Yii2 | Cycle | Frontend Page |
|---------|:-------:|:-------:|:----:|:-----:|:-------------:|
| Configuration | ✅ params | ✅ `SymfonyConfigProvider` | ✅ `Yii2ConfigProvider` | — | ✅ |
| Database Schema | ✅ `DbSchemaProvider` | ✅ `DoctrineSchemaProvider` | ✅ `Yii2DbSchemaProvider` | ✅ `CycleSchemaProvider` | ✅ |
| Routes | ✅ `UrlMatcherInterfaceProxy` | ✅ `SymfonyRouteCollectionAdapter` | ✅ `Yii2RouteAdapter` | — | ✅ |
| File Explorer | ✅ (API) | ✅ (API) | ✅ (API) | — | ✅ |
| Git | ✅ (API) | ✅ (API) | ✅ (API) | — | ✅ |
| Composer | ✅ (API) | ✅ (API) | ✅ (API) | — | ✅ |
| Opcache | ✅ (API) | ✅ (API) | ✅ (API) | — | ✅ |
| PHP Info | ✅ (API) | ✅ (API) | ✅ (API) | — | ✅ |
| Commands | ✅ (API) | ✅ (API) | ✅ (API) | — | ✅ |
| Container/DI | ✅ (API) | ✅ (API) | ✅ (API) | — | ✅ |
| Cache | ✅ (API) | ✅ (API) | ✅ (API) | — | ✅ |
| Translations | ✅ (API) | ✅ (API) | ✅ (API) | — | ✅ |

## 5. Playground Configuration

| Aspect | yiisoft-app | symfony-basic-app | yii2-basic-app |
|--------|-------------|-------------------|----------------|
| Port | 8101 | 8102 | 8103 |
| Registration | Config plugin (auto) | Bundle (dev/test only) | Bootstrap + Module |
| Request format | Native PSR-7 | HttpFoundation → PSR-7 | Yii Request → PSR-7 |
| Event dispatch | PSR-14 (native) | Symfony EventDispatcher | Yii Events |
| DI integration | di.php configs | Extension + CompilerPass | Module::registerServices() |
| Config format | params.php | YAML (app_dev_panel.yaml) | PHP array (module props) |
| Test fixtures | 15 actions | 15 actions | 15 actions |
| Storage path | @runtime/debug | var/debug/ | @runtime/debug/ |
| History size | 50 | 50 | 50 |

## 6. Known Gaps (Updated)

### Data format mismatches (4 remaining)

| Collector | Issue | Impact |
|-----------|-------|--------|
| `DoctrineCollector` | Output schema differs from `DatabasePanel` expectations (no `rawSql`, `actions[]`, `status`, `transactions`) | Panel may render incorrectly |
| `Yii2\DbCollector` | Output schema differs (no `rawSql`, `actions[]`, `status`; `rowCount` vs `rowsNumber`) | Panel may render incorrectly |
| Symfony `MailerCollector` | Missing `textBody`, `htmlBody`, `replyTo`, `cc`, `bcc`, `raw`, `charset`, `date` | Limited mail display |
| Yii2 `MailerCollector` | Missing `textBody`, `htmlBody`, `replyTo`, `raw`, `charset`, `date` | Limited mail display |

### Missing panel (1)

`HttpStreamCollector` — hidden from sidebar, data accessible via HttpClientPanel sub-view. Low priority.

### Playground gaps

- Symfony playground: framework-specific collectors disabled (missing dependencies)
- Yiisoft playground: missing test fixtures for Queue, Router, Validator, WebView

### Feature parity gaps between adapters

| Feature | Yiisoft | Symfony | Yii2 |
|---------|:-------:|:-------:|:----:|
| Queue monitoring | ✅ | ❌ | ❌ |
| Route debugging | ✅ | ❌ | ❌ |
| Validation debugging | ✅ | ❌ | ❌ |
| View/template debugging | ✅ WebView | ✅ Twig | ❌ |
| Middleware debugging | ✅ | ❌ | ❌ |
| Security debugging | ❌ | ✅ | ❌ |
| Cache debugging | ❌ | ✅ | ❌ |
| Message bus debugging | ❌ | ✅ Messenger | ❌ |
| Container proxy | ✅ | ❌ | ❌ |
| Asset bundle debugging | ✅ (via core) | ✅ (via core) | ✅ (via core) |
