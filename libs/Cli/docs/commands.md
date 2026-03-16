# CLI Commands

Three Symfony Console commands. All extend `Symfony\Component\Console\Command\Command`.
Exit codes: `Command::SUCCESS` (0) / `Command::FAILURE` (1).

## dev -- Debug Socket Server

Starts a long-running UDP socket server that receives real-time debug messages.

```bash
php yii dev [options]
```

| Option | Short | Default | Description |
|--------|-------|---------|-------------|
| `--address` | `-a` | `0.0.0.0` | Host to bind the server |
| `--port` | `-p` | `8890` | Port to listen on |
| `--env` | `-e` | - | `test` returns immediately |

Flow:
1. Creates `Connection` (UDP socket) via `Connection::create()`
2. Binds socket to address:port
3. Read loop processes incoming JSON messages
4. Messages categorized by type: `MESSAGE_TYPE_VAR_DUMPER`, `MESSAGE_TYPE_LOGGER`, plain text
5. Output formatted via `SymfonyStyle::block()`

Signal handling: `SIGINT` (Ctrl+C) for graceful shutdown when `pcntl_signal` is available.

Use `0.0.0.0` when running inside a VM or container to accept connections from the host.

## debug:reset -- Clear Debug Data

Stops the debugger and clears all stored debug data.

```bash
php yii debug:reset
```

Constructor dependencies:
- `StorageInterface` -- calls `clear()`
- `Debugger` -- calls `stop()`

## dev:broadcast -- Broadcast Test Messages

Sends test messages to all connected debug server clients.

```bash
php yii dev:broadcast [options]
```

| Option | Short | Default | Description |
|--------|-------|---------|-------------|
| `--message` | `-m` | `Test message` | Text to broadcast |
| `--env` | `-e` | - | `test` returns immediately |

Broadcasts the message in two formats:
- `MESSAGE_TYPE_LOGGER` with plain text
- `MESSAGE_TYPE_VAR_DUMPER` with `json_encode()`-wrapped data
