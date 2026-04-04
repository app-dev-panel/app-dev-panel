# MCP Server — Remaining Plan

Status: 2026-04-04. Phase 1-2 complete, Phase 3 partial, Phase 5-6 partial.

## Current State

6 debug tools in `libs/McpServer/src/Tool/Debug/`:
- `list_debug_entries`, `view_debug_entry`, `search_logs`
- `analyze_exception`, `view_database_queries` (with N+1 detection), `view_timeline`

3 inspector tools in `libs/McpServer/src/Tool/Inspector/`:
- `inspect_config` (params, config, events), `inspect_routes` (list, check), `inspect_database_schema`

`InspectorClient` in `libs/McpServer/src/Inspector/` — HTTP client for Inspector API.

Two transports: stdio (`bin/adp-mcp`) and HTTP (`POST /inspect/api/mcp`).
CLI command `mcp:serve` done. `--inspector-url` option for stdio mode.
Adapters (Yii3, Symfony, Laravel, Yii2) auto-wire InspectorClient for HTTP mode.

## Remaining Work

### Phase 3: Inspector Tools (partial — 3 of 8 done)

Implemented:

| Tool | Description |
|------|-------------|
| `inspect_config` | View app params, DI config groups, event listeners |
| `inspect_routes` | List routes, check route matching for a URL |
| `inspect_database_schema` | Table list with sizes, column/index details |

Remaining:

| Tool | Description |
|------|-------------|
| `query_database` | Execute read-only SQL query |
| `explain_query` | Run EXPLAIN/EXPLAIN ANALYZE on SQL |
| `browse_files` | Browse application files |
| `composer_info` | Get Composer dependency information |

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
