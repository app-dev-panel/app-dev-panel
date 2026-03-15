# Variant A: Command Center — Debug Main Page

## Full Layout

```
┌──────────────────────────────────────────────────────────────────────────────────────────────────────────────────────┐
│  GET  │ 200 │ /api/users?page=2&limit=25 │ 87ms │ 4.1 MB │ 2026-03-15 14:23:07 │  ◀ ▶  │ ⊞ Compare │  ⌘K       │
├────┬──┴─────┴────────────────────────────┴──────┴────────┴─────────────────────┴───────┴───────────┴──────────────┤
│    │ Request │ Response │ Logs (5) │ Events (31) │ DB (8) │ Exceptions │ Mail │ Service │ Profiling │ ··· ▾       │
│ 🔍 ├─────────────────────────────────────────────────────────────────────────────────────────────────────────────────┤
│    │                                                                                                               │
│ 📋 │  ┌─ Entry Selector ────────────────────────────────────────┐                                                  │
│    │  │ ▾ GET 200 /api/users?page=2&limit=25  87ms  14:23:07   │                                                  │
│ 🔧 │  └────────────────────────────────────────────────────────┘                                                  │
│    │                                                                                                               │
│ 📊 │  ┌─ Request Info ──────────────────────────────────────────────────────────────────────────────────────────┐   │
│    │  │                                                                                                        │   │
│ 📁 │  │  General                                                                                               │   │
│    │  │  ─────────────────────────────────────────────────────────────────                                      │   │
│ 🛠  │  │  Request URL      https://myapp.local/api/users?page=2&limit=25                                       │   │
│    │  │  Request Method    GET                                                                                 │   │
│    │  │  Status Code       200 OK                                                                              │   │
│    │  │  Remote Address    127.0.0.1:8080                                                                      │   │
│    │  │                                                                                                        │   │
│    │  │  Request Headers                                                                                       │   │
│    │  │  ─────────────────────────────────────────────────────────────────                                      │   │
│    │  │  Accept            application/json                                                                    │   │
│    │  │  Authorization     Bearer eyJhbGci...                                                                  │   │
│    │  │  Content-Type      application/json                                                                    │   │
│    │  │  User-Agent        Mozilla/5.0 (X11; Linux x86_64)                                                     │   │
│    │  │  X-Request-Id      f47ac10b-58cc-4372-a567-0e02b2c3d479                                                │   │
│    │  │                                                                                                        │   │
│    │  │  Query Parameters                                                                                      │   │
│    │  │  ─────────────────────────────────────────────────────────────────                                      │   │
│    │  │  page              2                                                                                   │   │
│    │  │  limit             25                                                                                  │   │
│    │  │                                                                                                        │   │
│    │  └────────────────────────────────────────────────────────────────────────────────────────────────────────┘   │
│    │                                                                                                               │
├────┴─────────────────────────────────────────────────────────────────────────────────────────────────────────────────┤
│  GET /api/debug/view/f47ac10b -> 200 OK (32ms)                             ● SSE Connected │ ADP v1.2.0 │  ⚙     │
└──────────────────────────────────────────────────────────────────────────────────────────────────────────────────────┘
```

## Entry Selector Dropdown (expanded)

Clicking the entry selector expands a dropdown with recent entries:

```
  ┌─ Entry Selector ────────────────────────────────────────────────────────────────────────────┐
  │ ▾ GET 200 /api/users?page=2&limit=25  87ms  14:23:07                                       │
  ├────────────────────────────────────────────────────────────────────────────────────────────────┤
  │  🔍 Filter entries...                                                                       │
  ├────────────────────────────────────────────────────────────────────────────────────────────────┤
  │  ● GET   200  /api/users?page=2&limit=25          87ms   14:23:07   <- selected            │
  │    POST  201  /api/users                          145ms   14:22:58                           │
  │    GET   200  /api/users/42/profile                52ms   14:22:41                           │
  │    GET   404  /api/users/999                       23ms   14:22:30                           │
  │    POST  500  /api/auth/refresh                   340ms   14:22:15                           │
  │    CLI     0  app:import-users --force           2340ms   14:20:00                           │
  │    GET   200  /api/products?category=electronics   98ms   14:19:45                           │
  │    GET   301  /old-path                            12ms   14:19:30                           │
  ├────────────────────────────────────────────────────────────────────────────────────────────────┤
  │  View all entries ->                                                                        │
  └────────────────────────────────────────────────────────────────────────────────────────────────┘
```

## Collector Tab: Logs

```
┌─ Logs (5 entries) ─────────────────────────────────────────────────────────────────────────────────────────────┐
│                                                                                                               │
│  ┌─ Toolbar ─────────────────────────────────────────────────────────────────────────────────────────────┐     │
│  │  Level: [All ▾]   Category: [All ▾]   Search: [________________]   │ ☰ Compact │ ☰ Full │  ⤓ Export │     │
│  └───────────────────────────────────────────────────────────────────────────────────────────────────────┘     │
│                                                                                                               │
│  ┌─────────┬──────────────┬──────────────────────────────────────────────────────────────┬──────────────┐     │
│  │ Level   │ Category     │ Message                                                      │ Time         │     │
│  ├─────────┼──────────────┼──────────────────────────────────────────────────────────────┼──────────────┤     │
│  │ INFO    │ app          │ User query: page=2, limit=25                                 │ 14:23:07.012 │     │
│  │ DEBUG   │ db           │ SELECT * FROM users LIMIT 25 OFFSET 25                       │ 14:23:07.015 │     │
│  │ INFO    │ app          │ Found 25 users (total: 142)                                  │ 14:23:07.034 │     │
│  │ DEBUG   │ cache        │ Cache HIT: user_count_total                                  │ 14:23:07.035 │     │
│  │ WARNING │ security     │ Rate limit approaching for IP 192.168.1.100 (85/100)         │ 14:23:07.036 │     │
│  └─────────┴──────────────┴──────────────────────────────────────────────────────────────┴──────────────┘     │
│                                                                                                               │
└───────────────────────────────────────────────────────────────────────────────────────────────────────────────┘
```

## Collector Tab: DB Queries

```
┌─ Database Queries (8 queries, 34ms total) ─────────────────────────────────────────────────────────────────────┐
│                                                                                                               │
│  ┌─ Toolbar ─────────────────────────────────────────────────────────────────────────────────────────────┐     │
│  │  Type: [All ▾]   Min time: [___]ms   Search: [________________]   │ Density: ☰ │ ☰ │  ⤓ Export      │     │
│  └───────────────────────────────────────────────────────────────────────────────────────────────────────┘     │
│                                                                                                               │
│  ┌────┬────────┬───────────────────────────────────────────────────────────────────┬──────────┬───────────┐   │
│  │ #  │ Type   │ Query                                                             │ Time     │ Rows      │   │
│  ├────┼────────┼───────────────────────────────────────────────────────────────────┼──────────┼───────────┤   │
│  │ 1  │ SELECT │ SELECT * FROM "user" WHERE "active" = 1 ORDER BY "created_at"    │   12ms   │ 25        │   │
│  │    │        │ DESC LIMIT 25 OFFSET 25                                          │          │           │   │
│  │ 2  │ SELECT │ SELECT COUNT(*) FROM "user" WHERE "active" = 1                   │    3ms   │ 1         │   │
│  │ 3  │ SELECT │ SELECT "r"."name" FROM "role" INNER JOIN "user_role" ON          │    8ms   │ 25        │   │
│  │    │        │ "r"."id" = "ur"."role_id" WHERE "ur"."user_id" IN (...)          │          │           │   │
│  │ 4  │ SELECT │ SELECT "key", "value" FROM "setting" WHERE "scope" = 'pagination'│    2ms   │ 3         │   │
│  │ 5  │ SELECT │ SELECT * FROM "cache_item" WHERE "key" = 'user_count_total'      │    1ms   │ 1         │   │
│  │ 6  │ UPDATE │ UPDATE "request_log" SET "count" = "count" + 1 WHERE "ip" =      │    4ms ● │ 1         │   │
│  │    │        │ '192.168.1.100'                                                  │          │           │   │
│  │ 7  │ SELECT │ SELECT "count" FROM "rate_limit" WHERE "ip" = '192.168.1.100'    │    2ms   │ 1         │   │
│  │ 8  │ INSERT │ INSERT INTO "audit_log" ("action", "user_id", "timestamp")       │    2ms   │ 1         │   │
│  │    │        │ VALUES ('list_users', 42, '2026-03-15 14:23:07')                 │          │           │   │
│  └────┴────────┴───────────────────────────────────────────────────────────────────┴──────────┴───────────┘   │
│                                                                                                               │
│  ● = slow query (> threshold)                                                                                 │
│                                                                                                               │
│  Timeline:  |==SELECT====|=S=|===SELECT=====|=S|S|==UPDATE==|=S|=I=|                                         │
│             0ms         12ms 15ms          23ms                  34ms                                          │
│                                                                                                               │
└───────────────────────────────────────────────────────────────────────────────────────────────────────────────┘
```

## Collector Tab: Events

```
┌─ Events (31 dispatched) ───────────────────────────────────────────────────────────────────────────────────────┐
│                                                                                                               │
│  ┌─────┬──────────────────────────────────────────────┬────────────────────────────────────────┬──────────┐   │
│  │ #   │ Event                                         │ Listeners                              │ Time     │   │
│  ├─────┼──────────────────────────────────────────────┼────────────────────────────────────────┼──────────┤   │
│  │ 1   │ Router\BeforeRoute                            │ 2 listeners                            │ 0.1ms    │   │
│  │ 2   │ Middleware\BeforeMiddleware                    │ 1 listener                             │ 0.0ms    │   │
│  │ 3   │ Auth\TokenValidated                           │ 3 listeners                            │ 1.2ms    │   │
│  │ 4   │ Controller\BeforeAction                       │ 1 listener                             │ 00ms     │   │
│  │ ... │                                               │                                        │          │   │
│  │ 31  │ Response\AfterSend                            │ 2 listeners                            │ 0.1ms    │   │
│  └─────┴──────────────────────────────────────────────┴────────────────────────────────────────┴──────────┘   │
│                                                                                                               │
└───────────────────────────────────────────────────────────────────────────────────────────────────────────────┘
```

## Collector Tab: Exceptions (with error)

```
┌─ Exceptions (1) ───────────────────────────────────────────────────────────────────────────────────────────────┐
│                                                                                                               │
│  ┌─ RuntimeException ──────────────────────────────────────────────────────────────────────────────────────┐   │
│  │                                                                                                        │   │
│  │  Message:  User not found with ID 999                                                                  │   │
│  │  Code:     404                                                                                         │   │
│  │  File:     /app/src/User/UserRepository.php:87                                                         │   │
│  │                                                                                                        │   │
│  │  Stack Trace                                                                                           │   │
│  │  ────────────────────────────────────────────────────────────────────────                               │   │
│  │  #0  /app/src/User/UserRepository.php:87       UserRepository->findOrFail(999)                         │   │
│  │  #1  /app/src/User/UserController.php:42       UserController->show($request)                          │   │
│  │  #2  /vendor/yiisoft/router/src/Router.php:128 Router->dispatch($request)                              │   │
│  │  #3  /vendor/yiisoft/middleware/Pipeline.php:56 Pipeline->handle($request)                              │   │
│  │  #4  /app/public/index.php:23                  {main}                                                  │   │
│  │                                                                                                        │   │
│  └────────────────────────────────────────────────────────────────────────────────────────────────────────┘   │
│                                                                                                               │
└───────────────────────────────────────────────────────────────────────────────────────────────────────────────┘
```

## Collector Tab: Response

```
┌─ Response ─────────────────────────────────────────────────────────────────────────────────────────────────────┐
│                                                                                                               │
│  Status        200 OK                                                                                         │
│  Content-Type  application/json                                                                               │
│  Size          3.2 KB                                                                                         │
│                                                                                                               │
│  Response Headers                                                                                             │
│  ─────────────────────────────────────────────────────────────────                                            │
│  Content-Type       application/json; charset=utf-8                                                           │
│  X-Request-Id       f47ac10b-58cc-4372-a567-0e02b2c3d479                                                     │
│  Cache-Control      no-cache, private                                                                         │
│  X-Debug-Id         abc123                                                                                    │
│                                                                                                               │
│  Body (JSON, prettified)                                                                                      │
│  ─────────────────────────────────────────────────────────────────                                            │
│  ┌────────────────────────────────────────────────────────────────────────────────────────────────────────┐   │
│  │  1  {                                                                                                 │   │
│  │  2    "data": [                                                                                       │   │
│  │  3      {                                                                                             │   │
│  │  4        "id": 26,                                                                                   │   │
│  │  5        "name": "Alice Johnson",                                                                    │   │
│  │  6        "email": "alice@example.com",                                                               │   │
│  │  7        "role": "admin",                                                                            │   │
│  │  8        "created_at": "2026-01-15T10:30:00Z"                                                        │   │
│  │  9      },                                                                                            │   │
│  │ 10      ...                                                                                           │   │
│  │ 11    ],                                                                                              │   │
│  │ 12    "meta": {                                                                                       │   │
│  │ 13      "total": 142,                                                                                 │   │
│  │ 14      "page": 2,                                                                                    │   │
│  │ 15      "limit": 25                                                                                   │   │
│  │ 16    }                                                                                               │   │
│  │ 17  }                                                                                                 │   │
│  └────────────────────────────────────────────────────────────────────────────────────────────────────────┘   │
│                                                                                                               │
└───────────────────────────────────────────────────────────────────────────────────────────────────────────────┘
```

## Interaction Notes

- Entry selector dropdown: opens on click, closes on blur or Escape
- Entry selector search: filters by URL, method, status code
- Collector tabs: click to switch, keyboard 1-9 to jump directly
- Tabs with counts show badges; badge turns red if errors exist in that collector
- DB query rows: click to expand full query with syntax highlighting and explain plan
- Exception stack trace: click file path to copy, or (if source available) expand inline source
- Log messages: click to expand context array

## State Management

| State                  | Storage    | Rationale                              |
|------------------------|-----------|----------------------------------------|
| Selected entry ID      | URL param | `?id=abc123` — bookmarkable            |
| Active collector tab   | URL param | `?tab=db` — bookmarkable               |
| Entry selector open    | Local     | Transient UI state                     |
| Entry selector filter  | Local     | Transient, not worth persisting        |
| Log level filter       | URL param | `?logLevel=warning` — shareable        |
| DB query expanded row  | Local     | Transient UI detail                    |
| Recent entries list    | Redux     | Fetched from API, cached               |
| Current entry data     | Redux     | Fetched from API for selected entry    |
