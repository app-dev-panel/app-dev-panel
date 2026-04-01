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

### Current State: Scaffolded but NOT Wired

Module Federation support exists as **disconnected pieces** — the PHP interfaces exist, the frontend loader exists, but the middleware that connects them is missing.

#### What exists

**PHP side** (interfaces only, no glue):
- `ModuleFederationProviderInterface` (`libs/API/src/Debug/ModuleFederationProviderInterface.php`) — a collector can declare it provides a remote panel via `getAsset(): ModuleFederationAssetBundle`
- `ModuleFederationAssetBundle` (`libs/API/src/Debug/ModuleFederationAssetBundle.php`) — abstract class with `getModule()` and `getScope()` methods

**Frontend side** (loader exists):
- `ModuleLoader` (`libs/frontend/packages/panel/src/Application/Pages/RemoteComponent.tsx`) — dynamically loads a Webpack Module Federation remote via `<script>` injection + `__webpack_init_sharing__`
- `Layout.tsx:164` — the `default` fallback checks for `data.__isPanelRemote__` flag

#### What's MISSING

The `DebugController::view()` (`libs/API/src/Debug/Controller/DebugController.php:49-63`) returns raw collector data from storage — it does **NOT** check if the collector implements `ModuleFederationProviderInterface` and does **NOT** inject the `__isPanelRemote__` flag. The data flows like this:

```
Collector::getCollected() → Dumper → JSON file → CollectorRepository::getDetail() → DebugController::view() → Frontend
```

There is no step that:
1. Looks up the collector class in the DI container
2. Checks if it implements `ModuleFederationProviderInterface`
3. Wraps the data with `{__isPanelRemote__: true, url, module, scope, data}`

**This means**: Module Federation currently only works if the collector's `getCollected()` manually returns data with the `__isPanelRemote__` flag baked in. The `ModuleFederationProviderInterface` / `ModuleFederationAssetBundle` PHP interfaces are dead code — nothing reads them.

#### How it WOULD work (if wired)

**Step 1: PHP — Create collector + asset bundle**

```php
// 1. Asset bundle describing the remote React component
final class MyPanelAsset extends ModuleFederationAssetBundle
{
    public static function getModule(): string
    {
        return 'myRemote';                    // Webpack container name
    }

    public static function getScope(): string
    {
        return './MyCollectorPanel';           // Exposed component path
    }
}

// 2. Collector implementing ModuleFederationProviderInterface
final class MyCollector implements ModuleFederationProviderInterface
{
    use CollectorTrait;

    private array $items = [];

    public static function getAsset(): ModuleFederationAssetBundle
    {
        return new MyPanelAsset();
    }

    public function collect(string $key, mixed $value): void
    {
        if (!$this->isActive()) return;
        $this->items[] = ['key' => $key, 'value' => $value];
    }

    public function getCollected(): array
    {
        return $this->items;
    }
}
```

**Step 2: Frontend — Build the remote React component**

```typescript
// webpack.config.js (remote app)
const { ModuleFederationPlugin } = require('webpack').container;

module.exports = {
    plugins: [
        new ModuleFederationPlugin({
            name: 'myRemote',               // matches getModule()
            filename: 'remoteEntry.js',
            exposes: {
                './MyCollectorPanel': './src/MyCollectorPanel',  // matches getScope()
            },
            shared: {
                react: { singleton: true },
                'react-dom': { singleton: true },
                '@mui/material': { singleton: true },
            },
        }),
    ],
};
```

```tsx
// src/MyCollectorPanel.tsx (the exposed component)
import React from 'react';
import { Box, Chip } from '@mui/material';

type Props = { data: Array<{ key: string; value: any }> };

const MyCollectorPanel = ({ data }: Props) => (
    <Box>
        {data.map((item, i) => (
            <Box key={i} sx={{ display: 'flex', gap: 1, py: 0.5 }}>
                <Chip label={item.key} size="small" />
                <span>{JSON.stringify(item.value)}</span>
            </Box>
        ))}
    </Box>
);

export default MyCollectorPanel;
```

Build this as a standalone remote:
```bash
npx webpack --config webpack.config.js
# Output: dist/remoteEntry.js (+ chunks)
```

Serve `dist/` at a URL accessible to the debug panel (e.g., alongside your app's assets).

**Step 3: The missing middleware** (what needs to be built)

The `DebugController::view()` (or a middleware before it) needs to:

```php
// Pseudocode for what's missing
$collectorData = $data[$collectorClass];

// Check if collector is a Module Federation provider
if (is_subclass_of($collectorClass, ModuleFederationProviderInterface::class)) {
    $asset = $collectorClass::getAsset();
    $collectorData = [
        '__isPanelRemote__' => true,
        'url' => '/path/to/remoteEntry.js',  // needs to be resolved
        'module' => $asset->getModule(),
        'scope' => $asset->getScope(),
        'data' => $collectorData,
    ];
}
```

#### Workaround: Manual `__isPanelRemote__` in `getCollected()`

Until the middleware is built, a collector can manually bake the flag into its data:

```php
final class MyCollector implements CollectorInterface
{
    use CollectorTrait;

    private array $items = [];

    public function collect(string $key, mixed $value): void { /* ... */ }

    public function getCollected(): array
    {
        return [
            '__isPanelRemote__' => true,
            'url' => '/assets/my-collector/remoteEntry.js',
            'module' => 'myRemote',
            'scope' => './MyCollectorPanel',
            'data' => $this->items,
        ];
    }
}
```

This bypasses the PHP interfaces entirely but works with the existing frontend.

**Difficulty**: High — requires Webpack Module Federation knowledge, React component building, and shared dependency coordination. The `__isPanelRemote__` workaround avoids the need to modify core code.

### How It Worked in yiisoft/yii-dev-panel (Original Project)

The original Yii Dev Panel project (from which ADP was forked) had a working prototype:

**Source**: [yiisoft/yii-dev-panel](https://github.com/yiisoft/yii-dev-panel), specifically:
- `examples/remote-panel/` — a standalone Vite app building a Module Federation remote
- PR [#57 "Remote example"](https://github.com/yiisoft/yii-dev-panel/pull/57) (draft, never merged)
- [docs/guide/en/shared_components.md](https://github.com/yiisoft/yii-dev-panel/blob/master/docs/guide/en/shared_components.md)
- Issue [#55 "Module federation doesn't work"](https://github.com/yiisoft/yii-dev-panel/issues/55) — known bug with `@originjs/vite-plugin-federation`

**Architecture of the original remote-panel example:**

```
examples/remote-panel/              # Standalone Vite app (the "remote")
├── src/
│   ├── LogPanel.tsx                # Custom panel component for LogCollector
│   └── CachePanel.tsx              # Custom panel component for CacheCollector
├── vite.config.ts                  # Module Federation config (name: "remote")
├── package.json                    # Deps: react, MUI, @originjs/vite-plugin-federation, SDK
└── index.html
```

**Remote's vite.config.ts** (key part):
```typescript
import federation from '@originjs/vite-plugin-federation';

export default defineConfig({
    plugins: [
        // ...react, svgr, tsconfigPaths
        federation({
            name: 'remote',                        // Container name
            filename: 'external.js',               // Output filename (NOT remoteEntry.js!)
            exposes: {
                './LogPanel': './src/LogPanel',     // Exposed components
                './CachePanel': './src/CachePanel',
            },
            shared: ['react', 'react-dom', 'react-redux', 'react-router', 'react-router-dom', 'redux-persist'],
        }),
    ],
    build: { outDir: 'dist', minify: true, target: 'esnext' },
});
```

**Host's vite.config.ts** (currently commented out at `libs/frontend/packages/panel/vite.config.ts:50-54`):
```typescript
// federation({
//     name: 'host',
//     remotes: {},         // Empty! Remotes are loaded dynamically, not statically
//     shared: sharedModules,
// }),
```

**Host-side loader** (`SharedPage.tsx` — exists at `libs/frontend/packages/panel/src/Application/Pages/SharedPage.tsx`):
```tsx
<ModuleLoader
    url={'http://localhost:3002/external.js'}   // Remote's built output
    module={'./LogPanel'}                        // Matches exposes key
    scope={'remote'}                             // Matches federation name
    props={{data: logsData}}                     // Collector data passed as props
/>
```

**How `ModuleLoader` works** (`RemoteComponent.tsx`):
1. Injects `<script src="http://localhost:3002/external.js">` into `<head>`
2. Calls `__webpack_init_sharing__('default')` to initialize shared scope
3. Calls `window['remote'].init(...)` to connect the remote container
4. Calls `window['remote'].get('./LogPanel')` to get the component factory
5. Wraps in `React.lazy()` + `<Suspense>` for async loading

**Remote component contract** — receives `{data}` prop with collector data:
```tsx
type LogPanelProps = { data: Array<{severity: string; text: string}> };
const LogPanel = ({data}: LogPanelProps) => (
    <>
        {data.map((entry, i) => (
            <Alert key={i} severity={entry.severity}>{entry.text}</Alert>
        ))}
        <JsonRenderer value={data} />
    </>
);
export default LogPanel;
```

**Known issue**: `@originjs/vite-plugin-federation` has compatibility problems (issue [originjs/vite-plugin-federation#448](https://github.com/originjs/vite-plugin-federation/issues/448)) — shared modules don't load correctly when host and remote use different chunk strategies. This is why the federation plugin is commented out in the current ADP codebase.

### What Was Carried Over to ADP vs. What Was Lost

| Component | In yii-dev-panel | In ADP | Status |
|-----------|-----------------|--------|--------|
| `RemoteComponent.tsx` (ModuleLoader) | Yes | Yes (`Application/Pages/RemoteComponent.tsx`) | Carried over, works |
| `SharedPage.tsx` (demo page) | Yes | Yes (`Application/Pages/SharedPage.tsx`) | Carried over, hardcoded to localhost:3002 |
| `examples/remote-panel/` | Yes | **No** | Lost — no example project |
| Host federation plugin in vite.config | Active | **Commented out** (line 50-54) | Disabled due to plugin bugs |
| `__isPanelRemote__` check in Layout | Yes | Yes (line 164) | Works, but no backend generates this flag |
| `ModuleFederationProviderInterface` | N/A (was backend-less) | Yes (new in ADP) | Dead code — never consumed |
| `ModuleFederationAssetBundle` | N/A | Yes (new in ADP) | Dead code — never consumed |
| `shared_components.md` docs | Yes | **No** | Lost |

**Key insight**: In yii-dev-panel, Module Federation was a **frontend-only** mechanism — the `SharedPage` had hardcoded data and URLs. There was never backend integration (no `ModuleFederationProviderInterface`). ADP added the PHP interfaces as a forward-looking design but never built the glue layer.

### Recommended Path Forward

Given that `@originjs/vite-plugin-federation` is buggy, and the original approach was never production-ready, consider these alternatives:

1. **Module Federation 2.0** (`@module-federation/vite`) — newer Vite-native implementation, more stable than `@originjs`
2. **Dynamic import() via URL** — simpler approach: collector returns a JS bundle URL, frontend loads it via `import()` without Module Federation overhead
3. **iFrame-based panels** — the Frames module already supports this pattern; a collector could declare its panel URL and render in an iframe

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
