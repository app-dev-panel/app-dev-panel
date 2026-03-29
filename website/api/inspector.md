# Inspector Endpoints

Inspector endpoints query live application state. All routes are under `/inspect/api`.

## Core Endpoints

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
| GET | `/phpinfo` | PHP info output |

## Database

| Method | Path | Description |
|--------|------|-------------|
| GET | `/table` | Database tables list |
| GET | `/table/{name}` | Table schema + paginated records |

## Translations

| Method | Path | Description |
|--------|------|-------------|
| GET | `/translations` | Translation catalogs |
| PUT | `/translations` | Update a translation |

## Request

| Method | Path | Description |
|--------|------|-------------|
| PUT | `/request` | Re-execute a captured request |
| POST | `/curl/build` | Build cURL command from request |

## Git

| Method | Path | Description |
|--------|------|-------------|
| GET | `/git/summary` | Branch, SHA, remotes, branches |
| GET | `/git/log` | Last 20 commits |
| POST | `/git/checkout` | Switch branch |
| POST | `/git/command` | Run git pull/fetch |

## Commands

| Method | Path | Description |
|--------|------|-------------|
| GET | `/command/` | List available commands |
| POST | `/command/` | Execute a command |

## Composer

| Method | Path | Description |
|--------|------|-------------|
| GET | `/composer/` | composer.json + composer.lock |
| GET | `/composer/inspect` | Package details |
| POST | `/composer/require` | Install a package |

## Cache

| Method | Path | Description |
|--------|------|-------------|
| GET | `/cache/` | View cache entry |
| DELETE | `/cache/` | Delete cache key |
| POST | `/cache/clear` | Clear all cache |

## OPcache

| Method | Path | Description |
|--------|------|-------------|
| GET | `/opcache/` | OPcache status and configuration |

## MCP (AI Integration)

| Method | Path | Description |
|--------|------|-------------|
| POST | `/mcp/` | JSON-RPC 2.0 handler |
| GET | `/mcp/settings` | Get MCP enabled status |
| PUT | `/mcp/settings` | Set MCP enabled status |

## Multi-Service Proxying

Inspector requests with `?service=<name>` are proxied to the registered external service's URL via `InspectorProxyMiddleware`. Requests without `?service` or with `?service=local` are handled locally.
