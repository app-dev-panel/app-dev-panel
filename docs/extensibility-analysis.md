# Extensibility Architecture Analysis

Analysis of ADP's extension points from a user perspective: what can be extended, how hard it is, and what's missing.

## 1. Custom Collector — The Primary Extension Point

### What a User Needs to Do (PHP Backend)

**Step 1**: Implement `CollectorInterface` (5 methods):

```php
<?php
declare(strict_types=1);

namespace App\Debug;

use AppDevPanel\Kernel\Collector\CollectorInterface;
use AppDevPanel\Kernel\Collector\CollectorTrait;

final class MyCustomCollector implements CollectorInterface
{
    use CollectorTrait; // provides getId(), getName(), startup(), shutdown(), isActive()

    private array $items = [];

    // Called by your code (proxy, event listener, etc.) during request lifecycle
    public function collect(string $key, mixed $value): void
    {
        if (!$this->isActive()) {
            return;
        }
        $this->items[] = ['key' => $key, 'value' => $value, 'time' => microtime(true)];
    }

    public function getCollected(): array
    {
        return $this->items;
    }

    private function reset(): void
    {
        $this->items = [];
    }
}
```

Key interfaces:
- `CollectorInterface` (`libs/Kernel/src/Collector/CollectorInterface.php`) — 5 methods: `getId()`, `getName()`, `startup()`, `shutdown()`, `getCollected()`
- `SummaryCollectorInterface` (`libs/Kernel/src/Collector/SummaryCollectorInterface.php`) — adds `getSummary()` for the debug entry list view
- `CollectorTrait` (`libs/Kernel/src/Collector/CollectorTrait.php`) — default implementations for `getId()` (FQCN), `getName()` (auto-derived from class name), `startup()`/`shutdown()` with `isActive()` guard

**Step 2**: Register the collector in the adapter config.

Per framework:

**Yii 3** — add to `params.php`:
```php
'app-dev-panel/kernel' => [
    'collectors' => [
        // ... existing collectors
        \App\Debug\MyCustomCollector::class,
    ],
],
```

**Symfony** — tag as service in `services.yaml` or register in `AppDevPanelExtension`:
```yaml
App\Debug\MyCustomCollector:
    tags: ['app_dev_panel.collector']
```
The `CollectorProxyCompilerPass` picks up tagged services and adds them to the Debugger.

**Laravel** — register in a service provider:
```php
$this->app->tag([MyCustomCollector::class], 'app-dev-panel.collectors');
```

**Step 3**: Feed data to the collector. Options:
- **Manual**: Call `$collector->collect(...)` from your code
- **Via proxy**: Create a PSR interface proxy that delegates to the real service and calls the collector (see Section 2)
- **Via adapter hooks**: Framework event listeners that call collector methods (e.g., Symfony EventSubscribers, Laravel middleware)

### What Happens After Registration

1. `Debugger::startup()` calls `$collector->startup()` on each registered collector
2. During the request, your proxy/listener feeds data via collector's public methods
3. `Debugger::shutdown()` triggers `StorageInterface::flush()`, which calls `$collector->getCollected()` on each collector
4. Data is serialized via `Dumper` and written to storage (JSON files by default)
5. Frontend fetches via `GET /debug/api/view/{id}?collector=App\Debug\MyCustomCollector`

### Frontend Rendering

The Debug Layout (`libs/frontend/packages/panel/src/Module/Debug/Pages/Layout.tsx:127-200`) has a hardcoded map from collector FQCN → React panel component. Unknown collectors fall through to the `default` handler which:

1. Checks for `__isPanelRemote__` flag → loads remote Module Federation component
2. If string → renders as `<pre>`
3. Otherwise → renders via generic `DumpPage` (JSON tree viewer)

**This means**: A custom collector's data will display as a raw JSON tree by default. For a custom UI, users must either:
- Fork the frontend and add a panel component + entry in `CollectorsMap` enum
- Use Module Federation (`__isPanelRemote__`) to load a remote React component

## 2. Custom Proxy — Intercepting Services

### Pattern

Proxies wrap PSR interfaces (or any service interface) and transparently feed data to collectors. The app code never knows it's being intercepted.

**Kernel provides ready-made proxies** (framework-independent):
| Proxy | PSR Interface | Feeds |
|-------|---------------|-------|
| `LoggerInterfaceProxy` | PSR-3 `LoggerInterface` | `LogCollector` |
| `EventDispatcherInterfaceProxy` | PSR-14 `EventDispatcherInterface` | `EventCollector` |
| `HttpClientInterfaceProxy` | PSR-18 `ClientInterface` | `HttpClientCollector` |
| `SpanProcessorInterfaceProxy` | OpenTelemetry `SpanProcessorInterface` | `OpenTelemetryCollector` |
| `FilesystemStreamProxy` | PHP `file://` stream wrapper | `FilesystemStreamCollector` |
| `HttpStreamProxy` | PHP `http://`/`https://` wrappers | `HttpStreamCollector` |

**Creating a custom proxy**:

```php
use AppDevPanel\Kernel\ProxyDecoratedCalls;

final class MyServiceProxy implements MyServiceInterface
{
    use ProxyDecoratedCalls; // delegates __call, __get, __set to $decorated

    public function __construct(
        private readonly MyServiceInterface $decorated,
        private readonly MyCustomCollector $collector,
    ) {}

    public function doSomething(string $arg): Result
    {
        $start = microtime(true);
        $result = $this->decorated->doSomething($arg);
        $this->collector->collect($arg, microtime(true) - $start);
        return $result;
    }
}
```

Then register as a DI decorator in the framework.

### Difficulty Assessment

- **Easy** if your target has a PSR interface — just implement, decorate, register
- **Medium** if it's a framework-specific interface — need framework-specific proxy (see Symfony's `SymfonyEventDispatcherProxy`, `SymfonyTranslatorProxy`)
- **Hard** if it's a concrete class without interface — may need subclassing or reflection tricks

## 3. Custom Storage Backend

`StorageInterface` (`libs/Kernel/src/Storage/StorageInterface.php`) has 5 methods:
- `addCollector(CollectorInterface)` — register a collector
- `getData(): array` — return aggregated data from collectors
- `read(string $type, ?string $id): array` — read from storage
- `write(string $id, array $summary, array $data, array $objects): void` — write directly
- `flush(): void` — serialize collector data and persist
- `clear(): void` — wipe all data

**Built-in implementations**:
- `FileStorage` — JSON files in `runtime/debug/` with GC
- `MemoryStorage` — in-memory (for testing)

A user could implement `StorageInterface` for database, Redis, S3, etc. Inject via DI to replace `FileStorage`.

**Difficulty**: Medium. The interface is clean but underdocumented — users need to study `FileStorage` to understand the 3-type split (summary/data/objects) and the `Dumper` serialization.

## 4. Custom Inspector Provider

Inspector endpoints query live application state. Several use provider interfaces:

| Provider Interface | Purpose | Default |
|-------------------|---------|---------|
| `SchemaProviderInterface` | Database schema inspection | `NullSchemaProvider` |
| `AuthorizationConfigProviderInterface` | Auth config (guards, roles, voters) | `NullAuthorizationConfigProvider` |
| `ElasticsearchProviderInterface` | ES cluster inspection | `NullElasticsearchProvider` |

Users can implement these to add database/auth/ES inspection for their specific setup. Register in DI to replace the `Null*` default.

**Difficulty**: Easy — small focused interfaces with clear contracts.

## 5. Custom API Middleware

`ApiExtensionsConfig` (`libs/API/src/ApiExtensionsConfig.php`) accepts:
- `middlewares` — additional PSR-15 middleware class names
- `commandMap` — command class registry
- `params` — arbitrary extension parameters

Injected via DI. Allows adding auth, rate limiting, or custom headers to the debug API.

**Difficulty**: Easy if familiar with PSR-15 middleware.

## 6. Custom Frontend Module

`ModuleInterface` (`libs/frontend/packages/sdk/src/Types/Module.types.ts`):
```typescript
type ModuleInterface = {
    routes: RouteObject[];
    reducers: Record<string, Reducer>;
    middlewares: Middleware[];
    standaloneModule: boolean;
};
```

Register in `modules.ts`, wire reducers/middlewares in `store.ts`. Appears in the sidebar automatically.

**Difficulty**: Medium — requires knowledge of React Router, Redux Toolkit, and the ADP layout system.

## 7. Ingestion API — External Data (Non-PHP)

`StorageInterface::write()` is used by the Ingestion API to accept debug data from external sources (Node.js, Python, Go, etc.) without PHP collectors. Data is written directly to storage in the same format.

This enables language-agnostic debugging — any app that can POST JSON can send debug data.

**Difficulty**: Easy from the sender side (just HTTP POST), but the data format must match the expected structure.

## 8. Module Federation — Remote Panel Components

The `default` fallback in `CollectorData` (`Layout.tsx:163-175`) checks for `__isPanelRemote__`:
```typescript
if (typeof data === 'object' && data.__isPanelRemote__) {
    return <ModuleLoader url={baseUrl + data.url} module={data.module} scope={data.scope} props={{data: data.data}} />;
}
```

A collector can return a payload with `__isPanelRemote__` flag and Module Federation coordinates. The frontend will dynamically load a remote React component to render the collector's data.

**Difficulty**: High — requires understanding Webpack Module Federation, building a remote, and coordinating the data format.

---

## Gap Analysis: What's Missing for Easy Extensibility

### Critical Gaps

1. **No collector plugin system**. Custom collectors require modifying adapter config files (params.php, services.yaml). There's no `composer require my/collector` auto-discovery. Each adapter has its own wiring mechanism — a user must understand the specific framework's DI to register a collector.

2. **Frontend collector rendering is hardcoded**. The `CollectorData` component in `Layout.tsx` maps FQCN → Panel component via a static dictionary. Unknown collectors get a raw JSON dump. There's no plugin registry or convention-based panel loading (e.g., "if collector X exists, look for panel X").

3. **`CollectorsMap` enum is hardcoded**. Adding a new collector to the sidebar with proper icon/label/count requires editing `CollectorsMap`, `collectorMeta.ts`, and `collectorsTotal.ts` in the SDK. No dynamic registration.

4. **No collector metadata protocol**. Collectors return raw arrays from `getCollected()`. There's no schema, no type hints for the frontend, no declared "this is what my data looks like" contract. The frontend must hardcode knowledge of each collector's data shape.

### Medium Gaps

5. **No "collector package" convention**. Unlike Symfony bundles or Laravel packages, there's no standardized way to distribute a collector + proxy + frontend panel as a single installable package.

6. **No event hooks for collector lifecycle**. Collectors can't listen to other collectors or react to debugger events (beyond `startup()`/`shutdown()`). Cross-collector communication goes through shared services.

7. **Documentation gap**. The Kernel CLAUDE.md has a 3-line "Adding a New Collector" section. No user-facing guide exists in `website/` for creating custom collectors.

### Minor Gaps

8. **`SummaryCollectorInterface` is optional but important**. Without it, the debug entry list shows no metrics for the collector. This isn't obvious from the interface names alone.

9. **No validation of `getCollected()` return format**. A collector that returns malformed data will silently break the frontend panel — no error message about the expected format.

10. **Module Federation for custom panels is undocumented**. The `__isPanelRemote__` mechanism exists but has no documentation, examples, or tooling.

---

## Recommendations

### Short-term (improve existing extensibility)

1. **Add user-facing "Custom Collectors" guide** to `website/guide/` covering: interface, trait, registration per adapter, data format conventions, frontend fallback behavior.

2. **Improve the JSON fallback panel**. The `DumpPage` for unknown collectors should show the collector name, entry count, and structured key-value rendering — not just a raw JSON tree.

3. **Add collector metadata to the backend**. Extend `CollectorInterface` with optional `getSchema(): ?array` or a separate `CollectorMetadataInterface` that declares data shape, icon hint, label, and panel URL.

### Medium-term (plugin architecture)

4. **Dynamic collector registration**. Implement Composer plugin or auto-discovery (like Symfony bundle auto-registration) so `composer require my/collector-package` auto-registers the collector.

5. **Dynamic frontend panel loading**. Replace the hardcoded `pages` map with a registry that:
   - Maps collector FQCN → panel component (for built-in)
   - Falls back to Module Federation if `__isPanelRemote__`
   - Falls back to a configurable "generic panel" that renders based on collector metadata

6. **Collector package convention**. Define a standard package structure: `src/Collector.php`, `src/Proxy.php` (optional), `frontend/Panel.tsx` (optional), `manifest.json` (metadata).

### Long-term (ecosystem)

7. **Collector marketplace/registry**. A central place to discover and install community collectors.

8. **SDK for other languages**. Formalize the Ingestion API protocol so non-PHP apps can register collectors with proper metadata, not just raw data dumps.
