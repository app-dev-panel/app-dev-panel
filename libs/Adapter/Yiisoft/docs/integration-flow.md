# Integration Flow

This document describes how the Yii adapter integrates ADP into a Yii 3 application,
step by step.

## Boot Sequence

```
1. Composer autoload
       │
2. Config plugin loads params.php
       │
3. DI container built with di.php + di-web.php (or di-console.php)
       │
4. Service provider registered (di-providers.php)
       │  └── DebugServiceProvider wraps ContainerInterface
       │
5. Bootstrap runs (bootstrap.php)
       │  └── VarDumper handler replaced with proxy
       │
6. Event listeners registered (events-web.php or events-console.php)
       │
7. Application starts → ApplicationStartup event
       │  └── Debugger::startup() called
       │
8. Request processing (proxies intercept PSR calls)
       │
9. Response sent → AfterEmit event
       │  └── Debugger::shutdown() called → data flushed to storage
```

## Proxy Registration in DI

The adapter registers proxy classes as **decorators** of the original services:

```php
// di.php (simplified)
return [
    // Wrap logger
    LoggerInterface::class => static function (ContainerInterface $container) {
        $realLogger = $container->get('original.logger');
        $collector = $container->get(LogCollector::class);
        return new LoggerInterfaceProxy($realLogger, $collector);
    },

    // Wrap event dispatcher
    EventDispatcherInterface::class => static function (ContainerInterface $container) {
        $realDispatcher = $container->get('original.dispatcher');
        $collector = $container->get(EventCollector::class);
        return new EventDispatcherInterfaceProxy($realDispatcher, $collector);
    },

    // Wrap HTTP client
    ClientInterface::class => static function (ContainerInterface $container) {
        $realClient = $container->get('original.client');
        $collector = $container->get(HttpClientCollector::class);
        return new HttpClientInterfaceProxy($realClient, $collector);
    },
];
```

The application code continues to type-hint against PSR interfaces and receives
the proxy transparently.

## Event Lifecycle Mapping

### Web Request

```
┌──────────────────────┐     ┌─────────────────────────────────────────────┐
│ Yii Framework Event  │     │ ADP Action                                  │
├──────────────────────┤     ├─────────────────────────────────────────────┤
│ ApplicationStartup   │ ──▶ │ Debugger::startup(), markApplicationStarted │
│ BeforeRequest        │ ──▶ │ Debugger::startup(request), markRequestStarted, collectRequest │
│ AfterRequest         │ ──▶ │ markRequestFinished, collectResponse        │
│ ApplicationShutdown  │ ──▶ │ markApplicationFinished                     │
│ AfterEmit            │ ──▶ │ Profiler::flush(), Debugger::shutdown()     │
│ ApplicationError     │ ──▶ │ ExceptionCollector                          │
└──────────────────────┘     └─────────────────────────────────────────────┘
```

### Console Command

```
┌──────────────────────┐     ┌─────────────────────────────────────────────┐
│ Yii Framework Event  │     │ ADP Action                                  │
├──────────────────────┤     ├─────────────────────────────────────────────┤
│ ApplicationStartup   │ ──▶ │ Debugger::startup(), markApplicationStarted │
│ ApplicationShutdown  │ ──▶ │ markApplicationFinished, Debugger::shutdown  │
│ ConsoleCommandEvent  │ ──▶ │ ConsoleAppInfoCollector, CommandCollector    │
│ ConsoleErrorEvent    │ ──▶ │ ConsoleAppInfoCollector, CommandCollector    │
│ ConsoleTerminateEvent│ ──▶ │ ConsoleAppInfoCollector, CommandCollector    │
└──────────────────────┘     └─────────────────────────────────────────────┘
```

## Adapter as Reference

This Yii adapter serves as the reference implementation for other adapters.
The key responsibilities any adapter must fulfill:

1. **Proxy wiring** — Replace PSR services with proxied versions via DI
2. **Lifecycle hooks** — Map `startup()` and `shutdown()` to framework events
3. **Context separation** — Different collectors for web vs. CLI
4. **Configuration** — Provide defaults and allow user overrides
5. **Bootstrap** — Wire early interceptors (VarDumper, stream wrappers)
