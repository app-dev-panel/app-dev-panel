# CLI Module

Provides console commands for managing the ADP debug system.

## Package

- Composer: `app-dev-panel/cli`
- Namespace: `AppDevPanel\Cli\`
- PHP: 8.4+
- Dependencies: `app-dev-panel/kernel`, `app-dev-panel/api`, `app-dev-panel/mcp-server`, `app-dev-panel/frontend-assets`, Symfony Console, Symfony Process

## Directory Structure

```
src/
├── Command/
│   ├── DebugServerCommand.php          # Start debug socket server (dev)
│   ├── DebugResetCommand.php           # Clear debug data (debug:reset)
│   ├── DebugServerBroadcastCommand.php # Broadcast test messages (dev:broadcast)
│   ├── DebugQueryCommand.php           # Query stored debug data (debug:query)
│   ├── DebugSummaryCommand.php         # Show brief summary of debug entry (debug:summary)
│   ├── DebugDumpCommand.php            # View dumped objects (debug:dump)
│   ├── DebugTailCommand.php            # Watch entries in real-time (debug:tail)
│   ├── ServeCommand.php                # Start HTTP debug server (serve)
│   ├── McpServeCommand.php             # Start MCP server for AI integration (mcp:serve)
│   ├── FrontendUpdateCommand.php       # Download latest frontend build (frontend:update)
│   ├── InspectConfigCommand.php        # Inspect application config (inspect:config)
│   ├── InspectDatabaseCommand.php      # Inspect database schema/data (inspect:db)
│   └── InspectRoutesCommand.php        # Inspect application routes (inspect:routes)
└── Server/
    └── server-router.php               # Router for built-in PHP server (bootstraps API)
tests/
└── Unit/
    └── Command/
        ├── DebugDumpCommandTest.php
        ├── DebugQueryCommandTest.php
        ├── DebugServerBroadcastCommandTest.php
        ├── DebugServerCommandTest.php
        ├── DebugSummaryCommandTest.php
        ├── DebugTailCommandTest.php
        ├── FrontendUpdateCommandTest.php
        ├── InspectConfigCommandTest.php
        ├── InspectDatabaseCommandTest.php
        ├── InspectRoutesCommandTest.php
        ├── McpServeCommandTest.php
        ├── ResetCommandTest.php
        └── ServeCommandTest.php
```

## Commands

### `dev` — Debug Server

Starts a UDP socket server that listens for real-time debug messages from the application.

```bash
php yii dev                         # Default: 0.0.0.0:8890
php yii dev -a 127.0.0.1 -p 9000   # Custom address and port
```

The server receives and categorizes messages:
- `MESSAGE_TYPE_VAR_DUMPER` — Variable dumps
- `MESSAGE_TYPE_LOGGER` — Log messages
- Plain text messages

Handles `SIGINT` (Ctrl+C) for graceful shutdown.

### `debug:reset` — Clear Debug Data

Stops the debugger and clears all stored debug data.

```bash
php yii debug:reset
```

Calls `Debugger::stop()` and `StorageInterface::clear()`.

### `dev:broadcast` — Broadcast Test Messages

Sends test messages to all connected debug server clients. Useful for verifying connectivity.

```bash
php yii dev:broadcast                    # Default: "Test message"
php yii dev:broadcast -m "Hello world"   # Custom message
```

Broadcasts in both `MESSAGE_TYPE_LOGGER` and `MESSAGE_TYPE_VAR_DUMPER` formats.

### `debug:query` — Query Debug Data

Query stored debug data from the CLI. Subcommands: `list`, `view`.

```bash
debug:query list                          # List recent entries (default 20)
debug:query list --limit=5                # Limit entries
debug:query list --json                   # Raw JSON output
debug:query view <id>                     # Full entry data
debug:query view <id> -c <CollectorFQCN>  # Specific collector data
```

Uses `CollectorRepositoryInterface` to read from storage.

### `serve` — Standalone ADP API Server

Starts a standalone HTTP server using PHP built-in server, serving the ADP API directly. When `--frontend-path` is omitted, the command auto-resolves the bundle via `AppDevPanel\FrontendAssets\FrontendAssets::path()` (from `app-dev-panel/frontend-assets`), so the full panel SPA is served at `/` out of the box. When a frontend path is available, the process is launched with `php -S host:port -t <frontendPath>` so the built-in server resolves static files from the bundle directory.

```bash
serve                                              # Default: 127.0.0.1:8888, panel auto-resolved from FrontendAssets
serve --host=0.0.0.0 --port=9000                   # Custom host/port
serve --storage-path=/path/to/debug/data           # Custom storage
serve --frontend-path=/path/to/built/assets        # Override bundle path (e.g. local dev build)
```

### `frontend:update` — Download Latest Frontend Build

Fetches `frontend-dist.zip` from the [latest GitHub Release](https://github.com/app-dev-panel/app-dev-panel/releases) and extracts it into `--path`. The archive contains **both** the panel SPA (`index.html`, `bundle.js`, `assets/`) and the toolbar widget (`toolbar/bundle.js`, `toolbar/bundle.css`). Intended for PHAR users or environments where Composer is not the update vehicle; composer-based installs update via `composer update app-dev-panel/frontend-assets`.

```bash
frontend:update check                               # Show current vs latest version
frontend:update check --json                        # Machine-readable output
frontend:update download --path=/path/to/dist       # Install latest panel + toolbar build
```

Writes a `.adp-version` file next to `index.html` so subsequent `check` invocations can compare installed vs latest. Emits a warning if the installed directory has `index.html` but no `toolbar/bundle.js` — typical for archives produced before toolbar was bundled. The GitHub API call is capped at 10s; the asset download at 30s.

### `mcp:serve` — MCP Server

Starts an MCP (Model Context Protocol) server over stdio, exposing ADP debug data to AI assistants.

```bash
mcp:serve --storage-path=/path/to/debug/data    # Required: path to debug storage
```

Creates `FileStorage` and `McpToolRegistry`, then runs `McpServer` over `StdioTransport`.

## Testing

```bash
composer test    # Runs PHPUnit
```
