# McpServer — MCP Protocol Server for ADP

MCP (Model Context Protocol) server that exposes ADP debug data and live application state to AI assistants.
Supports two transports: **stdio** (JSON-RPC over stdin/stdout) and **HTTP** (JSON-RPC over POST).
Two tool categories: **Debug** (read stored debug entries) and **Inspector** (query live app via HTTP).

## Dependencies

- `app-dev-panel/kernel` — StorageInterface for reading debug data
- Inspector API (HTTP) — Inspector tools query live app via `InspectorClient` (optional, no package dependency)

No external PHP packages required.

## Architecture

```
┌─────────────────────────────────────────────────┐
│              McpServer                           │
│                                                  │
│  process(message) → response                     │
│  run() → stdio loop (optional)                   │
│                                                  │
│  ┌──────────────────────────────────────────┐   │
│  │         ToolRegistry                      │   │
│  │                                           │   │
│  │  ┌─────────────────────────────┐         │   │
│  │  │  Debug Tools (6)            │         │   │
│  │  │  - list_debug_entries       │         │   │
│  │  │  - view_debug_entry         │         │   │
│  │  │  - search_logs              │         │   │
│  │  │  - analyze_exception        │         │   │
│  │  │  - view_database_queries    │         │   │
│  │  │  - view_timeline            │         │   │
│  │  └─────────────────────────────┘         │   │
│  │  ┌─────────────────────────────┐         │   │
│  │  │  Inspector Tools (3)        │         │   │
│  │  │  - inspect_config           │─────────┼───┼──▶ Inspector API
│  │  │  - inspect_routes           │         │   │    (GET /inspect/api/*)
│  │  │  - inspect_database_schema  │         │   │
│  │  └─────────────────────────────┘         │   │
│  └──────────────────────────────────────────┘   │
└─────────────────────────────────────────────────┘
         ▲                    ▲
         │                    │
    StdioTransport      McpController
    (bin/adp-mcp)       (POST /inspect/api/mcp)
```

## Directory Structure

```
libs/McpServer/
├── composer.json
├── CLAUDE.md
├── bin/
│   └── adp-mcp                          # Standalone stdio entry point
├── src/
│   ├── McpServer.php                    # Protocol handler (initialize, tools/list, tools/call, ping)
│   ├── McpToolRegistryFactory.php       # Creates ToolRegistry with all debug + inspector tools
│   ├── Inspector/
│   │   └── InspectorClient.php          # HTTP client for Inspector API (file_get_contents)
│   ├── Transport/
│   │   └── StdioTransport.php           # JSON-RPC over stdin/stdout (newline-delimited)
│   └── Tool/
│       ├── ToolInterface.php            # Tool contract: getName, getDescription, getInputSchema, execute
│       ├── ToolRegistry.php             # Registration + dispatch by name
│       ├── ToolResultTrait.php          # Shared text()/error() result builders
│       ├── Debug/
│       │   ├── ListEntriesTool.php      # list_debug_entries
│       │   ├── ViewEntryTool.php        # view_debug_entry
│       │   ├── SearchLogsTool.php       # search_logs
│       │   ├── AnalyzeExceptionTool.php # analyze_exception
│       │   ├── ViewDatabaseQueriesTool.php # view_database_queries
│       │   └── ViewTimelineTool.php     # view_timeline
│       └── Inspector/
│           ├── InspectConfigTool.php    # inspect_config
│           ├── InspectRoutesTool.php     # inspect_routes
│           └── InspectDatabaseSchemaTool.php # inspect_database_schema
└── tests/
    └── Unit/
        ├── McpServerTest.php            # Protocol handler tests (10 tests)
        ├── Transport/
        │   └── StdioTransportTest.php   # Transport tests (5 tests)
        └── Tool/
            ├── ToolRegistryTest.php     # Registry tests (4 tests)
            ├── Debug/
            │   ├── ListEntriesToolTest.php       # 5 tests
            │   ├── ViewEntryToolTest.php          # 4 tests
            │   ├── SearchLogsToolTest.php         # 5 tests
            │   ├── AnalyzeExceptionToolTest.php   # 6 tests
            │   ├── ViewDatabaseQueriesToolTest.php # 5 tests
            │   └── ViewTimelineToolTest.php       # 4 tests
            └── Inspector/
                ├── InspectConfigToolTest.php          # 9 tests
                ├── InspectRoutesToolTest.php           # 9 tests
                └── InspectDatabaseSchemaToolTest.php   # 11 tests
```

## Transports

### stdio (standalone)

For AI clients that launch a local process (Claude Code, Cursor):

```bash
php vendor/bin/adp-mcp --storage=/path/to/debug-data
# With inspector tools (live app queries):
php vendor/bin/adp-mcp --storage=/path/to/debug-data --inspector-url=http://localhost:8080
# or
php yii mcp:serve --storage-path=/path/to/debug-data --inspector-url=http://localhost:8080
```

Client config:
```json
{
  "mcpServers": {
    "adp": {
      "command": "php",
      "args": ["vendor/bin/adp-mcp", "--storage=/path/to/debug-data"]
    }
  }
}
```

Environment variables `ADP_STORAGE_PATH` and `ADP_INSPECTOR_URL` also accepted.
Inspector tools (config, routes, database schema) are only registered when `--inspector-url` is provided.

### HTTP (integrated into ADP server)

Automatically available when ADP server runs via any adapter or `debug:serve`.

The endpoint URL follows the pattern `http://<host>:<port>/inspect/api/mcp`, where `<host>:<port>`
is the address of the running application with ADP installed.

```
POST /inspect/api/mcp
Content-Type: application/json

{"jsonrpc":"2.0","id":1,"method":"tools/list"}
```

**Direct URL client config** (for MCP clients that support HTTP URLs):
```json
{
  "mcpServers": {
    "AppDevPanel": {
      "url": "http://localhost:8080/inspect/api/mcp"
    }
  }
}
```

**Via `mcp-remote` proxy** (for MCP clients that only support stdio, e.g., Claude Desktop):
```json
{
  "mcpServers": {
    "AppDevPanel": {
      "command": "npx",
      "args": ["-y", "mcp-remote", "http://localhost:8080/inspect/api/mcp"]
    }
  }
}
```

Replace `localhost:8080` with your application's actual address and port.
For playground servers: Yii3 `:8101`, Symfony `:8102`, Yii2 `:8103`, Laravel `:8104`, Spiral `:8105`.

The HTTP endpoint:
- Bypasses `ResponseDataWrapper` (JSON-RPC has its own envelope)
- Inherits IP filter and token auth from API middleware
- Returns 204 for notifications (no JSON-RPC `id`)
- Returns 400 for parse errors
- Can be enabled/disabled via `PUT /inspect/api/mcp/settings` (returns -32000 when disabled)

### Settings API

| Method | Path | Description |
|--------|------|-------------|
| GET | `/inspect/api/mcp/settings` | Get MCP enabled status: `{enabled: bool}` |
| PUT | `/inspect/api/mcp/settings` | Set MCP enabled status: body `{enabled: bool}` |

The enabled state is persisted as `mcp-settings.json` in the storage directory.
The frontend exposes a toggle in Settings > MCP Server.

## MCP Protocol

Implements MCP spec version `2024-11-05`. JSON-RPC 2.0 methods:

| Method | Type | Purpose |
|--------|------|---------|
| `initialize` | request | Handshake — returns server capabilities |
| `initialized` | notification | Client acknowledgment |
| `ping` | request | Health check |
| `tools/list` | request | List available tools with JSON Schema |
| `tools/call` | request | Execute a tool |

## Tools

### `list_debug_entries`

List recent debug entries with summary info.

| Parameter | Type | Required | Default |
|-----------|------|----------|---------|
| `limit` | integer | no | 20 |
| `filter` | string | no | — |

Filter matches against full entry JSON (URL, method, status, collector names).

### `view_debug_entry`

View full collector data for a specific entry.

| Parameter | Type | Required |
|-----------|------|----------|
| `id` | string | yes |
| `collector` | string | no |

Collector filter matches short name ("log", "database") or FQCN substring.
Truncates output at 10,000 chars per collector.

### `search_logs`

Search log messages across all entries.

| Parameter | Type | Required |
|-----------|------|----------|
| `query` | string | yes |
| `level` | string (enum) | no |
| `limit` | integer | no |

Scans entries newest-first. Matches against message text and JSON-encoded context.

### `analyze_exception`

Exception details with stack trace and context.

| Parameter | Type | Required |
|-----------|------|----------|
| `id` | string | no |

If `id` omitted, auto-finds the latest entry with an exception.
Includes: exception chain, stack trace, related request data, last 20 log messages.

### `view_database_queries`

SQL queries with timing and N+1 detection.

| Parameter | Type | Required |
|-----------|------|----------|
| `id` | string | no |
| `slow_only` | boolean | no |

Slow threshold: 100ms. Reports duplicate query groups. If `id` omitted, uses latest entry.

### `view_timeline`

Performance timeline from all collectors.

| Parameter | Type | Required |
|-----------|------|----------|
| `id` | string | no |

Shows first 100 events with offset from first event in milliseconds.

### `inspect_config` (Inspector)

View application configuration, parameters, and event listeners from the live app.

| Parameter | Type | Required | Default |
|-----------|------|----------|---------|
| `action` | string (enum: params, config, events) | no | params |
| `group` | string | no | — |
| `filter` | string | no | — |
| `service` | string | no | — |

Actions: `params` → `GET /inspect/api/params`, `config` → `GET /inspect/api/config`, `events` → `GET /inspect/api/events`.
Filter applies case-insensitive text search on keys. Truncates output at 15,000 chars.

### `inspect_routes` (Inspector)

List application routes or check if a specific path matches a route.

| Parameter | Type | Required | Default |
|-----------|------|----------|---------|
| `action` | string (enum: list, check) | no | list |
| `path` | string | no (required for check) | — |
| `filter` | string | no | — |
| `service` | string | no | — |

List: `GET /inspect/api/routes` → markdown table (Methods | Pattern | Name).
Check: `GET /inspect/api/route/check?route=<path>` → match result with handler action.

### `inspect_database_schema` (Inspector)

Browse database tables and view column/index details.

| Parameter | Type | Required | Default |
|-----------|------|----------|---------|
| `table` | string | no | — |
| `filter` | string | no | — |
| `service` | string | no | — |

Without `table`: `GET /inspect/api/table` → table list with row counts and sizes.
With `table`: `GET /inspect/api/table/{name}?limit=0` → columns (name, type, nullable, default, PK) and indexes.
Filter applies case-insensitive search on table names (list mode only).

## Key Classes

| Class | Purpose |
|-------|---------|
| `McpServer` | Protocol handler. `process(array): ?array` for HTTP, `run()` for stdio loop |
| `StdioTransport` | Newline-delimited JSON-RPC over stdin/stdout |
| `ToolInterface` | Contract: `getName()`, `getDescription()`, `getInputSchema()`, `execute(array): array` |
| `ToolRegistry` | Stores tools by name, returns `list()` for MCP, dispatches `get()` by name |
| `McpToolRegistryFactory` | Static `create(StorageInterface, ?InspectorClient)` — builds registry with debug + inspector tools |
| `ToolResultTrait` | `text(string)` and `error(string)` helpers for MCP tool responses |
| `InspectorClient` | HTTP client for Inspector API. `get(path, query)` / `post(path, body)` via `file_get_contents` |

## Adding New Tools

1. Create a class implementing `ToolInterface` in the appropriate directory
2. Register it in `McpToolRegistryFactory::create()`
3. Write tests using `MemoryStorage` for data injection
4. Tool response format: `['content' => [['type' => 'text', 'text' => '...']], 'isError' => bool]`

## Integration Points

| Module | Integration |
|--------|-------------|
| **API** | `McpController` at `POST /inspect/api/mcp`, route in `ApiRoutes::inspectorRoutes()` |
| **API** | `McpSettingsController` at `GET/PUT /inspect/api/mcp/settings` |
| **API** | `McpSettings` — file-based enabled/disabled persistence (`mcp-settings.json`) |
| **API** | `ApiApplication::buildPipeline()` skips `ResponseDataWrapper` for MCP path |
| **Cli** | `McpServeCommand` (`mcp:serve`) for stdio standalone, `--inspector-url` option |
| **Cli** | `server-router.php` registers `McpController` + `McpSettingsController` with `InspectorClient` |
| **Kernel** | Debug tools read from `StorageInterface` (FileStorage in production) |
| **Adapters** | Yii3, Symfony, Laravel, Yii2, Spiral — wire `InspectorClient` with app's own base URL |
| **Frontend** | MCP Server toggle in Settings dialog, RTK Query for `getMcpSettings`/`updateMcpSettings` |

## Test Summary

83 tests, 173 assertions. Debug tests use `MemoryStorage`, Inspector tests use mocked `InspectorClient` — no I/O.

| Suite | Tests |
|-------|------:|
| McpServerTest | 10 |
| StdioTransportTest | 5 |
| ToolRegistryTest | 4 |
| Debug tool tests (6 files) | 29 |
| Inspector tool tests (3 files) | 29 |
| McpControllerTest (in API) | 6 |
| **Total** | **83** |
