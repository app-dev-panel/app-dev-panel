# API Module

HTTP layer for ADP. Exposes debug data and application inspection via REST endpoints and SSE.

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
│   │   └── DebugController.php          # Debug data endpoints
│   ├── Middleware/
│   │   ├── ResponseDataWrapper.php      # Wraps responses in standard format
│   │   ├── DebugHeaders.php             # Adds X-Debug-Id, X-Debug-Link headers
│   │   └── MiddlewareDispatcherMiddleware.php
│   └── Repository/
│       ├── CollectorRepositoryInterface.php
│       └── CollectorRepository.php      # Reads debug data from storage
├── Inspector/
│   ├── Controller/
│   │   ├── InspectController.php        # Application introspection
│   │   ├── GitController.php            # Git operations
│   │   ├── CommandController.php        # Command execution
│   │   ├── ComposerController.php       # Composer management
│   │   ├── CacheController.php          # Cache inspection
│   │   └── OpcacheController.php        # OPcache status
│   ├── Database/
│   │   ├── SchemaProviderInterface.php
│   │   ├── CycleSchemaProvider.php      # Cycle ORM schema
│   │   └── DbSchemaProvider.php         # Yii DB schema
│   ├── Command/
│   │   ├── CommandInterface.php
│   │   ├── BashCommand.php
│   │   ├── PHPUnitCommand.php
│   │   ├── CodeceptionCommand.php
│   │   └── PsalmCommand.php
│   └── ApplicationState.php
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

## Middleware Chain

All API requests pass through:

1. **IpFilter** — Validates request IP against `allowedIPs` (default: `127.0.0.1`, `::1`)
2. **CorsAllowAll** — Adds permissive CORS headers
3. **ResponseDataWrapper** — Wraps all responses in `{id, data, error, success, status}`
4. **DebugHeaders** — Adds `X-Debug-Id` and `X-Debug-Link` response headers

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
