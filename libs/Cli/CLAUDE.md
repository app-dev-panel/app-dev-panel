# CLI Module

Provides console commands for managing the ADP debug system.

## Package

- Composer: `app-dev-panel/cli`
- Namespace: `AppDevPanel\Cli\`
- PHP: 8.4+
- Dependencies: `app-dev-panel/kernel`, `app-dev-panel/api`, Symfony Console

## Directory Structure

```
src/
├── Command/
│   ├── DebugServerCommand.php          # Start debug socket server
│   ├── DebugResetCommand.php           # Clear debug data
│   ├── DebugServerBroadcastCommand.php # Broadcast test messages
│   ├── DebugQueryCommand.php           # Query stored debug data (list, view, collector)
│   └── ServeCommand.php                # Start HTTP debug server (PHP built-in)
└── Server/
    └── server-router.php               # Router for built-in PHP server (bootstraps API)
tests/
└── Unit/
    └── Command/
        └── ResetCommandTest.php
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

Starts a standalone HTTP server using PHP built-in server, serving the ADP API directly.

```bash
debug:serve                                       # Default: 127.0.0.1:8888
debug:serve --host=0.0.0.0 --port=9000            # Custom host/port
debug:serve --storage-path=/path/to/debug/data    # Custom storage
debug:serve --frontend-path=/path/to/built/assets # Serve frontend
```

## Testing

```bash
composer test    # Runs PHPUnit
```
