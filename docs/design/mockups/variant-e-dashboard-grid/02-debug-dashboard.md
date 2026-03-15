# Variant E: Dashboard Grid — Debug Dashboard

## Overview

The Debug dashboard is the default view when inspecting a collected request entry. It shows request
metadata, logs, database queries, timeline, events, and response data as a grid of widgets.

## Default Debug Layout (4-Column Arrangement)

```
┌────────────────────────────────────────────────────────────────────────────────────────────────────────────────┐
│  ADP   ◀ ▶  GET /api/users ▾  #a3f7c1  2026-03-15 14:32:07          200 OK 145ms         + ✎ ⚙              │
│  ┌─────────┐ ┌───────────┐ ┌───────────┐ ┌───────────┐                                                       │
│  │ Debug   │ │ Inspector │ │ Perf      │ │ Custom 1  │  +                                                     │
│  │ ▀▀▀▀▀▀▀ │ │           │ │           │ │           │                                                       │
├────────────────────────────────────────────────────────────────────────────────────────────────────────────────┤
│                                                                                                                │
│  ┌══ Request Info ═══════════════════════ ─ □ ✕ ┐  ┌══ Response ═══════════════════════ ─ □ ✕ ┐               │
│  │ Method:   GET                               │  │ Status:   200 OK                        │               │
│  │ URL:      /api/users                        │  │ Format:   application/json               │               │
│  │ Route:    api/users                         │  │ Size:     2.4 KB                         │               │
│  │ Handler:  UserController::list              │  │ Headers:  7                               │               │
│  │ Time:     145ms                             │  │                                          │               │
│  │ Memory:   12.4 MB                           │  │ Body Preview:                            │               │
│  │ PHP:      8.4.5                             │  │ {"users": [{"id": 1, "name": ...}]}     │               │
│  └─────────────────────────────────────────────┘  └──────────────────────────────────────────┘               │
│                                                                                                                │
│  ┌══ Timeline ═══════════════════════════════════════════════════════════════════════════ ─ □ ✕ ┐              │
│  │                                                                                             │              │
│  │  0ms          25ms          50ms          75ms         100ms         125ms        145ms      │              │
│  │  ├─────────────┼─────────────┼─────────────┼─────────────┼─────────────┼───────────┤        │              │
│  │  ██████████░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░ Total    │              │
│  │  ████░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░ Boot     │              │
│  │  ░░░░░░████████████░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░ Route    │              │
│  │  ░░░░░░░░░░░░░░░░░░████████████████████████████░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░ Action   │              │
│  │  ░░░░░░░░░░░░░░░░░░░░░░░░░░████████████░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░ DB       │              │
│  │  ░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░██████░░░░░ View     │              │
│  │                                                                                             │              │
│  └─────────────────────────────────────────────────────────────────────────────────────────────┘              │
│                                                                                                                │
│  ┌══ DB Queries (8) ══════════════════════════════════ ─ □ ✕ ┐  ┌══ Logs (23) ═══════════════ ─ □ ✕ ┐       │
│  │                                                           │  │                                     │       │
│  │  #   Query                        Time     Rows           │  │ 14:32:07.012  INFO   App booted     │       │
│  │  ─── ────────────────────────     ──────   ─────          │  │ 14:32:07.034  DEBUG  Route matched  │       │
│  │  1   SELECT * FROM "user"         12.3ms   42             │  │ 14:32:07.035  INFO   Action start   │       │
│  │      WHERE "active" = 1                                   │  │ 14:32:07.041  DEBUG  DB connect     │       │
│  │  2   SELECT COUNT(*) FROM         3.1ms    1              │  │ 14:32:07.053  DEBUG  Query: SELECT  │       │
│  │      "user" WHERE "active" = 1                            │  │ 14:32:07.066  DEBUG  Query: SELECT  │       │
│  │  3   SELECT * FROM "role"         8.7ms    5              │  │ 14:32:07.089  INFO   Cache hit      │       │
│  │      WHERE "id" IN (1, 2, 3)                              │  │ 14:32:07.092  DEBUG  Query: SELECT  │       │
│  │  4   SELECT * FROM "permission"   5.2ms    12             │  │ 14:32:07.110  INFO   Serializing    │       │
│  │      WHERE "role_id" IN (1,2)                             │  │ 14:32:07.142  DEBUG  Response sent  │       │
│  │  5   SELECT * FROM "session"      2.1ms    1              │  │ 14:32:07.144  INFO   Request done   │       │
│  │      WHERE "user_id" = 1                                  │  │                                     │       │
│  │                                                           │  │ ─ INFO: 5  ─ DEBUG: 14  ─ WARN: 3  │       │
│  │  ▸ Showing 5 of 8    Total: 42.8ms                        │  │ ─ ERROR: 1                          │       │
│  │                                                           │  │                                     │       │
│  └───────────────────────────────────────────────────────────┘  └─────────────────────────────────────┘       │
│                                                                                                                │
│  ┌══ Events (12) ════════════════════════════════════════════════════════════════════════ ─ □ ✕ ┐              │
│  │                                                                                              │              │
│  │  #    Event Class                               Listeners    Time       Stopped              │              │
│  │  ──── ──────────────────────────────────────    ──────────   ────────   ────────             │              │
│  │  1    Application\Event\BeforeRequest           3            0.8ms      No                   │              │
│  │  2    Router\Event\RouteMatched                 2            0.3ms      No                   │              │
│  │  3    Controller\Event\BeforeAction             1            0.1ms      No                   │              │
│  │  4    Database\Event\QueryExecuted              1            0.2ms      No                   │              │
│  │  5    Database\Event\QueryExecuted              1            0.1ms      No                   │              │
│  │  6    Cache\Event\CacheHit                      1            0.1ms      No                   │              │
│  │                                                                                              │              │
│  │  ▸ Showing 6 of 12                                                                           │              │
│  └──────────────────────────────────────────────────────────────────────────────────────────────┘              │
│                                                                                                                │
└────────────────────────────────────────────────────────────────────────────────────────────────────────────────┘
```

## Grid Positions (12-Column Grid)

```
  Col:  1    2    3    4    5    6    7    8    9    10   11   12
  Row 1 ┌─── Request Info (6 cols) ───┬─── Response (6 cols) ─────┐
        │                             │                            │
  Row 2 └─────────────────────────────┴────────────────────────────┘
  Row 3 ┌─── Timeline (12 cols, full width) ───────────────────────┐
        │                                                          │
  Row 4 └─────────────────────────────────────────────────────────┘
  Row 5 ┌─── DB Queries (7 cols) ────┬─── Logs (5 cols) ──────────┐
        │                            │                             │
        │                            │                             │
  Row 8 └────────────────────────────┴─────────────────────────────┘
  Row 9 ┌─── Events (12 cols, full width) ─────────────────────────┐
        │                                                          │
  Row 10└──────────────────────────────────────────────────────────┘
```

## Compact Debug Layout (Minimized Widgets)

When screen space is tight, widgets can be minimized to title-bar-only strips:

```
┌────────────────────────────────────────────────────────────────────────────────────────────────────────────────┐
│  ADP   ◀ ▶  GET /api/users ▾  #a3f7c1                               200 OK 145ms         + ✎ ⚙              │
│  ┌─────────┐ ┌───────────┐                                                                                   │
│  │ Debug   │ │ Inspector │  +                                                                                 │
│  │ ▀▀▀▀▀▀▀ │ │           │                                                                                   │
├────────────────────────────────────────────────────────────────────────────────────────────────────────────────┤
│                                                                                                                │
│  ┌══ Request Info ══════════════════════════════════════════════════════════════════════ ─ □ ✕ ┐ (minimized)   │
│  ├══ Response ══════════════════════════════════════════════════════════════════════════ ─ □ ✕ ┤ (minimized)   │
│  ├══ Timeline ══════════════════════════════════════════════════════════════════════════ ─ □ ✕ ┤ (minimized)   │
│                                                                                                                │
│  ┌══ DB Queries (8) ══════════════════════════════════════════════════════════════════ ─ □ ✕ ──┐              │
│  │                                                                                             │              │
│  │  #   Query                                              Time      Rows    Status            │              │
│  │  ─── ──────────────────────────────────────────────     ───────   ─────   ───────           │              │
│  │  1   SELECT * FROM "user" WHERE "active" = 1           12.3ms    42      OK                 │              │
│  │  2   SELECT COUNT(*) FROM "user" WHERE "active" = 1    3.1ms     1       OK                 │              │
│  │  3   SELECT * FROM "role" WHERE "id" IN (1, 2, 3)      8.7ms     5       OK                 │              │
│  │  4   SELECT * FROM "permission" WHERE "role_id"...      5.2ms     12      OK                 │              │
│  │  5   SELECT * FROM "session" WHERE "user_id" = 1       2.1ms     1       OK                 │              │
│  │  6   SELECT * FROM "user_profile" WHERE "user_id"...   4.5ms     42      OK                 │              │
│  │  7   SELECT * FROM "setting" WHERE "key" = 'pag...'    1.2ms     1       OK                 │              │
│  │  8   INSERT INTO "audit_log" VALUES (...)               5.7ms     1       OK                 │              │
│  │                                                                                             │              │
│  │  Total: 8 queries, 42.8ms, 105 rows                                                        │              │
│  └─────────────────────────────────────────────────────────────────────────────────────────────┘              │
│                                                                                                                │
│  ┌══ Logs (23) ══════════════════════════════════════════════════════════════════════ ─ □ ✕ ────┐              │
│  │  ...expanded log widget...                                                                   │              │
│  └──────────────────────────────────────────────────────────────────────────────────────────────┘              │
│                                                                                                                │
└────────────────────────────────────────────────────────────────────────────────────────────────────────────────┘
```

## Error State: Failed Request (500)

```
┌────────────────────────────────────────────────────────────────────────────────────────────────────────────────┐
│  ADP   ◀ ▶  GET /api/products ▾  #b2e4a8  2026-03-15 14:31:10       500 Error 1024ms     + ✎ ⚙              │
│  ┌─────────┐ ┌───────────┐                                                                                   │
│  │ Debug   │ │ Inspector │  +                                                                                 │
│  │ ▀▀▀▀▀▀▀ │ │           │                                                                                   │
├────────────────────────────────────────────────────────────────────────────────────────────────────────────────┤
│                                                                                                                │
│  ┌══ Request Info ════════════════════════ ─ □ ✕ ┐  ┌══ Exception ═══════════════════════════ ─ □ ✕ ┐        │
│  │ Method:   GET                                │  │                                               │        │
│  │ URL:      /api/products                      │  │  RuntimeException                             │        │
│  │ Route:    api/products                       │  │  "Database connection timed out"               │        │
│  │ Handler:  ProductController::index           │  │                                               │        │
│  │ Time:     1024ms                             │  │  in src/Repository/ProductRepo.php:42         │        │
│  │ Memory:   34.2 MB                            │  │                                               │        │
│  │ PHP:      8.4.5                              │  │  Stack Trace:                                 │        │
│  └──────────────────────────────────────────────┘  │  #0 src/Repository/ProductRepo.php:42         │        │
│                                                     │  #1 src/Controller/ProductController.php:28  │        │
│  ┌══ DB Queries (3) ═════════════ ─ □ ✕ ┐          │  #2 vendor/framework/Router.php:156           │        │
│  │                                      │          │  #3 vendor/framework/Application.php:89       │        │
│  │  #  Query              Time   Status │          │  #4 public/index.php:12                       │        │
│  │  ── ────────────────   ─────  ────── │          │                                               │        │
│  │  1  SELECT * FROM...   8.2ms  OK     │          │  Previous: PDOException                       │        │
│  │  2  SELECT * FROM...   5.1ms  OK     │          │  "SQLSTATE[HY000] Connection refused"         │        │
│  │  3  SELECT * FROM...   FAIL   ERROR  │          │                                               │        │
│  │                                      │          └───────────────────────────────────────────────┘        │
│  │  Total: 3 queries, 1 failed          │                                                                    │
│  └──────────────────────────────────────┘                                                                    │
│                                                                                                                │
│  ┌══ Logs (15) ════════════════════════════════════════════════════════════════════════── ─ □ ✕ ┐              │
│  │ 14:31:10.012  INFO    App booted                                                            │              │
│  │ 14:31:10.034  DEBUG   Route matched: api/products                                           │              │
│  │ 14:31:10.045  DEBUG   DB connect attempt                                                    │              │
│  │ 14:31:10.856  ERROR   DB connection timeout after 800ms                                     │              │
│  │ 14:31:10.857  ERROR   RuntimeException: Database connection timed out                       │              │
│  │ 14:31:11.032  WARN    Error handler caught exception                                        │              │
│  │ 14:31:11.034  INFO    Returning 500 response                                                │              │
│  └─────────────────────────────────────────────────────────────────────────────────────────────┘              │
│                                                                                                                │
└────────────────────────────────────────────────────────────────────────────────────────────────────────────────┘
```
