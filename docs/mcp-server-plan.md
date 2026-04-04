# MCP Server — Remaining Plan

Status: 2026-04-04. Phase 1-2 complete, Phase 5-6 partial.

## Current State

6 debug tools implemented in `libs/McpServer/src/Tool/Debug/`:
- `list_debug_entries`, `view_debug_entry`, `search_logs`
- `analyze_exception`, `view_database_queries` (with N+1 detection), `view_timeline`

Two transports: stdio (`bin/adp-mcp`) and HTTP (`POST /debug/api/mcp`).
CLI command `mcp:serve` done.

## Remaining Work

### Phase 3: Inspector Tools (planned)

Tools to expose live application state via MCP:

| Tool | Description |
|------|-------------|
| `list_routes` | List registered routes, filter by path/method |
| `check_route` | Test route matching for a URL |
| `database_schema` | Get table schema (columns, types, constraints, indexes) |
| `query_database` | Execute read-only SQL query |
| `explain_query` | Run EXPLAIN/EXPLAIN ANALYZE on SQL |
| `list_config` | View DI container configuration |
| `browse_files` | Browse application files |
| `composer_info` | Get Composer dependency information |

Requires reusing `InspectorProxyMiddleware` logic for external services.

### Phase 4: Resources (planned)

MCP resources for read-only data:

| URI Pattern | Description |
|-------------|-------------|
| `debug://entries` | All debug entry summaries |
| `debug://entries/{id}` | Full data for specific entry |
| `debug://entries/latest` | Most recent debug entry |
| `debug://schema` | Database schema overview |
| `debug://routes` | Application route table |
| `debug://config` | Application configuration summary |

### Phase 5: Configuration (partial)

Done: `mcp:serve` CLI command.
Pending: configurable read-only mode, allowed inspector capabilities, per-adapter auth token integration.

### Phase 6: Adapter Integration (partial)

Done: HTTP transport via ApiApplication.
Pending: per-adapter DI wiring so adapters auto-configure MCP with correct storage paths.

## Configuration Example

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

Options: `--storage=PATH`, `--inspector-url=URL`, `--readonly`, `--no-inspector`.
