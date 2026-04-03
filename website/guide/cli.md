---
title: CLI Commands
description: "ADP CLI commands for managing the debug system: serve, reset, broadcast, query, and MCP server operations."
---

# CLI Commands

ADP provides console commands for managing the debug system. All commands are built on Symfony Console and available through your framework's CLI runner or the standalone `serve` server.

## Available Commands

### `dev` -- Debug Server

Starts a UDP socket server that receives real-time debug messages from the application.

```bash
php yii dev                         # Default: 0.0.0.0:8890
php yii dev -a 127.0.0.1 -p 9000   # Custom address and port
```

The server receives and displays three message types:
- Variable dumps (`MESSAGE_TYPE_VAR_DUMPER`)
- Log messages (`MESSAGE_TYPE_LOGGER`)
- Plain text messages

Supports graceful shutdown via `SIGINT` (Ctrl+C).

### `debug:reset` -- Clear Debug Data

Stops the debugger and clears all stored debug data.

```bash
php yii debug:reset
```

Internally calls <class>AppDevPanel\Kernel\Debugger</class>`::stop()` followed by <class>AppDevPanel\Kernel\Storage\StorageInterface</class>`::clear()`.

### `dev:broadcast` -- Broadcast Test Messages

Sends test messages to all connected debug server clients. Useful for verifying connectivity.

```bash
php yii dev:broadcast                    # Default: "Test message"
php yii dev:broadcast -m "Hello world"   # Custom message
```

### `debug:query` -- Query Debug Data

Query stored debug data from the command line.

```bash
debug:query list                          # List recent entries (default 20)
debug:query list --limit=5                # Limit entries
debug:query list --json                   # Raw JSON output
debug:query view <id>                     # Full entry data
debug:query view <id> -c <CollectorFQCN>  # Specific collector data
```

### `serve` -- Standalone ADP Server

Starts a standalone HTTP server using PHP's built-in server, serving the ADP API directly. No framework required.

```bash
serve                                              # Default: 127.0.0.1:8888
serve --host=0.0.0.0 --port=9000                   # Custom host/port
serve --storage-path=/path/to/debug/data           # Custom storage directory
serve --frontend-path=/path/to/built/assets        # Serve frontend assets
```

### `mcp:serve` -- MCP Server (stdio)

Starts the MCP server in stdio mode for AI assistant integration. See the [MCP Server](./mcp-server.md) page for details.

```bash
php yii mcp:serve --storage-path=/path/to/debug-data
```

### `debug:summary` -- Entry Summary

Shows a brief summary of a debug entry.

```bash
debug:summary <id>
```

### `debug:dump` -- View Dumped Objects

View dumped objects for a specific debug entry.

```bash
debug:dump <id>
```

### `debug:tail` -- Watch Entries

Watch debug entries in real-time as they arrive.

```bash
debug:tail
```

### `frontend:update` -- Update Frontend

Check for updates and download the latest frontend build.

```bash
frontend:update
```

### `inspect:config` -- Inspect Configuration

Inspect application configuration from the command line.

```bash
inspect:config
```

### `inspect:db` -- Inspect Database

Inspect database: list tables, view schema, execute queries.

```bash
inspect:db                        # List tables
inspect:db --table=users          # View table schema
```

### `inspect:routes` -- Inspect Routes

Inspect application routes: list all routes, check route matching.

```bash
inspect:routes                    # List all routes
inspect:routes --check=/api/users # Check route matching
```

## Command Summary

| Command | Purpose |
|---------|---------|
| `dev` | Start real-time UDP debug server |
| `debug:reset` | Clear all stored debug data |
| `dev:broadcast` | Send test messages to debug server |
| `debug:query` | Query stored entries from CLI |
| `debug:summary` | Show brief summary of a debug entry |
| `debug:dump` | View dumped objects for a debug entry |
| `debug:tail` | Watch debug entries in real-time |
| `serve` | Start standalone HTTP API server |
| `mcp:serve` | Start MCP server (stdio transport) |
| `frontend:update` | Check for updates and download latest frontend |
| `inspect:config` | Inspect application configuration |
| `inspect:db` | Inspect database (tables, schema, queries) |
| `inspect:routes` | Inspect application routes |
