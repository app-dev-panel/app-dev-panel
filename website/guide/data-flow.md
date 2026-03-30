---
title: Data Flow
---

# Data Flow

This page describes how debug data flows from your application to the ADP panel.

## Overview

```
Target App → Adapter → Proxies → Collectors → Debugger → Storage → API → Frontend
```

## Request Lifecycle

### Phase 1: Startup

When your application receives an HTTP request (or CLI command):

1. The adapter's event listener catches the framework's startup event
2. `Debugger::startup()` is called, which:
   - Registers a shutdown function
   - Checks if the request/command should be ignored (via `$ignoredRequests` / `$ignoredCommands` patterns, `X-Debug-Ignore` header)
   - If not ignored: calls `startup()` on all registered collectors

### Phase 2: Data Collection

During request processing, proxies intercept calls and feed data to collectors:

```
Application Code
      │
      ├──▶  Logger::log()              ──▶  LoggerInterfaceProxy          ──▶  LogCollector
      ├──▶  EventDispatcher::dispatch() ──▶  EventDispatcherInterfaceProxy ──▶  EventCollector
      ├──▶  HttpClient::sendRequest()   ──▶  HttpClientInterfaceProxy      ──▶  HttpClientCollector
      ├──▶  Container::get()            ──▶  ContainerInterfaceProxy       ──▶  ServiceCollector
      ├──▶  VarDumper::dump()           ──▶  VarDumperHandlerInterfaceProxy──▶  VarDumperCollector
      └──▶  throw Exception             ──▶  ExceptionHandler              ──▶  ExceptionCollector
```

Each collector accumulates data in memory for the duration of the request. Proxies record metadata like timestamps, file:line info (via `debug_backtrace()`), and unique IDs for correlation.

### Phase 3: Shutdown and Flush

When the request completes (or a console command finishes), the **Debugger** triggers shutdown:

1. Calls `shutdown()` on all collectors (resets internal state)
2. Calls `getCollected()` to retrieve accumulated data
3. Serializes objects using `Dumper` (depth-limited to 30 levels, with circular reference detection)
4. Calls `flush()` on storage

**FileStorage** writes three JSON files per debug entry:

| File | Contents |
|------|----------|
| `{id}/summary.json` | Entry metadata (timestamp, URL, status, collector summaries) |
| `{id}/data.json` | Full collector payloads |
| `{id}/objects.json` | Extracted unique PHP objects for deep inspection |

All writes use `file_put_contents()` with `LOCK_EX` for atomicity.

### Phase 4: Garbage Collection

After each flush, storage runs garbage collection:

- Acquires a non-blocking lock on `.gc.lock` (skips if another process holds it)
- Deletes entries beyond `historySize` (default 50), sorted by modification time

## Storage Format

```
runtime/debug/
├── YYYY-MM-DD/
│   ├── {entryId}/
│   │   ├── summary.json
│   │   ├── data.json
│   │   └── objects.json
│   ├── {entryId}/
│   │   └── ...
│   └── .gc.lock
└── .services.json
```

### Summary Format

```json
{
  "id": "1710520800123456",
  "collectors": ["LogCollector", "EventCollector", "RequestCollector"],
  "logger": {"total": 5},
  "event": {"total": 12},
  "http": {"count": 2, "totalTime": 0.45},
  "request": {"url": "/api/users", "method": "GET", "status": 200},
  "exception": null
}
```

Collectors that implement `SummaryCollectorInterface` contribute their summary keys (e.g., `logger`, `event`, `http`) for display in the entry list without loading full data.

## API Serving

The API serves stored data through a middleware chain:

```
Frontend Request
      │
      ▼
  API Middleware Chain
  ┌────────────────────────────┐
  │ 1. CorsAllowAll            │
  │ 2. IpFilter                │
  │ 3. TokenAuthMiddleware     │
  │ 4. FormatDataResponseAsJson│
  │ 5. ResponseDataWrapper     │
  └────────────┬───────────────┘
               │
               ▼
  CollectorRepository
  ├── .getSummary()    → summary.json
  ├── .getDetail(id)   → data.json
  └── .getObject(id)   → objects.json
               │
               ▼
  JSON Response: {id, data, error, success, status}
```

## Real-Time Updates (SSE)

The frontend uses **SSE** (Server-Sent Events) to detect new entries in real-time:

1. Frontend subscribes to `GET /debug/api/event-stream`
2. API polls storage every second, computing an MD5 hash of current summaries
3. When a new entry appears, the hash changes and an event is emitted: `{"type": "debug-updated"}`
4. Frontend fetches the updated entry list

The frontend `ServerSentEventsObserver` uses exponential backoff on connection failures: 1s base delay, doubles per attempt, 30s max, resets on successful connection.

## Ingestion API (External Applications)

Non-PHP applications can send debug data via the **Ingestion API**, bypassing the proxy/collector pipeline entirely. Data is written directly to storage and appears in the panel alongside PHP debug entries.

| Endpoint | Description |
|----------|-------------|
| `POST /debug/api/ingest` | Single debug entry with collectors + optional context/summary |
| `POST /debug/api/ingest/batch` | Multiple entries (max 100). Returns `{ids: [...], count}` |
| `POST /debug/api/ingest/log` | Shorthand for single log entry: `{level, message, context?}` |
| `GET /debug/api/openapi.json` | OpenAPI specification for the Ingestion API |

Request body format for single entry:

```json
{
  "collectors": {
    "LogCollector": [{"level": "info", "message": "Hello"}]
  },
  "summary": {},
  "context": {}
}
```

## Inspector Proxy (Multi-App)

The Inspector proxy enables the frontend to inspect external applications (Python, Node.js, Go, etc.) through a unified API.

```
Frontend: /inspect/api/routes?service=python-app
      │
      ▼
InspectorProxyMiddleware
      ├── Extract service name from ?service= param
      ├── Resolve via ServiceRegistry → ServiceDescriptor
      ├── Check online status (lastSeenAt within 60s)
      ├── Map path to capability (e.g., /routes → "routes")
      ├── Verify service supports the capability
      │
      ▼ (all checks pass)
Proxy request to: {inspectorUrl}/inspect/api/routes
```

### Capability Map

| Path Prefix | Capability |
|-------------|-----------|
| `/config`, `/params` | `config` |
| `/routes`, `/route/check` | `routes` |
| `/files` | `files` |
| `/cache` | `cache` |
| `/table` | `database` |
| `/translations` | `translations` |
| `/events` | `events` |
| `/command` | `commands` |
| `/git` | `git` |
| `/composer` | `composer` |
| `/phpinfo` | `phpinfo` |
| `/opcache` | `opcache` |

### Error Responses

| Condition | Status |
|-----------|--------|
| Service not found | 404 |
| Service offline (heartbeat timeout) | 503 |
| Capability not supported | 501 |
| No inspector URL configured | 502 |
| Connection refused / host unresolved | 502 |
| Request timeout | 504 |

## Service Registry

External applications register with ADP and send periodic heartbeats to appear as online:

```
External App                              ADP
     │                                     │
     │  POST /debug/api/services/register  │
     │  {service, language, inspectorUrl,  │
     │   capabilities}                     │
     │ ──────────────────────────────────▶ │
     │                                     │
     │  POST /debug/api/services/heartbeat │
     │  {service}  (every <60s)            │
     │ ──────────────────────────────────▶ │
     │                                     │
     │  GET /debug/api/services/           │
     │ ◀────────────────────────────────── │ (lists all with online/offline)
```

`ServiceDescriptor` contains: `service`, `language`, `inspectorUrl`, `capabilities[]`, `registeredAt`, `lastSeenAt`. A service is considered online if `now() - lastSeenAt < 60s`.

## Console Command Flow

Console commands follow the same lifecycle as web requests, with these differences:

- `ConsoleAppInfoCollector` replaces `WebAppInfoCollector`
- `CommandCollector` replaces `RequestCollector`
- No middleware, router, or asset collectors
- Events: `ConsoleCommandEvent` triggers startup, `ConsoleTerminateEvent` triggers shutdown

## Debug Server (UDP Socket)

The `dev` CLI command starts a UDP socket server for real-time log/dump output in the terminal:

```bash
php yii dev -a 0.0.0.0 -p 8890
```

When your application calls `dump()` or logs a message, the broadcaster:
1. Discovers running server sockets via glob (`/tmp/yii-dev-server-*.sock`)
2. Sends the data as base64-encoded JSON with an 8-byte length header
3. The server displays the message as a formatted block in the terminal

Message types: `VarDumper`, `Logger`, and plain text.
