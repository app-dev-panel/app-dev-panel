# Integration Flow

How ADP integrates with a Yii 2 application, step by step.

## Boot Sequence

```
1. Composer autoload
   └─ extra.bootstrap loads Bootstrap class

2. Bootstrap::bootstrap($app)
   ├─ Check shouldEnable() (YII_DEBUG or explicit config)
   ├─ Register 'debug-panel' module if not present
   └─ Call Module::bootstrap($app)

3. Module::bootstrap($app)
   ├─ registerServices()         → DI singletons: Storage, IdGenerator, PSR-17, API
   ├─ registerCollectors()       → Create collector instances based on config
   ├─ buildDebugger()            → Build Debugger with collectors + storage
   ├─ registerEventListeners()   → Attach web/console event listeners
   │   ├─ WebListener            → EVENT_BEFORE_REQUEST, EVENT_AFTER_REQUEST
   │   ├─ ConsoleListener        → EVENT_BEFORE_REQUEST, EVENT_AFTER_REQUEST
   │   └─ DB profiling           → yii\db\Command::EVENT_AFTER_EXECUTE
   └─ registerRoutes()           → URL rules for /debug/api/*, /inspect/api/*
```

## Web Request Lifecycle

```
Browser Request
    │
    ▼
┌─── Yii 2 Application ───────────────────────────────┐
│                                                       │
│  EVENT_BEFORE_REQUEST                                │
│  └─ WebListener::onBeforeRequest()                   │
│     ├─ Convert Yii Request → PSR-7                   │
│     ├─ Debugger::startup(context)                    │
│     ├─ WebAppInfoCollector::markApplicationStarted() │
│     ├─ WebAppInfoCollector::markRequestStarted()     │
│     └─ RequestCollector::collectRequest()            │
│                                                       │
│  ┌── Controller Action ──────────────────────┐       │
│  │ DB queries  → DatabaseCollector (via DbProfilingTarget) │       │
│  │ Yii logs    → DebugLogTarget (real-time)   │       │
│  │ Exceptions  → ErrorHandler catches        │       │
│  └───────────────────────────────────────────┘       │
│                                                       │
│  EVENT_AFTER_REQUEST                                 │
│  └─ WebListener::onAfterRequest()                    │
│     ├─ WebAppInfoCollector::markRequestFinished()    │
│     ├─ Convert Yii Response → PSR-7                  │
│     ├─ RequestCollector::collectResponse()           │
│     ├─ Add X-Debug-Id header                         │
│     ├─ WebAppInfoCollector::markApplicationFinished() │
│     └─ Debugger::shutdown() → flush to FileStorage   │
│                                                       │
└───────────────────────────────────────────────────────┘
    │
    ▼
Debug data stored in @runtime/debug/
```

## Console Command Lifecycle

```
php yii <command>
    │
    ▼
┌─── Yii 2 Console Application ───────────────────────┐
│                                                       │
│  EVENT_BEFORE_REQUEST                                │
│  └─ ConsoleListener::onBeforeRequest()               │
│     ├─ Extract command name from params              │
│     ├─ Debugger::startup(context)                    │
│     ├─ ConsoleAppInfoCollector::collect()            │
│     └─ CommandCollector::collect()                   │
│                                                       │
│  ┌── Command Action ─────────────────────────┐       │
│  │ DB queries  → DatabaseCollector (via DbProfilingTarget) │       │
│  │ Yii logs    → DebugLogTarget (real-time)   │       │
│  └───────────────────────────────────────────┘       │
│                                                       │
│  EVENT_AFTER_REQUEST                                 │
│  └─ ConsoleListener::onAfterRequest()                │
│     ├─ Capture exceptions from error handler         │
│     ├─ ConsoleAppInfoCollector::collect()            │
│     ├─ CommandCollector::collect()                   │
│     └─ Debugger::shutdown() → flush to FileStorage   │
│                                                       │
└───────────────────────────────────────────────────────┘
```

## API Request Flow

```
GET /debug/api/entries
    │
    ▼
Yii 2 UrlManager
    ├─ Match: debug/api/<path:.*>
    └─ Route: debug-panel/adp-api/handle
         │
         ▼
    AdpApiController::actionHandle()
    ├─ Convert Yii Request → PSR-7
    ├─ ApiApplication::handle($psrRequest)
    │   ├─ IpFilterMiddleware
    │   ├─ TokenAuthMiddleware
    │   ├─ ResponseDataWrapper
    │   └─ Controller dispatch
    ├─ Convert PSR-7 Response → Yii Response
    └─ Return (with CORS headers from beforeAction)
```
