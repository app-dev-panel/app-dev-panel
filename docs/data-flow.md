# Data Flow

## Debugger Request Lifecycle (PHP Adapter)

### Phase 1: Startup

```
User Request -> Framework Router -> Adapter Event Listener
                                         |
                                         v
                                  Debugger::startup(StartupContext)
                                         |
                                  +------+------+
                                  |  For each    |
                                  |  Collector:  |
                                  |  startup()   |
                                  +-------------+
```

1. The target application receives an HTTP request (or CLI command)
2. The Adapter's event listener catches the framework's startup event
3. `Debugger::startup()` is called, which:
   - Sets `$active = true`, `$skipCollect = false`
   - Registers `register_shutdown_function([$this, 'shutdown'])`
   - Checks if the request/command should be ignored via `WildcardPattern`:
     - Requests: checks `X-Debug-Ignore` header + `$ignoredRequests` patterns
     - Commands: checks `YII_DEBUG_IGNORE` env + `$ignoredCommands` patterns
   - If not ignored: resets `DebuggerIdGenerator`, calls `startup()` on all collectors, adds collectors to storage

### Phase 2: Collection (During Request Processing)

```
Application Code
      |
      +-->  Logger::log()               -->  LoggerInterfaceProxy         -->  LogCollector
      +-->  EventDispatcher::dispatch()  -->  EventDispatcherInterfaceProxy -->  EventCollector
      +-->  HttpClient::sendRequest()    -->  HttpClientInterfaceProxy     -->  HttpClientCollector
      +-->  Container::get()             -->  ContainerInterfaceProxy      -->  ServiceCollector
      +-->  VarDumper::dump()            -->  VarDumperHandlerInterfaceProxy -->  VarDumperCollector
      +-->  throw Exception              -->  ExceptionHandler              -->  ExceptionCollector
```

Proxy wrappers transparently intercept calls and feed data to collectors:

- **LoggerInterfaceProxy**: Records every `log()` call with level, message, context, file:line, timestamp via `debug_backtrace()`
- **EventDispatcherInterfaceProxy**: Records every dispatched event object with file:line
- **HttpClientInterfaceProxy**: Records outgoing HTTP requests/responses with timing (generates unique ID, measures start/end time)
- **ContainerInterfaceProxy**: Records service resolutions from DI. Uses `Yiisoft\Proxy\ProxyManager` for runtime proxy generation. Wraps tracked services in `ServiceProxy` for method-level tracking. Prevents infinite recursion via static `$resolving` flag.
- **VarDumperHandlerInterfaceProxy**: Captures manual `dump()` calls
- **ExceptionCollector**: Registers custom exception handler, captures full exception chain

Web-specific collectors:

- **RequestCollector**: Full request/response data (headers, body, status, URL, method, IP)
- **WebAppInfoCollector**: Application metadata (routes, DI config, environment)
- **TimelineCollector**: Tracks event sequence across all collectors with microsecond timestamps
- **FilesystemStreamCollector / HttpStreamCollector**: Track stream operations

### Phase 3: Shutdown and Storage

```
Framework "after response" event
         |
         v
  Debugger::shutdown()
         |
         v (if !$skipCollect)
  +--------------------+
  |  Storage::flush()  |
  +---------+----------+
            |
  +---------+---------+
  |                   |
  v                   v
Dumper::asJson()    Dumper::asJsonObjectsMap()
  |                   |
  v                   v
data.json           objects.json
            |
            v
  collectSummaryData() from SummaryCollectors
            |
            v
  summary.json
            |
            v
  FileStorage::gc()  (async, non-blocking lock)
```

1. Framework fires "after response" / "after emit" event
2. `Debugger::shutdown()`:
   - If `$skipCollect = false`: calls `$target->flush()`
   - Always calls `shutdown()` on all collectors (resets internal state)
   - Sets `$active = false`
3. `FileStorage::flush()`:
   - Calls `getData()` on all collectors
   - Serializes via `Dumper::create(collectors, excludedClasses)`:
     - `asJson(depth=30)` -> `data.json` (depth-limited, circular ref handling)
     - `asJsonObjectsMap(depth=30)` -> `objects.json` (extracted unique objects)
   - Collects summaries from `SummaryCollectorInterface` implementations -> `summary.json`
   - All writes use `file_put_contents($path, $content, LOCK_EX)` for atomicity
4. Garbage collection runs after flush:
   - Acquires `LOCK_EX | LOCK_NB` on `.gc.lock` (skips if another process holds it)
   - Deletes entries beyond `historySize` (default 50), sorted by modification time

### Phase 4: API Serving

```
Frontend Request
      |
      v
  API Middleware Chain
  +----------------------------+
  | 1. CorsAllowAll            |
  | 2. IpFilter                |
  | 3. TokenAuthMiddleware     |
  | 4. FormatDataResponseAsJson|
  | 5. ResponseDataWrapper     |
  +----------+-----------------+
             |
             v
  +----------------------------+
  | CollectorRepository        |
  | .getSummary()              | --> StorageInterface::read(TYPE_SUMMARY)
  | .getDetail(id)             | --> StorageInterface::read(TYPE_DATA)
  | .getDumpObject(id)         | --> StorageInterface::read(TYPE_OBJECTS)
  | .getObject(id, objectId)   | --> Find specific object in dump
  +----------+-----------------+
             |
             v
  JSON Response: {id, data, error, success, status}
```

### Phase 5: Real-Time Updates (SSE)

```
Frontend                              Backend
   |                                     |
   |  GET /debug/api/event-stream        |
   | ----------------------------------> |
   |                                     |
   |  Content-Type: text/event-stream    |
   | <---------------------------------- |
   |                                     |
   |  (polls storage MD5 hash every 1s)  |
   |                                     |
   |  data: {"type": "debug-updated"}    |
   | <---------------------------------- | (hash changed = new entry)
   |                                     |
   |  Frontend fetches updated list      |
   | ----------------------------------> |
```

The SSE endpoint (`ServerSentEventsStream`) computes an MD5 hash of the current summary data every second.
When the hash changes (new debug entry written), it sends an event to the frontend.

Frontend uses `ServerSentEventsObserver` with exponential backoff: 1s base delay, doubles per attempt, 30s max, resets on successful connection.

## Ingestion API Flow (Any Language)

```
External App (Python/Node.js/Go/etc.)
      |
      | POST /debug/api/ingest
      v
IngestionController::ingest()
      |
      v
Validate JSON body: { collectors: {...}, summary?: {...}, context?: {...} }
      |
      v
DebuggerIdGenerator::getId()  (auto-generate entry ID)
      |
      v
StorageInterface::write(id, summary, data, objects)
      |
      v
FileStorage writes to disk:
  /storage/YYYY-MM-DD/{id}/summary.json
  /storage/YYYY-MM-DD/{id}/data.json
  /storage/YYYY-MM-DD/{id}/objects.json
      |
      v
Response: {id, success: true} (201)
```

Ingestion bypasses collectors entirely -- writes directly to storage using the same file format. Data appears in the debugger UI alongside PHP debug entries.

### Ingestion Endpoints

| Endpoint | Description |
|----------|-------------|
| `POST /debug/api/ingest` | Single debug entry with collectors + optional context/summary |
| `POST /debug/api/ingest/batch` | Multiple entries (max 100). Returns `{ids: [...], count}` |
| `POST /debug/api/ingest/log` | Shorthand for single log entry: `{level, message, context?}` |
| `GET /debug/api/openapi.json` | Serves `openapi/ingestion.yaml` as JSON |

Pre-built clients: Python (`clients/python/adp-client`), TypeScript (`clients/typescript/@app-dev-panel/client`).

## Inspector Proxy Flow (Multi-App)

```
Frontend: /inspect/api/routes?service=python-app
      |
      v
InspectorProxyMiddleware
      |
      +-- Extract service name: "python-app"
      +-- ServiceRegistry::resolve("python-app") -> ServiceDescriptor
      +-- Check online: lastSeenAt within 60s?
      +-- Map path to capability: /routes -> "routes"
      +-- Check descriptor.supports("routes")
      |
      v (all checks pass)
Proxy request to: {inspectorUrl}/inspect/api/routes
      |
      +-- Strip ?service= param
      +-- Forward remaining query params
      +-- PSR-18 ClientInterface::sendRequest()
      |
      v
Return response from external service
```

### Capability Map

| Path prefix | Capability |
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
| `/classes` | `classes` |
| `/object` | `object` |
| `/phpinfo` | `phpinfo` |
| `/opcache` | `opcache` |
| `/request`, `/curl/build` | `request` |

### Error Responses

- Service not found: 404
- Service offline (heartbeat timeout): 503
- Capability not supported: 501
- No inspector URL configured: 502
- Connection refused / host unresolved: 502
- Request timeout: 504

## Service Registry Flow

```
External App                          ADP
     |                                 |
     | POST /debug/api/services/register
     | {service, language, inspectorUrl, capabilities}
     | ------------------------------> |
     |                                 | -> FileServiceRegistry::register()
     |                                 |    -> Write .services.json (LOCK_EX)
     |                                 |
     | POST /debug/api/services/heartbeat
     | {service}                       |
     | ------------------------------> | (every <60s to stay online)
     |                                 | -> Update lastSeenAt timestamp
     |                                 |
     | Frontend fetches:               |
     | GET /debug/api/services/        |
     | <------------------------------ | -> List all with online/offline status
```

`ServiceDescriptor` value object: `{service, language, inspectorUrl, capabilities[], registeredAt, lastSeenAt}`.
`isOnline()` returns true if `now() - lastSeenAt < 60s`. `supports(capability)` checks capabilities array (wildcard `*` matches all).

## Debug Server Flow (UDP Socket)

```
$ php yii dev -a 0.0.0.0 -p 8890

1. Connection::create() -> Create AF_UNIX SOCK_DGRAM socket
2. Connection::bind()   -> Bind to /tmp/yii-dev-server-{random}.sock
3. Print: "Listening on {socket_uri}"
4. SocketReader::read() -> Generator loop:
     |
     +-- Read 8-byte header (message length)
     +-- Read base64-encoded payload
     +-- Decode JSON: [type, data]
     +-- Yield [TYPE_RESULT, payload]
     |
     v
5. Match message type:
     MESSAGE_TYPE_VAR_DUMPER (0x001B) -> Display as "VarDumper" block
     MESSAGE_TYPE_LOGGER             -> Display as "Logger" block
     default                         -> Display as "Plain text" block
```

### Broadcaster (Application -> Server)

```
Application calls dump($var) or Logger::log()
      |
      v
VarDumperHandler / LoggerDecorator
      |
      v
Broadcaster::broadcast(type, data)
      |
      +-- Glob /tmp/yii-dev-server-*.sock
      +-- For each socket file:
      |     fsockopen(socket_uri)
      |     Write: 8-byte length + base64(json_encode([type, data]))
      +-- Handle errors (ECONNREFUSED = server not running)
```

## Storage Format

```
runtime/debug/
+-- YYYY-MM-DD/
|   +-- {entryId}/
|   |   +-- summary.json      # Entry metadata + collector summaries
|   |   +-- data.json          # Full collector data (depth-limited serialization)
|   |   +-- objects.json       # Extracted objects for deep inspection
|   +-- {entryId}/
|   |   +-- ...
|   +-- .gc.lock               # GC lock file (non-blocking)
+-- .services.json             # Service registry (multi-app)
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

### Collector Summary Keys

| Collector | Summary key | Fields |
|-----------|-------------|--------|
| `LogCollector` | `logger` | `{total}` |
| `EventCollector` | `event` | `{total}` |
| `HttpClientCollector` | `http` | `{count, totalTime}` |
| `ServiceCollector` | `service` | `{total, totalTime}` |
| `ExceptionCollector` | `exception` | `{class, message, file, line, code}` or null |
| `VarDumperCollector` | `var-dumper` | `{total}` |

## Console Command Flow

Same lifecycle as web requests, with these differences:

- **ConsoleAppInfoCollector** replaces WebAppInfoCollector
- **CommandCollector** replaces RequestCollector
- Events: `ConsoleCommandEvent` -> startup, `ConsoleTerminateEvent` -> shutdown, `ConsoleErrorEvent` -> exception
- No middleware, router, or asset collectors

## Yii 3 Adapter Event Wiring

### Web Events

| Event | Handler |
|-------|---------|
| `ApplicationStartup` | `Debugger::startup(StartupContext::generic())` |
| `BeforeRequest` | `Debugger::startup(StartupContext::forRequest($request))` |
| `AfterRequest` | Collectors observe response |
| `AfterEmit` | `Debugger::shutdown()` |
| `ApplicationError` | `ExceptionCollector::collect()` |

### Console Events

| Event | Handler |
|-------|---------|
| `ApplicationStartup` | `Debugger::startup(StartupContext::generic())` |
| `ConsoleCommandEvent` | `Debugger::startup(StartupContext::forCommand($name))` |
| `ConsoleTerminateEvent` | `Debugger::shutdown()` |
| `ConsoleErrorEvent` | `ExceptionCollector::collect()` |

## Adapter Configuration

```php
'app-dev-panel/yii-debug' => [
    'enabled' => true,
    'collectors' => [LogCollector::class, EventCollector::class, ...],
    'trackedServices' => [MyService::class => CustomProxy::class],
    'ignoredRequests' => ['/assets/*'],
    'ignoredCommands' => [],
    'dumper.excludedClasses' => [],
    'logLevel' => 0,  // ContainerInterfaceProxy::LOG_NOTHING
    'path' => '@runtime/debug',
    'historySize' => 50,
    'devServer.address' => '0.0.0.0',
    'devServer.port' => 8890,
    'authToken' => '',           // Empty = disabled
    'requestReplay.allowedHosts' => [],  // Empty = allow all
]
```
