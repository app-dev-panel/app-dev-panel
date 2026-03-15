# Variant C: Timeline-Driven — Detail Panel

## Detail Panel Structure

The detail panel occupies the bottom portion of the screen, below the timeline. Its content
changes based on the selected timeline segment. A resizable divider separates timeline from detail.

```
┌─── ═══════════════════════════════════ drag divider ═══════════════════════════════════════ ───┐
│                                                                                                │
│  Detail Panel ── [Segment Type]: [Segment Name] ──────────────────── Duration: Xms ── [✕]     │
│  [Tab 1]  [Tab 2]  [Tab 3]  [Tab 4]                                                           │
│  ──────────────────────────────────────────────────────────────────────────────────────────     │
│                                                                                                │
│  (Tab content area)                                                                            │
│                                                                                                │
└────────────────────────────────────────────────────────────────────────────────────────────────┘
```

## Detail Panel — Database Query Selected

```
┌─── ═══════════════════════════════════ drag divider ═══════════════════════════════════════ ───────────────────┐
│                                                                                                               │
│  DB Query #2 ── orders table ──────────────────────────────────── Duration: 34ms ── Start: 92ms ── [✕]       │
│  [SQL]  [Parameters]  [Explain Plan]  [Stack Trace]                                               ▲ active   │
│  ─────────────────────────────────────────────────────────────────────────────────────────────────────────     │
│                                                                                                               │
│   1 │ SELECT o.id, o.product_id, o.quantity, o.total_price, o.status,                                        │
│   2 │        p.name AS product_name, p.sku                                                                    │
│   3 │ FROM orders o                                                                                           │
│   4 │ INNER JOIN products p ON p.id = o.product_id                                                            │
│   5 │ WHERE o.user_id = :user_id                                                                              │
│   6 │   AND o.created_at >= :since                                                                            │
│   7 │   AND o.status IN (:status_1, :status_2)                                                                │
│   8 │ ORDER BY o.created_at DESC                                                                              │
│   9 │ LIMIT 25                                                                                                │
│                                                                                                               │
│  ┌─ Summary ──────────────────────────────────────────────────────────────────────────────────────────────┐   │
│  │ Connection: default (mysql)  │  Rows: 15  │  Cached: No  │  Transaction: #tx-4a2  │  Isolation: RR   │   │
│  └──────────────────────────────────────────────────────────────────────────────────────────────────────  ┘   │
│                                                                                                               │
└───────────────────────────────────────────────────────────────────────────────────────────────────────────────┘
```

### Parameters Tab

```
│  DB Query #2 ── orders table ──────────────────────────────────── Duration: 34ms ── Start: 92ms ── [✕]       │
│  [SQL]  [*Parameters*]  [Explain Plan]  [Stack Trace]                                                         │
│  ─────────────────────────────────────────────────────────────────────────────────────────────────────────     │
│                                                                                                               │
│  ┌──────────────────┬──────────┬──────────────────────────────────────────────────────────────────────────┐   │
│  │ Parameter        │ Type     │ Value                                                                    │   │
│  ├──────────────────┼──────────┼──────────────────────────────────────────────────────────────────────────┤   │
│  │ :user_id         │ int      │ 42                                                                       │   │
│  │ :since           │ string   │ "2026-01-01 00:00:00"                                                    │   │
│  │ :status_1        │ string   │ "completed"                                                              │   │
│  │ :status_2        │ string   │ "shipped"                                                                │   │
│  └──────────────────┴──────────┴──────────────────────────────────────────────────────────────────────────┘   │
│                                                                                                               │
│  Interpolated query:   [Copy to clipboard]                                                                    │
│  SELECT o.id, ... FROM orders o INNER JOIN products p ON p.id = o.product_id                                  │
│  WHERE o.user_id = 42 AND o.created_at >= '2026-01-01 00:00:00' AND o.status IN ('completed', 'shipped')     │
│  ORDER BY o.created_at DESC LIMIT 25                                                                          │
│                                                                                                               │
└───────────────────────────────────────────────────────────────────────────────────────────────────────────────┘
```

### Stack Trace Tab

```
│  DB Query #2 ── orders table ──────────────────────────────────── Duration: 34ms ── Start: 92ms ── [✕]       │
│  [SQL]  [Parameters]  [Explain Plan]  [*Stack Trace*]                                                         │
│  ─────────────────────────────────────────────────────────────────────────────────────────────────────────     │
│                                                                                                               │
│   #0  App\Repository\OrderRepository::findByUser()                    src/Repository/OrderRepository.php:47   │
│   #1  App\Service\OrderService::getUserOrders()                       src/Service/OrderService.php:83          │
│   #2  App\Controller\UserController::show()                           src/Controller/UserController.php:34     │
│   #3  Yiisoft\Router\Middleware\Router::process()                     vendor/yiisoft/router/src/Router.php:92  │
│   #4  Yiisoft\Middleware\Dispatcher::handle()                         vendor/.../Dispatcher.php:45             │
│       ... 8 more vendor frames (click to expand)                                                              │
│                                                                                                               │
└───────────────────────────────────────────────────────────────────────────────────────────────────────────────┘
```

## Detail Panel — HTTP Client Request Selected

```
┌─── ═══════════════════════════════════ drag divider ═══════════════════════════════════════ ───────────────────┐
│                                                                                                               │
│  HTTP Request ── GET https://api.stripe.com/v1/charges ─────────── Duration: 52ms ── Start: 108ms ── [✕]     │
│  [Request]  [Response]  [Timeline]  [Stack Trace]                                                             │
│  ─────────────────────────────────────────────────────────────────────────────────────────────────────────     │
│                                                                                                               │
│  ┌─ Request ──────────────────────────────────┐  ┌─ Response ─────────────────────────────────────────────┐   │
│  │ GET /v1/charges?customer=cus_abc123 HTTP/1.1│  │ HTTP/1.1 200 OK                                       │   │
│  │ Host: api.stripe.com                        │  │ Content-Type: application/json                         │   │
│  │ Authorization: Bearer sk_test_***...***xyz  │  │ Request-Id: req_8f3a2b                                 │   │
│  │ Content-Type: application/json              │  │                                                        │   │
│  │ Idempotency-Key: idk_9e2f1a                 │  │ {                                                      │   │
│  │                                             │  │   "object": "list",                                    │   │
│  │ (no body)                                   │  │   "data": [ ... 3 charges ... ],                       │   │
│  │                                             │  │   "has_more": false                                    │   │
│  └─────────────────────────────────────────────┘  └────────────────────────────────────────────────────────┘   │
│                                                                                                               │
│  Timing breakdown:  DNS: 2ms  │  Connect: 8ms  │  TLS: 12ms  │  TTFB: 24ms  │  Transfer: 6ms                │
│                                                                                                               │
└───────────────────────────────────────────────────────────────────────────────────────────────────────────────┘
```

## Detail Panel — Event Selected

```
┌─── ═══════════════════════════════════ drag divider ═══════════════════════════════════════ ───────────────────┐
│                                                                                                               │
│  Event ── App\Event\OrderCreated ──────────────────────────────── Duration: 18ms ── Start: 156ms ── [✕]      │
│  [Listeners]  [Event Data]  [Stack Trace]                                                                     │
│  ─────────────────────────────────────────────────────────────────────────────────────────────────────────     │
│                                                                                                               │
│  ┌────┬────────────────────────────────────────────────────────┬───────────┬──────────┬───────────────────┐   │
│  │ #  │ Listener                                               │ Duration  │ Stopped? │ Result            │   │
│  ├────┼────────────────────────────────────────────────────────┼───────────┼──────────┼───────────────────┤   │
│  │ 1  │ App\Listener\SendOrderConfirmation::handle()           │    8ms    │    No    │ void              │   │
│  │ 2  │ App\Listener\UpdateInventory::handle()                 │    6ms    │    No    │ void              │   │
│  │ 3  │ App\Listener\NotifyWarehouse::handle()                 │    4ms    │    No    │ void              │   │
│  └────┴────────────────────────────────────────────────────────┴───────────┴──────────┴───────────────────┘   │
│                                                                                                               │
│  Propagation: Not stopped  │  Total listeners: 3                                                              │
│                                                                                                               │
└───────────────────────────────────────────────────────────────────────────────────────────────────────────────┘
```

## Detail Panel — Middleware Selected

```
┌─── ═══════════════════════════════════ drag divider ═══════════════════════════════════════ ───────────────────┐
│                                                                                                               │
│  Middleware ── App\Middleware\AuthMiddleware ───────────────────── Duration: 42ms ── Start: 4ms ── [✕]        │
│  [Overview]  [Request In]  [Response Out]  [Stack Trace]                                                      │
│  ─────────────────────────────────────────────────────────────────────────────────────────────────────────     │
│                                                                                                               │
│  Class:     App\Middleware\AuthMiddleware                                                                      │
│  Method:    process(ServerRequestInterface, RequestHandlerInterface)                                           │
│  Position:  1 of 5 in middleware stack                                                                         │
│  Result:    Passed to next handler (did not short-circuit)                                                     │
│                                                                                                               │
│  ┌─ Attributes Added ────────────────────────────────────────────────────────────────────────────────────┐    │
│  │ auth.user_id     │ 42                                                                                 │    │
│  │ auth.roles       │ ["admin", "user"]                                                                  │    │
│  │ auth.token_exp   │ 2026-03-15T15:32:07+00:00                                                         │    │
│  └───────────────────────────────────────────────────────────────────────────────────────────────────────┘    │
│                                                                                                               │
└───────────────────────────────────────────────────────────────────────────────────────────────────────────────┘
```

## Detail Panel — Log Entry Selected

```
┌─── ═══════════════════════════════════ drag divider ═══════════════════════════════════════ ───────────────────┐
│                                                                                                               │
│  Log Entry ── Warning ── OrderService ─────────────────────────── Time: 142ms ── [✕]                         │
│  [Message]  [Context]  [Stack Trace]                                                                          │
│  ─────────────────────────────────────────────────────────────────────────────────────────────────────────     │
│                                                                                                               │
│  Level:     ▲ WARNING                                                                                         │
│  Channel:   app.service                                                                                       │
│  Message:   "Inventory low for product SKU-7842: 3 remaining (threshold: 5)"                                  │
│                                                                                                               │
│  Context:                                                                                                     │
│  ┌─────────────────────────────────────────────────────────────────────────────────────────────────────────┐   │
│  │  {                                                                                                     │   │
│  │    "product_id": 7842,                                                                                 │   │
│  │    "sku": "SKU-7842",                                                                                  │   │
│  │    "current_stock": 3,                                                                                 │   │
│  │    "threshold": 5,                                                                                     │   │
│  │    "warehouse": "east-1"                                                                               │   │
│  │  }                                                                                                     │   │
│  └─────────────────────────────────────────────────────────────────────────────────────────────────────────┘   │
│                                                                                                               │
└───────────────────────────────────────────────────────────────────────────────────────────────────────────────┘
```

## Detail Panel Behavior

```
┌────────────────────┬──────────────────────────────────────────────────────────────────────────────────────────┐
│ Behavior           │ Description                                                                             │
├────────────────────┼──────────────────────────────────────────────────────────────────────────────────────────┤
│ Open               │ Click any timeline bar                                                                  │
│ Close              │ Click [✕] button, press Escape, or click same bar again                                 │
│ Resize             │ Drag the divider between timeline and detail panel                                      │
│ Switch segment     │ Click a different bar — panel content updates, stays open                                │
│ Persist height     │ Panel height remembered in localStorage                                                  │
│ Tab persistence    │ Last-used tab per collector type remembered                                              │
│ Keyboard nav       │ Tab/Shift+Tab cycles through detail panel tabs                                          │
│ Copy support       │ All code blocks have copy-to-clipboard button                                           │
│ Fullscreen         │ Double-click detail panel header to expand to full height                                │
└────────────────────┴──────────────────────────────────────────────────────────────────────────────────────────┘
```
