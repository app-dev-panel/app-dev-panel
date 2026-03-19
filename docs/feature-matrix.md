# ADP Feature Matrix: Adapters, Playgrounds & Frontend

Analysis date: 2026-03-19

## 1. Core Kernel Collectors

All 14 Kernel collectors are supported by all three adapters (Yiisoft, Symfony, Yii2) and all three playgrounds.

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
| 10 | `HttpStreamCollector` | **DumpPage fallback** | ❌ |
| 11 | `RequestCollector` (web) | `RequestPanel` | ✅ |
| 12 | `WebAppInfoCollector` (web) | meta only (IndexPage) | ✅ |
| 13 | `CommandCollector` (console) | meta only | ✅ |
| 14 | `ConsoleAppInfoCollector` (console) | meta only | ✅ |

## 2. Adapter-Specific Collectors

| Collector | Yiisoft | Symfony | Yii2 | Frontend Panel |
|-----------|:-------:|:-------:|:----:|----------------|
| Database (Yiisoft DB) | ✅ `DatabaseCollector` | — | — | `DatabasePanel` (via old namespace) |
| Database (Doctrine) | — | ✅ `DoctrineCollector` | — | **DumpPage fallback** |
| Database (Yii2) | — | — | ✅ `DbCollector` | `DatabasePanel` |
| Middleware | ✅ `MiddlewareCollector` | — | — | `MiddlewarePanel` |
| Mailer (Yiisoft) | ✅ `MailerCollector` | — | — | `MailerPanel` (via old namespace) |
| Mailer (Symfony) | — | ✅ `MailerCollector` | — | **DumpPage fallback** |
| Mailer (Yii2) | — | — | ✅ `MailerCollector` | `MailerPanel` |
| Queue | ✅ `QueueCollector` | — | — | **DumpPage fallback** |
| Router | ✅ `RouterCollector` | — | — | **DumpPage fallback** |
| Validator | ✅ `ValidatorCollector` | — | — | **DumpPage fallback** |
| WebView | ✅ `WebViewCollector` | — | — | **DumpPage fallback** |
| Twig templates | — | ✅ `TwigCollector` | — | **DumpPage fallback** |
| Security | — | ✅ `SecurityCollector` | — | **DumpPage fallback** |
| Cache | — | ✅ `CacheCollector` | — | `CachePanel` |
| Messenger | — | ✅ `MessengerCollector` | — | **DumpPage fallback** |
| Asset Bundles | — | — | ✅ `AssetBundleCollector` | **DumpPage fallback** |

### Totals

| Adapter | Kernel | Adapter-Specific | Total | With Panel | Without Panel |
|---------|:------:|:----------------:|:-----:|:----------:|:-------------:|
| Yiisoft | 14 | 7 | **21** | 16 | 5 |
| Symfony | 14 | 6 | **20** | 15 | 5 |
| Yii2 | 14 | 3 | **17** | 16 | 1 |
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

## 6. Known Gaps

### Frontend namespace mismatch (Yiisoft adapter)

`collectors.ts` maps Yiisoft-specific collectors under old namespaces:
- `Yiisoft\Db\Debug\DatabaseCollector` → actual: `AppDevPanel\Adapter\Yiisoft\Collector\Db\DatabaseCollector`
- `Yiisoft\Mailer\Debug\MailerCollector` → actual: `AppDevPanel\Adapter\Yiisoft\Collector\Mailer\MailerCollector`
- `Yiisoft\Queue\Debug\QueueCollector` → actual: `AppDevPanel\Adapter\Yiisoft\Collector\Queue\QueueCollector`
- `Yiisoft\Validator\Debug\ValidatorCollector` → actual: `AppDevPanel\Adapter\Yiisoft\Collector\Validator\ValidatorCollector`
- `Yiisoft\Yii\View\Renderer\Debug\WebViewCollector` → actual: `AppDevPanel\Adapter\Yiisoft\Collector\View\WebViewCollector`
- `Yiisoft\Assets\Debug\AssetCollector` → no equivalent in new adapter

If collector IDs changed during migration, these panels won't render correctly.

### Collectors without dedicated frontend panels (12)

**Kernel:** `HttpStreamCollector`

**Yiisoft:** `QueueCollector`, `RouterCollector`, `ValidatorCollector`, `WebViewCollector`

**Symfony:** `DoctrineCollector`, `TwigCollector`, `SecurityCollector`, `MailerCollector`, `MessengerCollector`

**Yii2:** `AssetBundleCollector`

All fall back to `DumpPage` (raw JSON viewer).

### Symfony MailerCollector not mapped

`MailerPanel` is only mapped to old Yiisoft namespace and Yii2 mailer. Symfony's `AppDevPanel\Adapter\Symfony\Collector\MailerCollector` falls back to DumpPage despite having the same data structure.

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
| Asset bundle debugging | ❌ | ❌ | ✅ |
