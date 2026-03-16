# API Module

HTTP layer for ADP. Exposes debug data and application inspection via REST endpoints and SSE.

## Package

- Composer: `app-dev-panel/api`
- Namespace: `AppDevPanel\Api\`
- PHP: 8.1+
- Dependencies: `app-dev-panel/kernel`, plus Yii-specific packages (see below)

### Yii-specific dependencies (pending removal for framework-agnostic API)

`yiisoft/aliases`, `yiisoft/config`, `yiisoft/data-response`, `yiisoft/di`, `yiisoft/friendly-exception`, `yiisoft/http`, `yiisoft/middleware-dispatcher`, `yiisoft/translator`, `yiisoft/var-dumper`, `yiisoft/yii-middleware`

### Framework-independent dependencies

`alexkart/curl-builder`, `gitonomy/gitlib`, `guzzlehttp/guzzle`, `guzzlehttp/psr7`, `httpsoft/http-message`, `psr/container`, `psr/http-factory`, `psr/http-message`, `psr/http-server-handler`, `psr/http-server-middleware`, `psr/simple-cache`, `symfony/process`, `zircote/swagger-php`

## Directory Structure

```
src/
├── Debug/
│   ├── Controller/
│   │   └── DebugController.php
│   ├── Middleware/
│   │   ├── ResponseDataWrapper.php
│   │   ├── DebugHeaders.php
│   │   └── MiddlewareDispatcherMiddleware.php
│   └── Repository/
│       ├── CollectorRepositoryInterface.php
│       └── CollectorRepository.php
├── Inspector/
│   ├── Controller/
│   │   ├── InspectController.php        # Uses Kernel RequestCollector
│   │   ├── GitController.php
│   │   ├── CommandController.php
│   │   ├── ComposerController.php
│   │   ├── CacheController.php
│   │   └── OpcacheController.php
│   ├── Database/
│   │   ├── SchemaProviderInterface.php
│   │   ├── CycleSchemaProvider.php
│   │   └── DbSchemaProvider.php
│   ├── Command/
│   │   ├── CommandInterface.php
│   │   ├── BashCommand.php
│   │   ├── PHPUnitCommand.php
│   │   ├── CodeceptionCommand.php
│   │   └── PsalmCommand.php
│   └── ApplicationState.php
├── ServerSentEventsStream.php
└── ModuleFederationAssetBundle.php
config/
├── routes.php
├── di-web.php
└── params.php
```

## Key Change: InspectController

`InspectController` imports `AppDevPanel\Kernel\Collector\Web\RequestCollector` (from Kernel) instead of the Adapter-level collector. The `request()` and `buildCurl()` methods use `RequestCollector::class` as the key to read debug entry data from `CollectorRepositoryInterface`.

## API Endpoints

### Debug API (`/debug/api`)

| Method | Path | Description |
|--------|------|-------------|
| GET | `/` | List all debug entries (summaries) |
| GET | `/summary/{id}` | Single entry summary |
| GET | `/view/{id}` | Full entry data (optionally filtered by collector) |
| GET | `/dump/{id}` | Dump objects for entry |
| GET | `/object/{id}/{objectId}` | Specific object from dump |
| GET | `/event-stream` | SSE stream for real-time updates |

### Inspector API (`/inspect/api`)

| Method | Path | Description |
|--------|------|-------------|
| GET | `/routes` | All registered routes |
| GET | `/route/check` | Test route matching |
| GET | `/params` | Application parameters |
| GET | `/config` | DI configuration |
| GET | `/events` | Event listeners |
| GET | `/classes` | Declared classes |
| GET | `/object` | Instantiate and dump object |
| GET | `/files` | File explorer |
| GET | `/translations` | Translation catalogs |
| PUT | `/translations` | Update translation |
| GET | `/table` | Database tables list |
| GET | `/table/{name}` | Table schema + records |
| PUT | `/request` | Re-execute a request |
| POST | `/curl/build` | Build cURL command from request |
| GET | `/phpinfo` | PHP info output |

### Git API (`/inspect/api/git`)

| Method | Path | Description |
|--------|------|-------------|
| GET | `/summary` | Branch, SHA, remotes, branches |
| GET | `/log` | Last 20 commits |
| POST | `/checkout` | Switch branch |
| POST | `/command` | Run git pull/fetch |

### Command API (`/inspect/api/command`)

| Method | Path | Description |
|--------|------|-------------|
| GET | `/` | List available commands |
| POST | `/` | Execute a command |

### Composer API (`/inspect/api/composer`)

| Method | Path | Description |
|--------|------|-------------|
| GET | `/` | composer.json + composer.lock |
| GET | `/inspect` | Package details |
| POST | `/require` | Install package |

### Cache API (`/inspect/api/cache`)

| Method | Path | Description |
|--------|------|-------------|
| GET | `/` | View cache entry |
| DELETE | `/` | Delete cache key |
| POST | `/clear` | Clear all cache |

### OPcache API (`/inspect/api/opcache`)

| Method | Path | Description |
|--------|------|-------------|
| GET | `/` | OPcache status + configuration |

## Middleware Chain

```
Request -> IpFilter -> CorsAllowAll -> ResponseDataWrapper -> DebugHeaders -> Controller
```

1. **IpFilter** -- validates request IP against `allowedIPs` (default: `127.0.0.1`, `::1`)
2. **CorsAllowAll** -- adds permissive CORS headers
3. **ResponseDataWrapper** -- wraps responses in `{id, data, error, success, status}`
4. **DebugHeaders** -- adds `X-Debug-Id` and `X-Debug-Link` response headers

## Response Format

```json
{
    "id": "debug-entry-id",
    "data": { ... },
    "error": null,
    "success": true,
    "status": 200
}
```

## SSE (Server-Sent Events)

`/debug/api/event-stream` polls storage every second, computes MD5 hash of summary data.
When hash changes, emits:

```
data: {"type": "debug-updated", "payload": []}
```
