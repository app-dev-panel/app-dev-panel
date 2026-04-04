# Live Data Streaming Design

Status: 2026-04-04. Active design — implementation in progress.

## Problem

Current SSE endpoint polls storage hash every 500ms (O(n) on all summaries, ties up PHP worker).
No real-time streaming of logs/dumps — data only available after request completes.

## Architecture

```
  Target App (PHP process)                    SSE HTTP Worker
  ─────────────────────                       ─────────────────
  LoggerDecorator ─┐                          ┌─ Connection (UDP listener)
  VarDumperHandler ┤                          │
  Debugger.flush() ┤─► Broadcaster ──UDP──►───┤─► LiveEventStream
                   │   (to all .sock/.port)   │   (yields SSE events)
                   └                          └─► Browser (EventSource)
```

**Key insight**: each SSE HTTP worker creates its own UDP listener socket.
Broadcaster discovers all listeners (CLI + SSE workers) via glob and sends to all.

## Protocol

### Message Types (Connection constants)

| Constant | Value | Source | Description |
|----------|-------|--------|-------------|
| `MESSAGE_TYPE_VAR_DUMPER` | 0x001B | VarDumperHandler | Live var_dump |
| `MESSAGE_TYPE_LOGGER` | 0x002B | LoggerDecorator | Live log message |
| `MESSAGE_TYPE_ENTRY_CREATED` | 0x003B | Debugger (via storage hook) | Debug entry stored |

### Datagram Format

Each UDP datagram: `pack('P', strlen(base64)) . base64_encode(json_encode([type, payload]))`

### SSE Event Format

```
data: {"type":"live-log","payload":{"level":"info","message":"...","context":{...}}}

data: {"type":"live-dump","payload":{"variable":"..."}}

data: {"type":"entry-created","payload":{"id":"abc123"}}
```

## Components

### Backend (PHP)

1. **Connection** — already cross-platform (Unix sockets / UDP localhost)
2. **Broadcaster** — already sends to all listeners
3. **LiveEventStream** — new, replaces polling SSE
   - Creates a Connection + binds
   - Non-blocking recv (SO_RCVTIMEO 50ms)
   - Yields SSE events from received datagrams
   - Cleans up socket on close
4. **StorageBroadcaster** — new decorator for StorageInterface
   - Wraps FileStorage
   - On write(TYPE_SUMMARY), broadcasts MESSAGE_TYPE_ENTRY_CREATED
   - Carries entry ID in payload

### Frontend (TypeScript)

1. **EventTypesEnum** — add `LiveLog`, `LiveDump`, `EntryCreated`
2. **useServerSentEvents** — unchanged (already generic)
3. **Layout.tsx** — handle `EntryCreated` events (replace `DebugUpdated`)
4. **Live panel** — new UI for real-time logs/dumps (future)

## Migration

- `entry-created` replaces `debug-updated` for entry notifications
- `debug-updated` kept as alias for backwards compatibility
- Frontend checks for both event types during transition
- Old polling SSE endpoint kept at same URL, behavior changes transparently

## Limitations

- UDP max datagram size: ~64KB. Log messages exceeding this are truncated.
- No delivery guarantee — UDP is fire-and-forget. Acceptable for dev tooling.
- Each SSE browser tab = 1 PHP worker + 1 UDP socket. Acceptable for dev (1-3 tabs).
