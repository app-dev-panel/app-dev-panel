# Integration Flow

## Boot Sequence

```
1. Symfony Kernel boots
   ↓
2. bundles.php → AppDevPanelBundle registered
   ↓
3. AppDevPanelExtension::load()
   ├── Configuration tree processed
   ├── Core services registered (IdGenerator, Storage, TimelineCollector)
   ├── All enabled collectors registered (tagged app_dev_panel.collector)
   ├── Event subscribers registered (HttpSubscriber, ConsoleSubscriber, CorsSubscriber)
   ├── API services registered (middleware, controllers, inspector endpoints)
   ├── SymfonyConfigProvider registered as 'config' alias
   ├── DoctrineSchemaProvider or NullSchemaProvider registered
   └── AdpApiController registered for routing bridge
   ↓
4. CollectorProxyCompilerPass::process()
   ├── Collects all app_dev_panel.collector tagged services
   ├── Registers Debugger with all collectors + StorageInterface
   ├── Decorates 'logger' (or LoggerInterface FQCN) → LoggerInterfaceProxy
   ├── Decorates 'event_dispatcher' → SymfonyEventDispatcherProxy
   ├── Decorates ClientInterface (PSR-18) → HttpClientInterfaceProxy
   └── Collects container parameters → InspectController + SymfonyConfigProvider
   ↓
5. Container compiled and cached
```

## Web Request Lifecycle

```
1. HTTP request arrives
   ↓
2. kernel.request (priority 1024)
   ├── HttpSubscriber::onKernelRequest()
   ├── Skip if path starts with /debug/api or /inspect/api
   ├── Convert Symfony Request → PSR-7 ServerRequest (via nyholm/psr7-server)
   ├── Override URI and headers from Symfony Request for accuracy
   ├── Debugger::startup(StartupContext::forRequest($psrRequest))
   │   ├── Check ignored patterns
   │   ├── IdGenerator::reset() → new debug entry ID
   │   └── All collectors::startup()
   ├── WebAppInfoCollector::markApplicationStarted(), markRequestStarted()
   └── RequestCollector::collectRequest($psrRequest) — Kernel's generic PSR-7 collector
   ↓
3. Symfony routing, controller resolution, execution
   ├── Logger calls → LoggerInterfaceProxy → LogCollector
   ├── Event dispatches → SymfonyEventDispatcherProxy → EventCollector
   ├── HTTP client calls → HttpClientInterfaceProxy → HttpClientCollector
   ├── Cache calls → CacheCollector (via decorated adapter)
   ├── Doctrine queries → DatabaseCollector (via middleware/logger)
   ├── Twig renders → TwigCollector (via profiler extension)
   └── dump() calls → VarDumperCollector (via handler proxy)
   ↓
4. kernel.response (priority -1024)
   ├── HttpSubscriber::onKernelResponse()
   ├── WebAppInfoCollector::markRequestFinished()
   ├── Convert Symfony Response → PSR-7 ResponseInterface (via nyholm/psr7)
   ├── RequestCollector::collectResponse($psrResponse)
   └── Response gets X-Debug-Id header
   ↓
5. Response sent to client
   ↓
6. kernel.terminate (priority -2048)
   ├── HttpSubscriber::onKernelTerminate()
   ├── WebAppInfoCollector::markApplicationFinished()
   └── Debugger::shutdown()
       ├── All collectors::shutdown()
       └── FileStorage::flush()
           ├── Write summary.json (metadata + collector list)
           ├── Write data.json (collector payloads)
           ├── Write objects.json (serialized objects)
           └── Garbage collection (keep last N entries)
```

## Console Command Lifecycle

```
1. Console application runs
   ↓
2. console.command (priority 1024)
   ├── ConsoleSubscriber::onConsoleCommand()
   ├── Debugger::startup(StartupContext::forCommand($commandName))
   ├── ConsoleAppInfoCollector::collect()
   └── CommandCollector::collect()
   ↓
3. Command execution
   ├── Logger/Event/HTTP proxies collect data as above
   ↓
4a. console.error (on failure)
    ├── ExceptionCollector::collect($error)
    ├── ConsoleAppInfoCollector::collect()
    └── CommandCollector::collect()
   ↓
4b. console.terminate
    ├── ConsoleAppInfoCollector::collect()
    ├── CommandCollector::collect() (captures exit code)
    └── Debugger::shutdown() → flush to storage
```

## PSR-7 Bridge

Symfony uses HttpFoundation (non-PSR-7). The adapter converts both request and response:

1. **Request** (in `onKernelRequest`): `nyholm/psr7-server` creates PSR-7 `ServerRequestInterface` from `$_SERVER` globals. URI and headers are overridden from Symfony's `Request` for accuracy.
2. **Response** (in `onKernelResponse`): `Psr17Factory` creates PSR-7 `ResponseInterface` with status code, headers, and body from Symfony's `Response`.

PSR-7 conversion is needed for:
- `Debugger::startup()` — checks ignored request patterns via `StartupContext::forRequest()`
- `RequestCollector::collectRequest()` and `collectResponse()` — stores request/response data

## Proxy Decoration Details

### Logger

```
Container has 'logger'? → decorate 'logger'
Else has LoggerInterface FQCN? → decorate that
→ LoggerInterfaceProxy(inner, LogCollector)
```

Symfony registers loggers as `logger` service ID (via MonologBundle), not as `Psr\Log\LoggerInterface`. The compiler pass checks both, preferring `logger`.

### Event Dispatcher

```
Container has 'event_dispatcher'? → decorate with SymfonyEventDispatcherProxy
→ SymfonyEventDispatcherProxy(inner, EventCollector)
```

Cannot use Kernel's `EventDispatcherInterfaceProxy` (PSR-14 only) because:
- Symfony's `dispatch(object, ?string)` has a second parameter
- `SymfonyConfigProvider` needs `getListeners()` from `Symfony\Component\EventDispatcher\EventDispatcherInterface`
- Symfony passes lazy `[$service, 'method']` arrays to `addListener()` (not yet callable)

### HTTP Client

```
Container has ClientInterface? → decorate with HttpClientInterfaceProxy
→ HttpClientInterfaceProxy(inner, HttpClientCollector)
```

## Inspector Data Flow

```
Frontend ParametersPage → GET /inspect/api/params → InspectController::params()
  → returns $this->params (container parameters, 3rd constructor arg)

Frontend EventsPage → GET /inspect/api/events → InspectController::eventListeners()
  → $container->get('config') → SymfonyConfigProvider::get('events')
    → $dispatcher->getListeners() → describeListener() for each

Frontend ConfigPage → GET /inspect/api/config?group=X → InspectController::config()
  → SymfonyConfigProvider::get($group)
    → 'di'/'services': container service IDs
    → 'params'/'parameters': container parameters
    → 'events'/'events-web': event listeners
    → 'bundles': bundle configuration

Frontend DatabasePage → GET /inspect/api/table → DatabaseController
  → DoctrineSchemaProvider (if doctrine available) or NullSchemaProvider
```
