# Variant D: Minimal Zen — Debug Collector Detail

## Concept

When the user clicks "Open full" on an expanded card (or navigates via command palette), the
collector detail view takes over the full content area. One column, generous whitespace, inline
actions. A breadcrumb at the top provides context. Tabs within the page switch content sections
without a page reload.

## Request Collector — Full Detail

```
┌────────────────────────────────────────────────────────────────────────────────────────────────────────────────────┐
│ ◆ ADP          ┌─ GET /api/users ── 200 ── 143ms ─┐     ◁  ▷         ⌘K Search…            ☀  ⋮                  │
│                 └──────────────────────────────────-┘                                                              │
├────────────────────────────────────────────────────────────────────────────────────────────────────────────────────┤
│                                                                                                                    │
│         Debug  ›  Request                                                                        ◁ Back to cards   │
│                                                                                                                    │
│         ───────────────────────────────────────────────────────────────────────────────────────────────────          │
│                                                                                                                    │
│         GET /api/users                                                                                             │
│                                                                                                                    │
│         200 OK         143ms         1.2 KB         HTTP/1.1                                                       │
│                                                                                                                    │
│         ┌─────────┐ ┌──────────┐ ┌──────────┐ ┌──────────┐ ┌─────────┐                                            │
│         │ Request │ │ Response │ │ Headers  │ │ Timing   │ │  cURL   │                                            │
│         └─────────┘ └──────────┘ └──────────┘ └──────────┘ └─────────┘                                            │
│          ▔▔▔▔▔▔▔▔▔                                                                                                │
│                                                                                                                    │
│         Controller     App\Controller\UserController::index                              ⎘ Copy                    │
│         Route          api/users                                                         ⎘ Copy                    │
│         Action         index                                                                                       │
│                                                                                                                    │
│         Request Headers                                                                                            │
│         ─────────────────────────────────────────────────────────────────────────                                   │
│         Accept              application/json                                             ⎘ Copy                    │
│         Host                localhost:8080                                                ⎘ Copy                    │
│         Authorization       Bearer eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJz...         ⎘ Copy                    │
│         Content-Type        application/json                                             ⎘ Copy                    │
│         User-Agent          Mozilla/5.0 (X11; Linux x86_64)                              ⎘ Copy                    │
│         X-Request-Id        f47ac10b-58cc-4372-a567-0e02b2c3d479                         ⎘ Copy                    │
│                                                                                                                    │
│         Query Parameters                                                                                           │
│         ─────────────────────────────────────────────────────────────────────────                                   │
│         page              1                                                                                        │
│         limit             25                                                                                       │
│         sort              -created_at                                                                              │
│                                                                                                                    │
│         Body                                                                                      ⎘ Copy All       │
│         ─────────────────────────────────────────────────────────────────────────                                   │
│         (empty)                                                                                                    │
│                                                                                                                    │
│                                                                                                                    │
│         ─────────────────────────────────────────────────────────────────────────────────────────────────────        │
│         Repeat Request          Copy cURL          Export JSON          Compare with…                               │
│                                                                                                                    │
└────────────────────────────────────────────────────────────────────────────────────────────────────────────────────┘
```

## Log Collector — Full Detail

```
┌────────────────────────────────────────────────────────────────────────────────────────────────────────────────────┐
│ ◆ ADP          ┌─ GET /api/users ── 200 ── 143ms ─┐     ◁  ▷         ⌘K Search…            ☀  ⋮                  │
│                 └──────────────────────────────────-┘                                                              │
├────────────────────────────────────────────────────────────────────────────────────────────────────────────────────┤
│                                                                                                                    │
│         Debug  ›  Log                                                                            ◁ Back to cards   │
│                                                                                                                    │
│         ───────────────────────────────────────────────────────────────────────────────────────────────────          │
│                                                                                                                    │
│         12 log messages                                                    Filter: ┌─ All levels ─┐  ┌─ Search… ─┐ │
│                                                                                    └──────────────┘  └───────────┘ │
│                                                                                                                    │
│         14:23:01.003   error     Failed to fetch user avatar                                                       │
│         ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─            │
│                        Category: app                                                                               │
│                        Context:  {"user_id": 42, "error": "Connection timed out"}                                  │
│                                                                                                                    │
│         14:23:01.001   error     PDOException: SQLSTATE[HY000]                                                     │
│         ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─            │
│                        Category: db                                                                                │
│                                                                                                                    │
│         14:23:00.998   error     Request validation failed for /api/users                                          │
│         ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─            │
│                        Category: validation                                                                        │
│                                                                                                                    │
│         14:23:00.950   warning   Deprecated: Method getUserName() is deprecated                                    │
│                                                                                                                    │
│         14:23:00.940   warning   Slow query detected: 45ms                                                         │
│                                                                                                                    │
│         14:23:00.930   warning   Cache miss for key "users:list:page1"                                             │
│                                                                                                                    │
│         14:23:00.920   warning   Rate limit approaching: 85/100 requests                                           │
│                                                                                                                    │
│         14:23:00.900   info      Resolving UserController::index                                                   │
│                                                                                                                    │
│         14:23:00.880   info      Database query: SELECT * FROM users LIMIT 25                                      │
│                                                                                                                    │
│         14:23:00.870   info      Cache lookup: users:list:page1                                                    │
│                                                                                                                    │
│         14:23:00.850   info      Matched route: api/users                                                          │
│                                                                                                                    │
│         14:23:00.840   info      Request started: GET /api/users                                                   │
│                                                                                                                    │
│                                                                                                                    │
└────────────────────────────────────────────────────────────────────────────────────────────────────────────────────┘
```

## Database Collector — Full Detail

```
┌────────────────────────────────────────────────────────────────────────────────────────────────────────────────────┐
│ ◆ ADP          ┌─ GET /api/users ── 200 ── 143ms ─┐     ◁  ▷         ⌘K Search…            ☀  ⋮                  │
│                 └──────────────────────────────────-┘                                                              │
├────────────────────────────────────────────────────────────────────────────────────────────────────────────────────┤
│                                                                                                                    │
│         Debug  ›  Database                                                                       ◁ Back to cards   │
│                                                                                                                    │
│         ───────────────────────────────────────────────────────────────────────────────────────────────────          │
│                                                                                                                    │
│         4 queries       18.3ms total                                               ┌─ Search queries… ─┐           │
│                                                                                    └───────────────────┘           │
│                                                                                                                    │
│    ┌──────────────────────────────────────────────────────────────────────────────────────────────────────────────┐ │
│    │                                                                                                              │ │
│    │  #1     8.2ms                                                                         ⎘ Copy   ▶ Explain   │ │
│    │                                                                                                              │ │
│    │  SELECT u.id, u.name, u.email, u.created_at                                                                  │ │
│    │  FROM users u                                                                                                │ │
│    │  WHERE u.active = 1                                                                                          │ │
│    │  ORDER BY u.created_at DESC                                                                                  │ │
│    │  LIMIT 25 OFFSET 0                                                                                           │ │
│    │                                                                                                              │ │
│    └──────────────────────────────────────────────────────────────────────────────────────────────────────────────┘ │
│                                                                                                                    │
│    ┌──────────────────────────────────────────────────────────────────────────────────────────────────────────────┐ │
│    │                                                                                                              │ │
│    │  #2     5.1ms                                                                         ⎘ Copy   ▶ Explain   │ │
│    │                                                                                                              │ │
│    │  SELECT COUNT(*) FROM users WHERE active = 1                                                                 │ │
│    │                                                                                                              │ │
│    └──────────────────────────────────────────────────────────────────────────────────────────────────────────────┘ │
│                                                                                                                    │
│    ┌──────────────────────────────────────────────────────────────────────────────────────────────────────────────┐ │
│    │                                                                                                              │ │
│    │  #3     3.5ms                                                                         ⎘ Copy   ▶ Explain   │ │
│    │                                                                                                              │ │
│    │  SELECT r.name FROM roles r                                                                                  │ │
│    │  INNER JOIN user_roles ur ON ur.role_id = r.id                                                               │ │
│    │  WHERE ur.user_id IN (1, 2, 3, 5, 8, 13, 21, 34)                                                            │ │
│    │                                                                                                              │ │
│    └──────────────────────────────────────────────────────────────────────────────────────────────────────────────┘ │
│                                                                                                                    │
│    ┌──────────────────────────────────────────────────────────────────────────────────────────────────────────────┐ │
│    │                                                                                                              │ │
│    │  #4     1.5ms                                                                         ⎘ Copy   ▶ Explain   │ │
│    │                                                                                                              │ │
│    │  SELECT value FROM cache WHERE key = 'users:list:page1' AND expires_at > NOW()                               │ │
│    │                                                                                                              │ │
│    └──────────────────────────────────────────────────────────────────────────────────────────────────────────────┘ │
│                                                                                                                    │
│                                                                                                                    │
│         ─────────────────────────────────────────────────────────────────────────────────────────────────────        │
│         Export All Queries          Copy All as SQL                                                                 │
│                                                                                                                    │
└────────────────────────────────────────────────────────────────────────────────────────────────────────────────────┘
```

## Exception Collector — Full Detail

```
┌────────────────────────────────────────────────────────────────────────────────────────────────────────────────────┐
│ ◆ ADP          ┌─ POST /api/orders ── 500 ── 342ms ┐    ◁  ▷         ⌘K Search…            ☀  ⋮                  │
│                 └───────────────────────────────────-┘                                                             │
├────────────────────────────────────────────────────────────────────────────────────────────────────────────────────┤
│                                                                                                                    │
│         Debug  ›  Exception                                                                      ◁ Back to cards   │
│                                                                                                                    │
│         ───────────────────────────────────────────────────────────────────────────────────────────────────          │
│                                                                                                                    │
│         RuntimeException                                                                                           │
│         Order total exceeds maximum allowed amount                                                                 │
│                                                                                                                    │
│         Code      0                                                                                                │
│         File      src/Service/OrderService.php:142                                                ⎘ Copy Path      │
│         Time      2025-03-15 14:22:12                                                                              │
│                                                                                                                    │
│         Stack Trace                                                                                                │
│         ─────────────────────────────────────────────────────────────────────────                                   │
│                                                                                                                    │
│         #0  src/Service/OrderService.php:142                                                                       │
│             OrderService->validateTotal(amount: 15000.00)                                                          │
│                                                                                                                    │
│         #1  src/Service/OrderService.php:89                                                                        │
│             OrderService->create(data: array(4))                                                                   │
│                                                                                                                    │
│         #2  src/Controller/OrderController.php:56                                                                  │
│             OrderController->store(request: ServerRequest)                                                         │
│                                                                                                                    │
│         #3  vendor/yiisoft/router/src/MiddlewareDispatcher.php:42                                                  │
│             MiddlewareDispatcher->dispatch(request: ServerRequest)                                                 │
│                                                                                                                    │
│         #4  vendor/yiisoft/middleware-dispatcher/src/MiddlewareStack.php:38                                         │
│             MiddlewareStack->handleRequest(request: ServerRequest)                                                 │
│                                                                                                                    │
│                                                                                                                    │
│         ─────────────────────────────────────────────────────────────────────────────────────────────────────        │
│         Copy Stack Trace          Search Similar Issues          Open in IDE                                       │
│                                                                                                                    │
└────────────────────────────────────────────────────────────────────────────────────────────────────────────────────┘
```

## Inline Actions Bar

All detail pages share a bottom actions bar. Actions are text buttons, not icons.

```
         ─────────────────────────────────────────────────────────────────────────────────────────────────────
         Repeat Request          Copy cURL          Export JSON          Compare with…
```

Actions are contextual — each collector shows relevant actions only.
