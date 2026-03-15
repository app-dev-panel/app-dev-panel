# Data Flow

## Request Lifecycle

This document describes how data flows through ADP from the moment a user's application
handles a request to the moment the data appears in the debug panel.

### Phase 1: Startup

```
User Request → Framework Router → Adapter Event Listener
                                         │
                                         ▼
                                  Debugger::startup()
                                         │
                                  ┌──────┴──────┐
                                  │  For each    │
                                  │  Collector:  │
                                  │  startup()   │
                                  └─────────────┘
```

1. The target application receives an HTTP request (or CLI command)
2. The Adapter's event listener catches the framework's "application startup" event
3. `Debugger::startup()` is called, which:
   - Generates a unique debug entry ID
   - Checks if the current request/command should be ignored (via URL/command patterns)
   - Calls `startup()` on each registered collector

### Phase 2: Collection (During Request Processing)

```
Application Code
      │
      ├──▶ Logger::log()          ──▶ LoggerProxy         ──▶ LogCollector
      ├──▶ EventDispatcher::dispatch() ──▶ EventProxy     ──▶ EventCollector
      ├──▶ HttpClient::sendRequest()   ──▶ HttpClientProxy ──▶ HttpClientCollector
      ├──▶ Container::get()        ──▶ ContainerProxy     ──▶ ServiceCollector
      ├──▶ VarDumper::dump()       ──▶ VarDumperProxy     ──▶ VarDumperCollector
      └──▶ throw Exception         ──▶ ExceptionHandler   ──▶ ExceptionCollector
```

During request processing, the application's code calls PSR services as usual.
Proxy wrappers transparently intercept these calls and feed data to collectors:

- **LoggerProxy** records every `log()` call with level, message, context, and timestamp
- **EventDispatcherProxy** records every dispatched event object
- **HttpClientProxy** records outgoing HTTP requests and responses
- **ContainerProxy** records which services are resolved from DI
- **VarDumperProxy** captures manual `dump()` calls
- **ExceptionCollector** registers a custom exception handler

Additional web-specific collectors (in ADP Kernel):
- **RequestCollector**: Full request/response data (headers, body, status code)
- **WebAppInfoCollector**: Application timing (startup, request processing, emit) and memory usage
- **TimelineCollector**: Timing data for profiling

External collectors (provided by framework-specific packages, not in ADP Kernel):
- **MiddlewareCollector**: Middleware stack (`yiisoft/yii-debug`)
- **RouterCollector**: Matched route info (`yiisoft/yii-debug`)
- **DatabaseCollector**: SQL queries (`yiisoft/db`)
- **MailerCollector**: Sent emails (`yiisoft/mailer`)

### Phase 3: Shutdown & Storage

```
Framework "after response" event
         │
         ▼
  Debugger::shutdown()
         │
         ▼
  ┌──────────────┐
  │  For each    │
  │  Collector:  │
  │  shutdown()  │
  │  getCollected()│
  └──────┬───────┘
         │
         ▼
  ┌──────────────┐
  │  Dumper      │──── Serialize objects with depth control
  │  serialize() │     and deduplication
  └──────┬───────┘
         │
         ▼
  ┌──────────────┐
  │  Storage     │──── Write to disk as JSON
  │  write()     │     {date}/{entry-id}/{type}.json
  └──────────────┘
```

1. The framework fires its "after response" or "shutdown" event
2. `Debugger::shutdown()` calls `shutdown()` on each collector
3. Collector data is serialized through `Dumper` (handles circular refs, depth limits)
4. Three data types are written to storage:
   - **Summary**: Lightweight metadata (timestamp, URL, status code, collector names)
   - **Data**: Full collector payloads
   - **Objects**: Serialized object dumps (for deep inspection)

### Phase 4: API Serving

```
Frontend Request
      │
      ▼
  API Middleware Chain
  ┌──────────────────┐
  │ 1. IpFilter      │──── Verify allowed IP
  │ 2. CORS          │──── Add CORS headers
  │ 3. ResponseWrap  │──── Wrap in {data, error, success, status}
  │ 4. DebugHeaders  │──── Add X-Debug-Id header
  └──────┬───────────┘
         │
         ▼
  ┌──────────────────┐
  │ CollectorRepo    │──── Read from Storage
  │ .getSummary()    │
  │ .getDetail(id)   │
  │ .getDumpObject() │
  └──────┬───────────┘
         │
         ▼
  JSON Response to Frontend
```

### Phase 5: Real-Time Updates (SSE)

```
Frontend                              Backend
   │                                     │
   │  GET /debug/api/event-stream        │
   │ ──────────────────────────────────▶ │
   │                                     │
   │  Content-Type: text/event-stream    │
   │ ◀────────────────────────────────── │
   │                                     │
   │  (backend polls storage hash)       │
   │                                     │
   │  data: {type: "debug-updated"}      │
   │ ◀────────────────────────────────── │ (hash changed = new entry)
   │                                     │
   │  Frontend fetches updated list      │
   │ ──────────────────────────────────▶ │
```

The SSE endpoint computes an MD5 hash of the current summary data every second.
When the hash changes (new debug entry written), it sends an event to the frontend,
which then fetches the updated list.

## Storage Format

Debug data is stored as JSON files on disk:

```
runtime/debug/
└── 2024-01-15/
    ├── abc123def456/
    │   ├── summary.json      # Request metadata
    │   ├── data.json          # Full collector data
    │   └── objects.json       # Serialized object dumps
    └── xyz789ghi012/
        ├── summary.json
        ├── data.json
        └── objects.json
```

`FileStorage` implements garbage collection: entries older than the configured
history size are automatically deleted on write.

## Console Command Flow

The same flow applies to CLI commands, with these differences:

- **ConsoleAppInfoCollector** replaces WebAppInfoCollector
- **CommandCollector** replaces RequestCollector
- Events: `ConsoleCommandEvent`, `ConsoleErrorEvent`, `ConsoleTerminateEvent`
- No middleware or router collectors
