---
title: MCP Server
---

# MCP Server

ADP includes an MCP (Model Context Protocol) server that exposes debug data to AI assistants like Claude, Cursor, and other MCP-compatible clients.

## Transports

ADP supports two transport modes for MCP communication:

| Transport | Use Case | Entry Point |
|-----------|----------|-------------|
| **stdio** | AI clients that launch a local process (Claude Code, Cursor) | `php vendor/bin/adp-mcp` |
| **HTTP** | AI clients that connect to a running server | `POST /inspect/api/mcp` |

### stdio Transport

Configure your AI client to launch the ADP MCP binary:

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

The `ADP_STORAGE_PATH` environment variable is also accepted.

### HTTP Transport

Available automatically when the ADP server is running. For clients that support HTTP URLs:

```json
{
  "mcpServers": {
    "AppDevPanel": {
      "url": "http://localhost:8080/inspect/api/mcp"
    }
  }
}
```

For stdio-only clients (e.g., Claude Desktop), use the `mcp-remote` proxy:

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

## Available Tools

The MCP server exposes six debug tools:

| Tool | Description |
|------|-------------|
| `list_debug_entries` | List recent debug entries with summary info |
| `view_debug_entry` | View full collector data for a specific entry |
| `search_logs` | Search log messages across all entries by query and level |
| `analyze_exception` | Exception details with stack trace and context |
| `view_database_queries` | SQL queries with timing and N+1 detection |
| `view_timeline` | Performance timeline from all collectors |

## Enabling and Disabling

The MCP server can be toggled via the Settings UI in the frontend or via the API:

```bash
# Check status
curl http://localhost:8080/inspect/api/mcp/settings

# Enable
curl -X PUT http://localhost:8080/inspect/api/mcp/settings \
  -H "Content-Type: application/json" -d '{"enabled": true}'
```

When disabled, the MCP endpoint returns a JSON-RPC error code `-32000`.

## Protocol

ADP implements MCP spec version `2024-11-05` using JSON-RPC 2.0. Supported methods: `initialize`, `initialized`, `ping`, `tools/list`, `tools/call`.
