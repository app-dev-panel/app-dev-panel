# CLI Module

Symfony Console commands for managing the ADP debug system.

## Package

- Composer: `app-dev-panel/cli`
- Namespace: `AppDevPanel\Cli\`
- PHP: 8.2+
- Dependencies: `app-dev-panel/kernel` (Symfony Console comes transitively via Kernel)

## Directory Structure

```
src/
└── Command/
    ├── DebugServerCommand.php          # Start debug socket server
    ├── DebugResetCommand.php           # Clear debug data
    └── DebugServerBroadcastCommand.php # Broadcast test messages
tests/
└── Unit/
    └── Command/
        └── ResetCommandTest.php
```

## Commands

All commands extend `Symfony\Component\Console\Command\Command`. Exit codes use `Command::SUCCESS` / `Command::FAILURE`.

### `dev` -- Debug Server

Starts a UDP socket server for real-time debug messages.

```bash
php yii dev                         # Default: 0.0.0.0:8890
php yii dev -a 127.0.0.1 -p 9000   # Custom address and port
```

| Option | Short | Default | Description |
|--------|-------|---------|-------------|
| `--address` | `-a` | `0.0.0.0` | Host to bind |
| `--port` | `-p` | `8890` | Port to listen on |
| `--env` | `-e` | - | `test` returns immediately |

Creates `Connection` via `Connection::create()`, binds socket, enters read loop. Messages decoded from JSON and categorized: `MESSAGE_TYPE_VAR_DUMPER`, `MESSAGE_TYPE_LOGGER`, or plain text.

Registers `SIGINT` handler for graceful shutdown (when `pcntl_signal` available).

### `debug:reset` -- Clear Debug Data

Stops the debugger and clears all stored debug data.

```bash
php yii debug:reset
```

Calls `Debugger::stop()` and `StorageInterface::clear()`.

### `dev:broadcast` -- Broadcast Test Messages

Sends test messages to all connected debug server clients.

```bash
php yii dev:broadcast                    # Default: "Test message"
php yii dev:broadcast -m "Hello world"   # Custom message
```

| Option | Short | Default | Description |
|--------|-------|---------|-------------|
| `--message` | `-m` | `Test message` | Text to broadcast |
| `--env` | `-e` | - | `test` returns immediately |

Broadcasts in both `MESSAGE_TYPE_LOGGER` (plain text) and `MESSAGE_TYPE_VAR_DUMPER` (JSON-encoded) formats.

## Testing

```bash
composer test    # Runs PHPUnit
```
