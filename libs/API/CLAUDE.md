# API Module

HTTP layer for ADP. Three domains: **Debug** (stored debug entries), **Inspector** (live app state), **Ingestion** (external data intake).

## Package

- Composer: `app-dev-panel/api`
- Namespace: `AppDevPanel\Api\`
- PHP: 8.4+
- Dependencies: `app-dev-panel/kernel`

## Directory Structure

```
src/
├── Debug/
│   ├── Controller/
│   │   └── DebugController.php          # Debug data endpoints (Debugger mode)
│   ├── Middleware/
│   │   ├── ResponseDataWrapper.php      # Wraps responses in {id, data, error, success, status}
│   │   ├── DebugHeaders.php             # Adds X-Debug-Id, X-Debug-Link headers
│   │   └── MiddlewareDispatcherMiddleware.php
│   └── Repository/
│       ├── CollectorRepositoryInterface.php
│       └── CollectorRepository.php      # Reads debug data from storage
├── Inspector/
│   ├── Controller/                      # Inspector mode — live app state
│   │   ├── InspectController.php        # config, params, classes, object, phpinfo, events
│   │   ├── RoutingController.php        # routes, route check
│   │   ├── DatabaseController.php       # table list, table data with pagination
│   │   ├── FileController.php           # file explorer, file read
│   │   ├── TranslationController.php    # translation catalogs, update
│   │   ├── RequestController.php        # re-execute request, build cURL
│   │   ├── GitController.php            # git summary, log, checkout, commands
│   │   ├── CommandController.php        # list/execute commands + composer scripts
│   │   ├── ComposerController.php       # composer.json/lock, inspect, require
│   │   ├── CacheController.php          # view/delete/clear cache
│   │   ├── OpcacheController.php        # OPcache status
│   │   └── ServiceController.php       # Service registration (register, heartbeat, list, deregister)
│   ├── Middleware/
│   │   └── InspectorProxyMiddleware.php # Proxies inspector requests to external services
│   ├── Database/
│   │   ├── SchemaProviderInterface.php  # Interface for database schema inspection
│   │   └── NullSchemaProvider.php       # Default no-op fallback
│   ├── Command/
│   │   ├── CommandInterface.php
│   │   ├── BashCommand.php
│   │   ├── PHPUnitCommand.php
│   │   ├── CodeceptionCommand.php
│   │   └── PsalmCommand.php
│   └── ApplicationState.php
├── Ingestion/
│   └── Controller/
│       └── IngestionController.php      # External data intake (any language)
├── ServerSentEventsStream.php           # SSE implementation
└── ModuleFederationAssetBundle.php      # Remote panel support
config/
├── routes.php                           # All route definitions
├── di-web.php                           # DI configuration
└── params.php                           # Default parameters
```

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

### Ingestion API (`/debug/api/ingest`)

Language-agnostic endpoints for external applications to send debug data. Defined by OpenAPI 3.1 spec at `openapi/ingestion.yaml`.

| Method | Path | Description |
|--------|------|-------------|
| POST | `/` | Ingest single debug entry (collectors + optional context/summary) |
| POST | `/batch` | Ingest multiple entries at once |
| POST | `/log` | Shorthand: ingest a single log entry |
| GET | `/openapi.json` | Serve the OpenAPI spec |

Pre-built clients: Python (`clients/python/`), TypeScript (`clients/typescript/`).

### Service Registry API (`/debug/api/services`)

Manages external application registrations for multi-app inspector proxying.

| Method | Path | Description |
|--------|------|-------------|
| POST | `/register` | Register an external service (body: `service`, `inspectorUrl`, `language`, `capabilities`) |
| POST | `/heartbeat` | Heartbeat to keep service online (body: `service`) |
| GET | `/` | List all registered services with online/offline status |
| DELETE | `/{service}` | Deregister a service by name |

Service name `local` is reserved for the host PHP application.

### Inspector Proxy

`InspectorProxyMiddleware` is wired into the `/inspect/api` route group. When a request includes `?service=<name>`, the middleware proxies the request to the registered service's `inspectorUrl` instead of handling it locally. Requests without `?service` or with `?service=local` are handled by the local PHP controllers.

Capability checking: the middleware maps inspector path prefixes to capability names (e.g., `/routes` -> `routes`, `/table` -> `database`). If the target service does not declare the required capability, a 501 response is returned.

### Inspector OpenAPI Spec

`openapi/inspector.yaml` defines the Inspector API contract (OpenAPI 3.1) that external applications must implement to be proxied. Capabilities map to endpoint groups: `config`, `routes`, `files`, `cache`, `database`, `translations`, `events`, `commands`, `git`, `classes`, `object`, `phpinfo`, `opcache`, `request`, `composer`.

## Middleware Chain

All API requests pass through:

1. **IpFilter** — Validates request IP against `allowedIPs` (default: `127.0.0.1`, `::1`)
2. **CorsAllowAll** — Adds permissive CORS headers
3. **ResponseDataWrapper** — Wraps all responses in `{id, data, error, success, status}`
4. **DebugHeaders** — Adds `X-Debug-Id` and `X-Debug-Link` response headers

Inspector route group (`/inspect/api`) additionally includes:

5. **InspectorProxyMiddleware** — Routes requests with `?service=<name>` to external service URLs

## Response Format

All API responses are wrapped:

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

The `/debug/api/event-stream` endpoint polls storage every second, computing an MD5 hash
of the summary data. When a new debug entry is written, the hash changes and an event is emitted:

```
data: {"type": "debug-updated", "payload": []}
```

The frontend listens for this event and refreshes the debug entry list.
