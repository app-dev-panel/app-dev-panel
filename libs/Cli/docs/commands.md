# CLI Commands

## Overview

The CLI module provides three Symfony Console commands for managing the ADP debug system.
All commands extend `Symfony\Component\Console\Command\Command`.

## dev — Debug Socket Server

**Purpose**: Starts a long-running process that receives real-time debug messages via UDP socket.

**Usage**:
```bash
php yii dev [options]
```

**Options**:
| Option | Short | Default | Description |
|--------|-------|---------|-------------|
| `--address` | `-a` | `0.0.0.0` | Host to bind the server |
| `--port` | `-p` | `8890` | Port to listen on |
| `--env` | `-e` | - | Environment (test mode returns immediately) |

**How it works**:
1. Creates a `Connection` (UDP socket) via `Connection::create()`
2. Binds the socket to the specified address:port
3. Enters a read loop, processing incoming messages
4. Messages are decoded from JSON and categorized by type
5. Output is formatted using Symfony's `SymfonyStyle`

**Signal handling**: Registers a `SIGINT` handler for graceful shutdown on Ctrl+C.

**Use with `0.0.0.0`**: When running inside a VM or container, use `0.0.0.0` to accept
connections from the host machine.

## debug:reset — Clear Debug Data

**Purpose**: Clears all stored debug data and stops the debugger.

**Usage**:
```bash
php yii debug:reset
```

**Dependencies** (injected via constructor):
- `StorageInterface` — calls `clear()` to remove all entries
- `Debugger` — calls `stop()` to halt the debugger

## dev:broadcast — Test Message Broadcast

**Purpose**: Sends test messages to all connected debug server clients.

**Usage**:
```bash
php yii dev:broadcast [options]
```

**Options**:
| Option | Short | Default | Description |
|--------|-------|---------|-------------|
| `--message` | `-m` | `Test message` | Text to broadcast |
| `--env` | `-e` | - | Environment (test mode returns immediately) |

**How it works**:
1. Creates a `Connection` (UDP socket) via `Connection::create()`
2. Broadcasts the message in two formats:
   - As `MESSAGE_TYPE_LOGGER` with plain text
   - As `MESSAGE_TYPE_VAR_DUMPER` with VarDumper JSON wrapping

Useful for verifying that clients are connected and receiving messages.
