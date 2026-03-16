# Integration Flow

## Boot Sequence

```
1. Symfony Kernel boots
   ↓
2. bundles.php → AppDevPanelBundle registered
   ↓
3. AppDevPanelExtension::load()
   ├── Configuration tree processed
   ├── Core services registered (IdGenerator, Storage)
   ├── All enabled collectors registered (tagged app_dev_panel.collector)
   └── Event subscribers registered (HttpSubscriber, ConsoleSubscriber)
   ↓
4. CollectorProxyCompilerPass::process()
   ├── Collects all app_dev_panel.collector tagged services
   ├── Registers Debugger with all collectors
   ├── Decorates LoggerInterface → LoggerInterfaceProxy
   ├── Decorates EventDispatcherInterface → EventDispatcherInterfaceProxy
   └── Decorates ClientInterface → HttpClientInterfaceProxy
   ↓
5. Container compiled and cached
```

## Web Request Lifecycle

```
1. HTTP request arrives
   ↓
2. kernel.request (priority 1024)
   ├── HttpSubscriber::onKernelRequest()
   ├── Convert Symfony Request → PSR-7 ServerRequest (via nyholm/psr7)
   ├── Debugger::startup(StartupContext::forRequest($psrRequest))
   │   ├── Check ignored patterns
   │   ├── IdGenerator::reset() → new debug entry ID
   │   └── All collectors::startup()
   ├── WebAppInfoCollector::collect()
   └── SymfonyRequestCollector::collectRequest($symfonyRequest)
   ↓
3. Symfony routing, controller resolution, execution
   ├── Logger calls → LoggerInterfaceProxy → LogCollector
   ├── Event dispatches → EventDispatcherInterfaceProxy → EventCollector
   ├── HTTP client calls → HttpClientInterfaceProxy → HttpClientCollector
   ├── Cache calls → CacheCollector (via decorated adapter)
   ├── Doctrine queries → DoctrineCollector (via middleware/logger)
   ├── Twig renders → TwigCollector (via profiler extension)
   └── dump() calls → VarDumperCollector (via handler proxy)
   ↓
4. kernel.response (priority -1024)
   ├── HttpSubscriber::onKernelResponse()
   ├── SymfonyRequestCollector::collectResponse($response)
   └── Response gets X-Debug-Id header
   ↓
5. Response sent to client
   ↓
6. kernel.terminate (priority -2048)
   ├── HttpSubscriber::onKernelTerminate()
   └── Debugger::shutdown()
       ├── All collectors::shutdown()
       └── FileStorage::flush()
           ├── Write summary.json (metadata)
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
    └── CommandCollector::collect()
   ↓
4b. console.terminate
    ├── CommandCollector::collect() (captures exit code)
    └── Debugger::shutdown() → flush to storage
```

## PSR-7 Bridge

Symfony uses HttpFoundation (non-PSR-7). The adapter bridges this gap:

1. **Request**: `nyholm/psr7-server` converts `$_SERVER` globals to PSR-7 `ServerRequestInterface`
2. **URI override**: Symfony's `Request::getUri()` is used to ensure the PSR-7 URI matches
3. **Headers**: Copied from Symfony's `HeaderBag` to PSR-7 message
4. **Response**: `SymfonyRequestCollector` works directly with HttpFoundation `Response` — no PSR-7 conversion needed

The PSR-7 conversion is only needed for `Debugger::startup()` which checks ignored request patterns via `StartupContext::forRequest()`.
