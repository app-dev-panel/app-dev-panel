# API Module

HTTP layer for ADP. Five domains: **Debug** (stored debug entries), **Inspector** (live app state), **Ingestion** (external data intake), **MCP** (AI assistant integration), **LLM** (AI chat and analysis). LLM supports four providers: OpenRouter, Anthropic, OpenAI (HTTP APIs), and ACP (Agent Client Protocol — spawns local AI agents like Claude Code via stdio subprocess).

## Package

- Composer: `app-dev-panel/api`
- Namespace: `AppDevPanel\Api\`
- PHP: 8.4+
- Dependencies: `app-dev-panel/kernel`, `app-dev-panel/mcp-server`, `gitonomy/gitlib`, `guzzlehttp/guzzle`, `zircote/swagger-php`

## Directory Structure

```
src/
├── ApiApplication.php                   # Main application bootstrap
├── ApiConfig.php                        # Core API configuration
├── ApiExtensionsConfig.php              # Extension points configuration
├── ApiSecurityConfig.php                # Security configuration (IP, token auth)
├── ApiRoutes.php                        # All route definitions (debug, inspector, ingestion, mcp, llm, service)
├── Debug/
│   ├── Controller/
│   │   ├── DebugController.php          # Debug data endpoints (list, summary, view, dump, object, SSE)
│   │   └── SettingsController.php       # Debug settings (path mapping)
│   ├── Middleware/
│   │   ├── ResponseDataWrapper.php      # Wraps responses in {id, data, error, success}
│   │   ├── DebugHeaders.php             # Adds X-Debug-Id, X-Debug-Link headers
│   │   └── TokenAuthMiddleware.php      # Token-based authentication
│   ├── Repository/
│   │   ├── CollectorRepositoryInterface.php
│   │   └── CollectorRepository.php      # Reads debug data from storage
│   ├── Exception/
│   │   ├── NotFoundException.php            # Debug entry not found
│   │   └── PackageNotInstalledException.php # Required package missing
│   ├── HtmlViewProviderInterface.php
│   ├── ModuleFederationAssetBundle.php  # Remote panel support
│   └── ModuleFederationProviderInterface.php
├── Panel/
│   ├── PanelConfig.php                      # Panel display configuration
│   └── PanelController.php                  # Serves embedded debug panel SPA
├── Inspector/
│   ├── Controller/                      # Inspector mode — live app state
│   │   ├── InspectController.php        # config, params, classes, object, phpinfo, events
│   │   ├── RoutingController.php        # routes, route check
│   │   ├── DatabaseController.php       # table list, table data, explain, query
│   │   ├── FileController.php           # file explorer, file read
│   │   ├── TranslationController.php    # translation catalogs, update
│   │   ├── RequestController.php        # re-execute request, build cURL
│   │   ├── GitController.php            # git summary, log, checkout, commands
│   │   ├── GitRepositoryProvider.php    # Git repository instance factory
│   │   ├── CommandController.php        # list/execute commands + composer scripts
│   │   ├── ComposerController.php       # composer.json/lock, inspect, require
│   │   ├── CacheController.php          # view/delete/clear cache
│   │   ├── OpcacheController.php        # OPcache status
│   │   ├── AuthorizationController.php  # live auth config (guards, role hierarchy, voters)
│   │   ├── ElasticsearchController.php  # Elasticsearch cluster health, indices, search, raw query
│   │   ├── RedisController.php          # Redis inspection (ping, info, keys, get, delete, flush)
│   │   ├── CodeCoverageController.php   # Code coverage (pcov/xdebug)
│   │   └── ServiceController.php        # Service registration (register, heartbeat, list, deregister)
│   ├── Middleware/
│   │   └── InspectorProxyMiddleware.php # Proxies inspector requests to external services
│   ├── Authorization/
│   │   ├── AuthorizationConfigProviderInterface.php  # Interface for live auth config
│   │   └── NullAuthorizationConfigProvider.php       # Default no-op fallback
│   ├── Database/
│   │   ├── SchemaProviderInterface.php  # Interface for database schema inspection
│   │   └── NullSchemaProvider.php       # Default no-op fallback
│   ├── Elasticsearch/
│   │   ├── ElasticsearchProviderInterface.php  # Interface for ES cluster inspection
│   │   └── NullElasticsearchProvider.php       # Default no-op fallback
│   ├── Command/
│   │   ├── CommandInterface.php
│   │   ├── CommandResponse.php
│   │   ├── BashCommand.php
│   │   ├── PHPUnitCommand.php
│   │   ├── CodeceptionCommand.php
│   │   └── PsalmCommand.php
│   ├── Test/
│   │   ├── CodeceptionJSONReporter.php
│   │   └── PHPUnitJSONReporter.php
│   └── ApplicationState.php
├── Ingestion/
│   └── Controller/
│       ├── IngestionController.php      # External data intake (any language)
│       └── OtlpController.php           # OpenTelemetry trace ingestion (OTLP format)
├── Mcp/
│   ├── Controller/
│   │   ├── McpController.php            # JSON-RPC 2.0 MCP handler
│   │   └── McpSettingsController.php    # MCP enabled/disabled settings
│   └── McpSettings.php                  # File-based MCP settings persistence
├── Llm/
│   ├── Controller/
│   │   └── LlmController.php           # LLM integration (connect, chat, analyze, history, OAuth)
│   ├── Acp/
│   │   ├── AcpDaemonManager.php          # Daemon lifecycle: start/stop, session management, Unix socket IPC
│   │   ├── AcpDaemonManagerInterface.php # Interface for daemon manager (start, startSession, sendPrompt, etc.)
│   │   ├── acp-daemon-runner.php         # Standalone daemon process (multi-session, Unix socket server)
│   │   ├── AcpCommandVerifier.php       # Checks if agent command exists on PATH
│   │   ├── AcpCommandVerifierInterface.php # Interface for command verification
│   │   └── AcpResponse.php              # Value object for ACP agent response
│   ├── FileLlmHistoryStorage.php        # File-based chat history
│   ├── FileLlmSettings.php              # File-based LLM settings
│   ├── LlmHistoryStorageInterface.php
│   ├── LlmProviderService.php          # Provider dispatch (OpenRouter, Anthropic, OpenAI, ACP)
│   └── LlmSettingsInterface.php
├── Http/
│   ├── JsonResponseFactory.php          # JSON response creation
│   └── JsonResponseFactoryInterface.php
├── Middleware/
│   ├── IpFilterMiddleware.php           # IP whitelist validation
│   ├── CorsMiddleware.php               # Permissive CORS headers
│   └── MiddlewarePipeline.php           # Middleware chain executor
├── Router/
│   ├── Route.php                        # Route definition
│   └── Router.php                       # Request-to-route matching
├── PathMapper.php                       # IDE file path mapping
├── PathMapperInterface.php
├── NullPathMapper.php
├── PathResolver.php                     # Path resolution
├── PathResolverInterface.php
└── ServerSentEventsStream.php           # SSE implementation
```

## API Endpoints

### Panel (`/debug`)

| Method | Path | Description |
|--------|------|-------------|
| GET | `/` | Serve debug panel SPA |
| GET | `/{path+}` | SPA catch-all routing |

### Debug API (`/debug/api`)

| Method | Path | Description |
|--------|------|-------------|
| GET | `/` | List all debug entries (summaries) |
| GET | `/summary/{id}` | Single entry summary |
| GET | `/view/{id}` | Full entry data (optionally filtered by collector) |
| GET | `/dump/{id}` | Dump objects for entry |
| GET | `/object/{id}/{objectId}` | Specific object from dump |
| GET | `/event-stream` | SSE stream for real-time updates |
| GET | `/settings` | Debug settings (path mapping) |

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
| POST | `/table/explain` | Explain SQL query |
| POST | `/table/query` | Execute raw SQL query |
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

### Authorization API (`/inspect/api/authorization`)

| Method | Path | Description |
|--------|------|-------------|
| GET | `/` | Guards, role hierarchy, voters/policies, security config |

Requires `AuthorizationConfigProviderInterface` implementation from adapter. Falls back to `NullAuthorizationConfigProvider` (empty arrays).

### Elasticsearch API (`/inspect/api/elasticsearch`)

| Method | Path | Description |
|--------|------|-------------|
| GET | `/` | Cluster health + indices list |
| GET | `/{name}` | Index detail (mappings, settings, stats) |
| POST | `/search` | Execute search query against an index |
| POST | `/query` | Execute raw Elasticsearch query |

Backed by `ElasticsearchProviderInterface`. Default: `NullElasticsearchProvider` (returns empty data). Adapters provide concrete implementations.

### Redis API (`/inspect/api/redis`)

| Method | Path | Description |
|--------|------|-------------|
| GET | `/ping` | Test Redis connection |
| GET | `/info` | Server info (`INFO` command, optional `?section=`) |
| GET | `/db-size` | Number of keys in current DB |
| GET | `/keys` | Browse keys via SCAN (`?pattern=*&limit=100&cursor=0`) |
| GET | `/get` | Get key value (type-aware: string/list/set/zset/hash/stream) with TTL |
| DELETE | `/delete` | Delete a key |
| POST | `/flush-db` | Flush current database |

Requires `\Redis` (phpredis extension) in the DI container.

### Code Coverage API (`/inspect/api/coverage`)

| Method | Path | Description |
|--------|------|-------------|
| GET | `/` | Collect and return PHP code coverage data (requires pcov or xdebug) |
| GET | `/file` | Read a source file (`?path=`) |

### MCP API (`/inspect/api/mcp`)

JSON-RPC 2.0 endpoint for AI assistant integration via Model Context Protocol.

| Method | Path | Description |
|--------|------|-------------|
| POST | `/` | JSON-RPC 2.0 handler (initialize, ping, tools/list, tools/call) |
| GET | `/settings` | Get MCP enabled status: `{enabled: bool}` |
| PUT | `/settings` | Set MCP enabled status: body `{enabled: bool}` |

The MCP endpoint bypasses `ResponseDataWrapper` — JSON-RPC uses its own envelope.
Returns -32000 error when MCP is disabled via settings.

### Ingestion API (`/debug/api/ingest`)

Language-agnostic endpoints for external applications to send debug data. Defined by OpenAPI 3.1 spec at `openapi/ingestion.yaml`.

| Method | Path | Description |
|--------|------|-------------|
| POST | `/` | Ingest single debug entry (collectors + optional context/summary) |
| POST | `/batch` | Ingest multiple entries at once (max 100) |
| POST | `/log` | Shorthand: ingest a single log entry |
| GET | `/openapi.json` | Serve the OpenAPI spec |

### OTLP Trace Ingestion (`/debug/api/otlp`)

| Method | Path | Description |
|--------|------|-------------|
| POST | `/v1/traces` | Ingest OpenTelemetry traces in OTLP format |

Pre-built clients: Python (`clients/python/`), TypeScript (`clients/typescript/`).

### LLM API (`/debug/api/llm`)

AI-powered chat and analysis integration.

| Method | Path | Description |
|--------|------|-------------|
| GET | `/status` | LLM connection status |
| POST | `/connect` | Connect to LLM provider (API key) |
| POST | `/oauth/initiate` | Start OAuth flow for LLM provider |
| POST | `/oauth/exchange` | Exchange OAuth code for token |
| POST | `/disconnect` | Disconnect from LLM provider |
| POST | `/model` | Set active model |
| POST | `/timeout` | Set request timeout |
| POST | `/custom-prompt` | Set custom system prompt |
| GET | `/models` | List available models |
| POST | `/chat` | Send chat message |
| POST | `/analyze` | Analyze debug entry with AI |
| GET | `/history` | Get chat history |
| POST | `/history` | Add history entry |
| DELETE | `/history/{index}` | Delete specific history entry |
| DELETE | `/history` | Clear all history |

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

`openapi/inspector.yaml` defines the Inspector API contract (OpenAPI 3.1) that external applications must implement to be proxied. Capabilities map to endpoint groups: `config`, `routes`, `files`, `cache`, `database`, `translations`, `events`, `commands`, `git`, `classes`, `object`, `phpinfo`, `opcache`, `request`, `composer`, `authorization`.

## Middleware Chain

All API requests pass through:

1. **IpFilterMiddleware** — Validates request IP against `allowedIPs` (default: `127.0.0.1`, `::1`)
2. **CorsMiddleware** — Adds permissive CORS headers (`Access-Control-Allow-Origin: *`)
3. **ResponseDataWrapper** — Wraps all responses in `{id, data, error, success}`
4. **TokenAuthMiddleware** — Optional token-based authentication

Inspector route group (`/inspect/api`) additionally includes:

5. **InspectorProxyMiddleware** — Routes requests with `?service=<name>` to external service URLs

## Response Format

All API responses are wrapped:

```json
{
    "id": "debug-entry-id",
    "data": { ... },
    "error": null,
    "success": true
}
```

## SSE (Server-Sent Events)

The `/debug/api/event-stream` endpoint polls storage every second, computing an MD5 hash
of the summary data. When a new debug entry is written, the hash changes and an event is emitted:

```
data: {"type": "debug-updated", "payload": []}
```

The frontend listens for this event and refreshes the debug entry list.
