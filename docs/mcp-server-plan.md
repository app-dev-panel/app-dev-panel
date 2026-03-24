# MCP Server for ADP — Detailed Plan

Status: **Phase 1-2 implemented** (core + debug tools + HTTP transport). Date: 2026-03-24.

### Implementation Status

| Phase | Status | Details |
|-------|--------|---------|
| Phase 1: Core infrastructure | **Done** | McpServer, StdioTransport, ToolInterface, ToolRegistry, bin/adp-mcp |
| Phase 2: Debug tools | **Done** | 6 tools: list_debug_entries, view_debug_entry, search_logs, analyze_exception, view_database_queries, view_timeline |
| Phase 3: Inspector tools | Planned | list_routes, check_route, database_schema, query_database, etc. |
| Phase 4: Resources | Planned | debug://entries, debug://schema, debug://routes |
| Phase 5: CLI + config | **Partial** | mcp:serve command done. Config options pending. |
| Phase 6: Adapter integration | **Partial** | HTTP transport via ApiApplication done. Per-adapter DI wiring pending. |

**Bonus (not in original plan)**: HTTP transport via `POST /debug/api/mcp` — MCP runs inside ADP HTTP server.

Module docs: `libs/McpServer/CLAUDE.md`

---

## 1. Motivation

MCP (Model Context Protocol) allows AI assistants to directly interact with tools and data sources.
An ADP MCP server would let developers use AI assistants (Claude, Cursor, etc.) to:

- Query and analyze debug entries from natural language ("show me slow queries from the last request")
- Inspect live application state (routes, DI config, database schema)
- Diagnose exceptions and errors with full context
- Run SQL queries and analyze results
- Browse application files and configuration
- Analyze performance timelines

This turns ADP from a "visual-only" tool into an **AI-accessible debugging backend**.

---

## 2. Architecture Decision

### Option A: Standalone MCP server (PHP process)
A new `libs/McpServer/` module that directly uses Kernel's `StorageInterface` and `FileServiceRegistry`.

**Pros**: No HTTP overhead, direct storage access, simpler deployment.
**Cons**: Requires PHP runtime, duplicates some API logic.

### Option B: MCP server wrapping existing HTTP API
A thin MCP layer (PHP or TypeScript) that calls the existing REST API.

**Pros**: Reuses all existing endpoints, can be any language.
**Cons**: HTTP overhead, requires running API server, double serialization.

### Option C: Hybrid — PHP MCP server using Kernel + API internals directly
A new `libs/McpServer/` module that reuses API controllers and Kernel classes directly (in-process),
without HTTP. Controllers already return structured data — just call them directly.

**Recommended: Option C.** Reuses existing code, no HTTP overhead, stays in PHP ecosystem,
follows the same pattern as CLI (which also calls Kernel directly).

---

## 3. Module Structure

```
libs/McpServer/
├── composer.json
├── CLAUDE.md
├── src/
│   ├── McpServer.php                    # Main server: stdio transport, tool registry
│   ├── Transport/
│   │   ├── TransportInterface.php       # stdin/stdout abstraction
│   │   └── StdioTransport.php           # JSON-RPC over stdio
│   ├── Protocol/
│   │   ├── JsonRpcHandler.php           # JSON-RPC 2.0 message parsing
│   │   ├── McpMessage.php               # MCP message DTOs
│   │   └── McpCapabilities.php          # Server capabilities declaration
│   ├── Tool/
│   │   ├── ToolInterface.php            # Contract for MCP tools
│   │   ├── ToolRegistry.php             # Auto-discovery and dispatch
│   │   ├── Debug/
│   │   │   ├── ListEntriesTools.php     # list_debug_entries
│   │   │   ├── ViewEntryTool.php        # view_debug_entry
│   │   │   ├── SearchLogsTool.php       # search_logs
│   │   │   ├── AnalyzeExceptionTool.php # analyze_exception
│   │   │   └── TimelineTool.php         # view_timeline
│   │   ├── Inspector/
│   │   │   ├── ListRoutesTool.php       # list_routes
│   │   │   ├── CheckRouteTool.php       # check_route
│   │   │   ├── DatabaseSchemaTool.php   # database_schema
│   │   │   ├── QueryDatabaseTool.php    # query_database
│   │   │   ├── ExplainQueryTool.php     # explain_query
│   │   │   ├── ListConfigTool.php       # list_config
│   │   │   ├── ViewFilesTool.php        # browse_files
│   │   │   └── ComposerInfoTool.php     # composer_info
│   │   └── Ingestion/
│   │       └── IngestDataTool.php       # ingest_debug_data
│   └── Resource/
│       ├── ResourceInterface.php        # Contract for MCP resources
│       ├── DebugEntryResource.php       # debug://entries/{id}
│       └── StorageSummaryResource.php   # debug://summary
└── tests/
    └── ...
```

---

## 4. MCP Tools (detailed)

### 4.1 Debug Tools

#### `list_debug_entries`
List recent debug entries with summary info.

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `limit` | int | no | Max entries (default 20) |
| `filter` | string | no | Filter by URL pattern, method, or status code |

Returns: Array of `{id, timestamp, method, url, status, collectors, duration}`.

#### `view_debug_entry`
View full collector data for a specific debug entry.

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `id` | string | yes | Debug entry ID |
| `collector` | string | no | Filter to specific collector (e.g., "log", "database", "exception") |

Returns: Full collector data, optionally filtered.

#### `search_logs`
Search log messages across debug entries.

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `query` | string | yes | Search term (matches message, context) |
| `level` | string | no | Filter by level: debug, info, warning, error, critical |
| `limit` | int | no | Max results (default 50) |

Returns: Matching log entries with entry ID, timestamp, level, message, context.

#### `analyze_exception`
Get exception details with full stack trace and context.

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `id` | string | no | Debug entry ID (default: latest with exception) |

Returns: Exception class, message, file, line, trace, and related collector data (request, logs).

#### `view_timeline`
View performance timeline for a debug entry.

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `id` | string | no | Debug entry ID (default: latest) |

Returns: Ordered timeline events with durations, memory usage.

#### `view_database_queries`
List SQL queries from a debug entry with timing.

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `id` | string | no | Debug entry ID (default: latest) |
| `slow_only` | bool | no | Only queries > 100ms |

Returns: Queries with SQL, params, duration, row count.

### 4.2 Inspector Tools

#### `list_routes`
List all registered application routes.

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `filter` | string | no | Filter by path or method |

Returns: Routes with method, path, handler, middleware.

#### `check_route`
Test route matching for a given URL.

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `method` | string | yes | HTTP method (GET, POST, etc.) |
| `path` | string | yes | URL path to match |

Returns: Matched route, params, handler.

#### `database_schema`
Get database table schema.

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `table` | string | no | Specific table name (default: list all tables) |

Returns: Table columns with types, constraints, indexes.

#### `query_database`
Execute a SQL query (read-only by default).

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `sql` | string | yes | SQL query |
| `params` | object | no | Bind parameters |
| `readonly` | bool | no | Enforce read-only (default: true) |

Returns: Query results with columns and rows.

#### `explain_query`
Run EXPLAIN on a SQL query.

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `sql` | string | yes | SQL query to explain |
| `analyze` | bool | no | Run EXPLAIN ANALYZE (default: false) |

Returns: Query plan.

#### `list_config`
View application DI container configuration.

Returns: Registered services, parameters, event listeners.

#### `browse_files`
Browse application files.

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `path` | string | no | Directory path (default: root) |

Returns: File tree with names, sizes, modification times.

#### `composer_info`
Get Composer dependency information.

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `package` | string | no | Specific package name |

Returns: Installed packages, versions, or single package details.

### 4.3 Ingestion Tools

#### `ingest_debug_data`
Ingest external debug data (for non-PHP applications).

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `collectors` | object | yes | Collector data keyed by collector name |
| `context` | object | no | Application context (service, language) |

Returns: Created debug entry ID.

---

## 5. MCP Resources

Resources provide read-only data that AI clients can reference.

| URI Pattern | Description |
|-------------|-------------|
| `debug://entries` | List of all debug entry summaries |
| `debug://entries/{id}` | Full data for a specific entry |
| `debug://entries/latest` | Most recent debug entry |
| `debug://schema` | Database schema overview |
| `debug://routes` | Application route table |
| `debug://config` | Application configuration summary |

---

## 6. Implementation Plan

### Phase 1: Core MCP Infrastructure (foundation)

1. **Create `libs/McpServer/` module** with `composer.json`, namespace `AppDevPanel\McpServer`
2. **Implement JSON-RPC 2.0 transport** — `StdioTransport` reads/writes JSON-RPC over stdin/stdout
3. **Implement MCP protocol handler** — `initialize`, `tools/list`, `tools/call`, `resources/list`, `resources/read`
4. **Implement `ToolInterface`** and `ToolRegistry` — tools declare name, description, JSON Schema input, and `execute()` method
5. **Add CLI entry point** — `bin/adp-mcp` executable that boots the server

Dependencies: `appdevpanel/kernel` (storage access), `appdevpanel/api` (controller reuse).

### Phase 2: Debug Tools (read stored data)

6. **Implement `list_debug_entries`** — calls `StorageInterface::read(TYPE_SUMMARY)`, formats for AI consumption
7. **Implement `view_debug_entry`** — calls `StorageInterface::read(TYPE_DATA, $id)`, supports collector filter
8. **Implement `search_logs`** — scans entries for matching log messages
9. **Implement `analyze_exception`** — extracts exception data, cross-references with request/log collectors
10. **Implement `view_timeline`** — extracts timeline collector data
11. **Implement `view_database_queries`** — extracts database collector data with optional slow-query filter

### Phase 3: Inspector Tools (live application state)

12. **Implement inspector tools** — `list_routes`, `check_route`, `database_schema`, `query_database`, `explain_query`, `list_config`, `browse_files`, `composer_info`
13. **Handle inspector proxy** — if the target app is a registered external service, proxy requests via HTTP (reuse `InspectorProxyMiddleware` logic)

### Phase 4: Resources & Polish

14. **Implement MCP resources** — `DebugEntryResource`, `StorageSummaryResource`
15. **Add resource templates** for parameterized URIs (`debug://entries/{id}`)
16. **Output formatting** — optimize tool outputs for AI consumption (concise, structured, actionable)
17. **Error handling** — meaningful error messages for missing entries, offline services, etc.

### Phase 5: Integration & CLI

18. **Add `mcp:serve` CLI command** to `libs/Cli/` — starts MCP server with configurable storage path
19. **Add configuration** — allow setting read-only mode, allowed inspector capabilities, auth token
20. **Write tests** — unit tests for each tool, integration tests for protocol handling

### Phase 6: Adapter Integration

21. **Add MCP server config to adapters** — Yii, Symfony, Laravel can auto-configure MCP server with correct storage paths
22. **Document setup** for each IDE / AI client (Claude Code, Cursor, VS Code + Continue, etc.)

---

## 7. MCP Protocol Requirements

The server must implement these MCP protocol methods:

| Method | Purpose |
|--------|---------|
| `initialize` | Handshake, declare capabilities (tools, resources) |
| `initialized` | Client acknowledgment |
| `tools/list` | Return all available tools with JSON Schema |
| `tools/call` | Execute a tool and return result |
| `resources/list` | Return available resources |
| `resources/read` | Read a specific resource |
| `ping` | Health check |

Transport: **stdio** (JSON-RPC 2.0 over stdin/stdout). This is the standard for local MCP servers.

---

## 8. Configuration

```json
{
    "mcpServers": {
        "adp": {
            "command": "php",
            "args": ["vendor/bin/adp-mcp", "--storage=/path/to/debug-data"],
            "env": {
                "ADP_READONLY": "true",
                "ADP_AUTH_TOKEN": "optional-token"
            }
        }
    }
}
```

Options:
- `--storage=PATH` — Path to debug data storage directory
- `--inspector-url=URL` — URL of running application for inspector tools (optional)
- `--readonly` — Disable write operations (database queries, cache clear)
- `--no-inspector` — Disable inspector tools entirely (debug-only mode)

---

## 9. Dependencies

| Dependency | Purpose | Exists? |
|------------|---------|---------|
| `appdevpanel/kernel` | StorageInterface, collectors, Dumper | Yes |
| `appdevpanel/api` | Controller logic reuse (optional) | Yes |
| PHP 8.4+ | Required runtime | Yes |
| `ext-json` | JSON-RPC serialization | Standard |

No external PHP packages required. The MCP protocol is simple enough to implement with native PHP.

---

## 10. Example Interactions

### Developer: "What went wrong in the last request?"

```
→ tools/call: list_debug_entries {limit: 1}
← [{id: "20260324_abc", method: "POST", url: "/api/users", status: 500, duration: 342}]

→ tools/call: analyze_exception {id: "20260324_abc"}
← {class: "PDOException", message: "Column 'email' cannot be null",
   file: "src/User/Repository.php", line: 45,
   trace: [...], related_logs: [...]}
```

### Developer: "Show me slow database queries"

```
→ tools/call: view_database_queries {slow_only: true}
← [{sql: "SELECT * FROM users WHERE ...", duration: 1.2s, rows: 50000}]

→ tools/call: explain_query {sql: "SELECT * FROM users WHERE ...", analyze: true}
← {plan: "Seq Scan on users (cost=0.00..1250.00)...", suggestion: "Add index on ..."}
```

### Developer: "What routes does my app have for /api/users?"

```
→ tools/call: list_routes {filter: "/api/users"}
← [{method: "GET", path: "/api/users", handler: "UserController::index"},
   {method: "POST", path: "/api/users", handler: "UserController::store"}]
```

---

## 11. Risks & Mitigations

| Risk | Mitigation |
|------|------------|
| Large debug entries overwhelm AI context | Truncate/summarize large outputs, support pagination |
| SQL injection via `query_database` | Read-only mode by default, parameterized queries only |
| Inspector tools expose sensitive data | Auth token support, IP filtering inherited from API |
| PHP process stays running (memory) | Stateless per-call design, no caching between requests |
| MCP protocol evolves | Keep protocol layer thin, easy to update |

---

## 12. Estimated Scope

| Phase | Files | Effort |
|-------|-------|--------|
| Phase 1: Core infrastructure | ~8 | Medium |
| Phase 2: Debug tools | ~6 | Small |
| Phase 3: Inspector tools | ~8 | Medium |
| Phase 4: Resources | ~4 | Small |
| Phase 5: CLI + config | ~3 | Small |
| Phase 6: Adapter integration | ~4 | Small |
| **Total** | **~33 files** | — |
