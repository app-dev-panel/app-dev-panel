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
│   │   ├── NotFoundException.php            # Debug entry not found (-> 404)
│   │   ├── BadRequestException.php          # Malformed client input (-> 400)
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
│   │   ├── HttpMockController.php       # HTTP mock expectations, verify, history, reset (Phiremock)
│   │   └── ServiceController.php        # Service registration (register, heartbeat, list, deregister)
│   ├── Middleware/
│   │   └── InspectorProxyMiddleware.php # Proxies inspector requests to external services (UrlPolicy re-check)
│   ├── Coverage/
│   │   └── StoredCoverageReader.php     # Reads CodeCoverageCollector payload from stored entries
│   ├── Authorization/
│   │   ├── AuthorizationConfigProviderInterface.php  # Interface for live auth config
│   │   └── NullAuthorizationConfigProvider.php       # Default no-op fallback
│   ├── Database/
│   │   ├── SchemaProviderInterface.php  # Interface for database schema inspection
│   │   └── NullSchemaProvider.php       # Default no-op fallback
│   ├── Elasticsearch/
│   │   ├── ElasticsearchProviderInterface.php  # Interface for ES cluster inspection
│   │   └── NullElasticsearchProvider.php       # Default no-op fallback
│   ├── HttpMock/
│   │   ├── HttpMockProviderInterface.php       # HTTP mock backend (expectations, history, verify)
│   │   ├── NullHttpMockProvider.php            # Default no-op fallback
│   │   └── PhiremockProvider.php               # Backend implementation via Phiremock HTTP API
│   ├── Command/
│   │   ├── CommandInterface.php
│   │   ├── CommandResponse.php
│   │   ├── CommandTimeout.php           # 120s hard ceiling + per-request `?timeout=` clamp
│   │   ├── ProcessCommandTrait.php      # Shared Process runner (timeout, timed-out response)
│   │   ├── BashCommand.php
│   │   ├── PHPUnitCommand.php           # JSON-report variant (uses PHPUnitJSONReporter)
│   │   ├── PHPUnitRawCommand.php        # raw stdout/stderr variant
│   │   ├── CodeceptionCommand.php       # JSON-report variant (uses CodeceptionJSONReporter)
│   │   ├── CodeceptionRawCommand.php    # raw stdout/stderr variant
│   │   └── PsalmCommand.php
│   ├── Test/
│   │   ├── PHPUnitJSONReporter.php        # PHPUnit 10+ Extension, writes phpunit-report.json
│   │   ├── PHPUnitReportCollector.php     # In-memory collector used by PHPUnitJSONReporter
│   │   └── CodeceptionJSONReporter.php    # Codeception 5+ Extension, writes codeception-report.json
│   └── ApplicationState.php
├── Ingestion/
│   ├── OpenApiSpecLoader.php            # Locates + parses openapi/ingestion.yaml (ext-yaml or symfony/yaml)
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
│   │   ├── AcpSocketLocator.php          # Socket path (<storage>/.acp/daemon.sock, 0700) + trust checks
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
├── Security/
│   ├── DebugIdValidator.php             # 400-throwing wrapper over Kernel `Storage\StorageIdValidator` (`[A-Za-z0-9_-]{1,64}`)
│   ├── ClassNameValidator.php           # FQCN syntax guard before class_exists()/is_subclass_of() on input
│   ├── UrlPolicy.php                    # SSRF guard for inspectorUrl (scheme, userinfo, forbidden ranges, DNS; strict mode)
│   ├── NetworkAddressClassifier.php     # forbidden / private / public IP buckets (CIDR), loopback hostname helper
│   └── SystemDnsResolver.php            # Default A+AAAA resolver for UrlPolicy (tests inject a closure instead)
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
├── PathResolver.php                     # Path resolution + static isInside()/canonical()/stripPrefix() helpers
├── PathResolverInterface.php
└── ServerSentEventsStream.php           # SSE implementation
```

## API Endpoints

### Panel (`/debug`)

| Method | Path | Description |
|--------|------|-------------|
| GET | `/debug` | Serve debug panel SPA (`PanelController::index`) |
| GET | `/debug/{path+}` | SPA catch-all for client-side routing, excludes `/debug/api/*` |

`PanelController` only renders the bootstrap HTML and resolves `bundle.js`/`bundle.css` via
`PanelConfig::$staticUrl`. Each adapter is responsible for **publishing** the
`app-dev-panel/frontend-assets` bundle into a public directory the web server can serve directly:
Symfony copies into `public/bundles/appdevpanel/`, Laravel into `public/vendor/app-dev-panel`,
Yii 2/3 symlink into `@webroot/app-dev-panel`. The API module never streams static files itself —
that work belongs to the web server.

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
| POST | `/` | Execute a command. Optional `?timeout=<seconds>` shortens the run limit |

Every subprocess-backed command (`BashCommand`, `MagoCommand`, `PsalmCommand`, `PHPStanCommand`, `PHPUnitCommand`, `PHPUnitRawCommand`, `CodeceptionCommand`, `CodeceptionRawCommand`, `PestCommand`, `TestoCommand`) uses `ProcessCommandTrait`: the Symfony `Process` runs with `CommandTimeout::DEFAULT` (120 s, matches the fixture ceiling in the root CLAUDE.md — never raise). `withTimeout()` / `?timeout=` can only shorten it (`CommandTimeout::clamp()`). A timed-out process is killed and reported as `status: fail` with `errors: ["Command timed out after N seconds."]`. `MagoCommand::isAvailable()` memoises its `command -v mago` lookup (5 s `Process`, no `@shell_exec`).

### Composer API (`/inspect/api/composer`)

| Method | Path | Description |
|--------|------|-------------|
| GET | `/` | composer.json + composer.lock |
| GET | `/inspect` | Package details |
| POST | `/require` | Install package (guarded by `allowDestructiveOperations`, 403 otherwise) |

`ComposerController` never spawns `composer` itself: it hands the argv (`['composer', 'show', <pkg>, '--all', '--format=json']` / `['composer', 'require', '<pkg>:<version|*>', '-n'[, '--dev']]`) to an injectable runner — fourth constructor argument `callable(list<string>): CommandResponse`, default `BashCommand` (Symfony `Process` under the 120 s `CommandTimeout` ceiling). Unit tests inject a recording fake, so no process, composer binary or network is involved. `inspect` extracts the first JSON object from the output (composer prints root/su warnings around it); `require` returns the raw text output as `result` unless it is a JSON document.

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
| GET | `/` | Coverage recorded by `CodeCoverageCollector` for a stored entry: `?debugEntryId=<id>` or the newest entry whose summary has `codeCoverage`. 200 with `error` when no driver; 501 when the controller has no `CollectorRepositoryInterface` (adapter must wire it); 404 when no entry carries coverage |
| GET | `/file` | Read a source file (`?path=`), must resolve inside the project root |

The endpoint never starts/stops the driver itself (that only ever measured the controller and reported 0%).

### HTTP Mock API (`/inspect/api/http-mock`)

| Method | Path | Description |
|--------|------|-------------|
| GET | `/status` | Backend availability (enabled, provider name, reachable URL) |
| GET | `/expectations` | List registered expectations |
| POST | `/expectations` | Register a new expectation (mock rule) |
| DELETE | `/expectations` | Clear all expectations |
| GET | `/verify` | Verify executed requests against expectations |
| GET | `/history` | Request history captured by the mock backend |
| POST | `/reset` | Reset expectations + history |

Backed by `HttpMockProviderInterface`. Default `NullHttpMockProvider` returns "disabled". `PhiremockProvider` proxies to a running Phiremock server.

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
| GET | `/openapi.json` | Serve the OpenAPI spec (decoded once via `OpenApiSpecLoader`, ext-yaml or symfony/yaml) |

A client-supplied `debugId` must match `DebugIdValidator::PATTERN` (= Kernel `StorageIdValidator::PATTERN`, `[A-Za-z0-9_-]{1,64}`, compatible with `DebuggerIdGenerator`) — it becomes a storage path segment. Anything else is a `BadRequestException` (400) and nothing is written. `CollectorRepository` applies the same validator on every read, so `/debug/api/{summary,view,dump,object}/{id}` and `?debugEntryId=` (RequestController, CodeCoverageController) reject traversal ids before they reach storage. Defense in depth: `FileStorage` / `SqliteStorage` re-validate every `read($type, $id)` / `write($id, …)` themselves and throw `InvalidArgumentException` (-> 500) for anything the API validator would have rejected, so a bypassed controller still cannot build a path from `../`.

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

### Project Config API (`/debug/api/project`)

Persists team-shared panel configuration (Frames, OpenAPI specs) into a VCS-tracked
`config/adp/project.json` so every developer sees the same setup after `git pull`.
Implemented in `Project/Controller/ProjectController.php`, backed by Kernel's
`ProjectConfigStorageInterface`. Each adapter wires the storage to a framework-specific
config dir (see Kernel module CLAUDE.md).

| Method | Path | Description |
|--------|------|-------------|
| GET | `/config` | Returns `{config: {version, frames, openapi}, configDir}`. `configDir` is the absolute path the user can `git add` |
| PUT | `/config` | Accepts either a bare `{frames, openapi}` document or the GET wrapper `{config: {...}}`. Malformed entries (non-string keys/values, empty strings) are dropped silently |

The frontend's `Module/Project` keeps a `localStorage` cache via `redux-persist`. On
boot it dispatches `getProjectConfig` and overwrites the local Frames/OpenAPI slices
with the server document — except when the server is empty and the local cache has
data, which fires a one-shot migration `PUT` to seed the new file. User edits to either
slice are debounced (500 ms) into a single `PUT`.

### Service Registry API (`/debug/api/services`)

Manages external application registrations for multi-app inspector proxying.

| Method | Path | Description |
|--------|------|-------------|
| POST | `/register` | Register an external service (body: `service`, `inspectorUrl`, `language`, `capabilities`) |
| POST | `/heartbeat` | Heartbeat to keep service online (body: `service`) |
| GET | `/` | List all registered services with online/offline status |
| DELETE | `/{service}` | Deregister a service by name |

Service name `local` is reserved for the host PHP application.

`inspectorUrl` is validated by `Security\UrlPolicy` at registration **and** re-checked by `InspectorProxyMiddleware` right before `sendRequest()` (502 `Service inspector URL is rejected: …`). Always enforced: only `http`/`https`, no userinfo, host required, unresolvable hosts fail closed, and neither a literal IP nor any resolved address may be **forbidden** — link-local / cloud metadata (`169.254.0.0/16`, `fe80::/10`), unspecified (`0.0.0.0/8`, `::`), multicast (`224.0.0.0/4`, `ff00::/8`), reserved (`240.0.0.0/4`); IPv4-mapped IPv6 is unwrapped first. **Private** targets — `localhost`/`*.localhost`, loopback, RFC1918, CGNAT `100.64.0.0/10`, ULA `fc00::/7`, site-local `fec0::/10` — are accepted by default (inspected services normally run on localhost / in the same docker network). `ApiSecurityConfig::$restrictInspectorUrlsToPublicHosts = true` (build `new UrlPolicy(restrictToPublicHosts: true)`) is the strict mode that refuses private targets too. The DNS resolver is injectable (`new UrlPolicy(false, $resolver)`) so tests never touch the network; the default is `SystemDnsResolver::resolve()`.

### Inspector Proxy

`InspectorProxyMiddleware` is wired into the `/inspect/api` route group. When a request includes `?service=<name>`, the middleware proxies the request to the registered service's `inspectorUrl` instead of handling it locally. Requests without `?service` or with `?service=local` are handled by the local PHP controllers.

Capability checking: the middleware maps inspector path prefixes to capability names (e.g., `/routes` -> `routes`, `/table` -> `database`). If the target service does not declare the required capability, a 501 response is returned.

### Inspector OpenAPI Spec

`openapi/inspector.yaml` defines the Inspector API contract (OpenAPI 3.1) that external applications must implement to be proxied. Capabilities map to endpoint groups: `config`, `routes`, `files`, `cache`, `database`, `translations`, `events`, `commands`, `git`, `classes`, `object`, `phpinfo`, `opcache`, `request`, `composer`, `authorization`.

## Middleware Chain

All API requests pass through:

1. **IpFilterMiddleware** — Validates request IP against `allowedIPs` (default: `127.0.0.1`, `::1`)
2. **CorsMiddleware** — Adds permissive CORS headers (`Access-Control-Allow-Origin: *`)
3. **ResponseDataWrapper** — Wraps all responses in `{id, data, error, success}`; `NotFoundException` -> 404, `BadRequestException` -> 400, any other throwable -> 500 with `data.class`/`data.message` (+ `file`, `line`, `trace` only while `exposeExceptionDetails` is on — constructor flag, default `true`, exposed as `ApiSecurityConfig::$exposeExceptionDetails`)
4. **TokenAuthMiddleware** — Optional token-based authentication

Inspector route group (`/inspect/api`) additionally includes:

5. **InspectorProxyMiddleware** — Routes requests with `?service=<name>` to external service URLs

## Security Config Flags (`ApiSecurityConfig`)

| Flag | Default | Effect |
|------|---------|--------|
| `allowedIps` | `['127.0.0.1', '::1']` | `IpFilterMiddleware` whitelist |
| `authToken` | `''` | `TokenAuthMiddleware` (off when empty) |
| `requestReplayAllowedHosts` | `['127.0.0.1', 'localhost']` | Hosts `RequestController::request` may replay to |
| `allowDestructiveOperations` | `false` | Command execution, composer require, cache clear, raw SQL |
| `restrictInspectorUrlsToPublicHosts` | `false` | Pass to `UrlPolicy` — strict mode refusing loopback/RFC1918/ULA `inspectorUrl` targets (link-local, metadata, unspecified, multicast, reserved, non-http(s), userinfo are refused in both modes) |
| `exposeExceptionDetails` | `true` | Pass to `ResponseDataWrapper` — include `file`/`line`/`trace` in 500 responses |

Adapters own the wiring: `ServiceController`, `InspectorProxyMiddleware` (`UrlPolicy`), `ResponseDataWrapper` and `CodeCoverageController` (`CollectorRepositoryInterface`) all default to the secure/legacy-compatible value when the extra constructor argument is omitted.

## Input Guards (`Security/`)

- `DebugIdValidator::assertValid($id, $field)` — throws `BadRequestException`; delegates the format to Kernel `Storage\StorageIdValidator`; used by `IngestionController` (write) and `CollectorRepository::loadData()` (every read).
- `ClassNameValidator::classExists()` / `isSubclassOf()` — only a syntactically valid FQCN reaches the autoloader (`FileController ?class=`, `DebugController ?collector=`).
- `PathResolver::isInside($root, $path)` — realpath both sides, compare with trailing `DIRECTORY_SEPARATOR` (`/srv/app-backup/.env` is outside `/srv/app`); used by `FileController` and `CodeCoverageController::file`.
- `AcpSocketLocator` — ACP daemon socket at `<storagePath>/.acp/daemon.sock` in a `0700` owner-only directory (per-user temp fallback only when the path exceeds the 100-byte `sun_path` limit); directory owner/mode and socket node type/owner are verified before every `stream_socket_client()`.
- `FileLlmSettings` logs the legacy `.llm-settings.json` migration through an optional PSR-3 logger (third constructor arg) instead of writing to STDERR.

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

## Serialization

Inspector controllers (`InspectController`, `AuthorizationController`, `CacheController`,
`GitController`, `RoutingController`, `RequestController`, `TranslationController`) serialise
their responses through `AppDevPanel\Kernel\Inspector\Primitives::dump($value, $depth)` — **not**
`VarDumper::create(...)->asPrimitives(...)` directly. `Primitives::dump()` recursively walks
arrays and replaces every `Closure` with a `ClosureDescriptor` marker so the frontend can render
it as a syntax-highlighted PHP block. Use this helper for any new inspector endpoint that might
surface framework-level data with closures (DI definitions, event listeners, route handlers,
translator fallbacks, etc.).

## SSE (Server-Sent Events)

The `/debug/api/event-stream` endpoint polls storage every second, computing an MD5 hash
of the summary data. When a new debug entry is written, the hash changes and an event is emitted:

```
data: {"type": "debug-updated", "payload": []}
```

The frontend listens for this event and refreshes the debug entry list.
