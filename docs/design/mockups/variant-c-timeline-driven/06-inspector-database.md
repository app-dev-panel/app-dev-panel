# Variant C: Timeline-Driven — Inspector: Database Queries on Timeline

## Database Timeline View

All database queries from the selected debug entry are shown on a dedicated timeline, with a query
detail panel below. This view is accessed from the icon rail (DB icon) or by filtering the main
timeline to show only database spans.

```
┌──────────────────────────────────────────────────────────────────────────────────────────────────────────────────┐
│ ┌─ADP─┐  GET /api/users/42  ─  200 OK  ─  247ms  ─  ID: 6f3a9b  ─  Database: 5 queries, 94ms total           │
├────┬───┴─────────────────────────────────────────────────────────────────────────────────────────────────────────┤
│    │                                                                                                            │
│    │  ┌─ Summary Bar ────────────────────────────────────────────────────────────────────────────────────────┐  │
│    │  │  Queries: 5  │  Total: 94ms (38% of request)  │  Connections: 2  │  Slowest: 34ms  │  N+1: None    │  │
│    │  └──────────────────────────────────────────────────────────────────────────────────────────────────────┘  │
│ ┌──┤                                                                                                            │
│ │🔍│  ┌─ Request Timeline (dimmed) ────────────────────────────────────────────────────────────────────────┐   │
│ │  │  │  0ms       50ms       100ms      150ms      200ms      247ms                                      │   │
│ │📋│  │  ├──────────┼──────────┼──────────┼──────────┼──────────┤                                          │   │
│ │  │  │  ░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░  (dimmed request span)        │   │
│ │⏱ │  └────────────────────────────────────────────────────────────────────────────────────────────────────┘   │
│ │  │                                                                                                            │
│ │📊│  ┌─ Database Timeline ────────────────────────────────────────────────────────────────────────────────┐   │
│ │  │  │  0ms       50ms       100ms      150ms      200ms      247ms                                      │   │
│ │🗄 │  │  ├──────────┼──────────┼──────────┼──────────┼──────────┤                                          │   │
│ │  │  │           │           │           │           │           │                                         │   │
│ │🔔│  │  Q1 users │     ████████████      │           │           │  SELECT * FROM users WHERE id = :id    │   │
│ │  │  │           │           │           │           │           │  12ms · 1 row · default                 │   │
│ │🌐│  │           │           │           │           │           │                                         │   │
│ │  │  │  Q2 orders│           ████████████████████████│           │  SELECT o.* FROM orders o WHERE ...     │   │
│ │⚙ │  │           │           │           │           │           │  34ms · 15 rows · default  ⚠ SLOW      │   │
│ └──┤  │           │           │           │           │           │                                         │   │
│    │  │  Q3 produc│           │     ████████████      │           │  SELECT p.name, p.sku FROM products ... │   │
│    │  │           │           │           │           │           │  18ms · 15 rows · default               │   │
│    │  │           │           │           │           │           │                                         │   │
│    │  │  Q4 invent│           │           │  ████████ │           │  SELECT stock FROM inventory WHERE ...   │   │
│    │  │           │           │           │           │           │  14ms · 15 rows · replica               │   │
│    │  │           │           │           │           │           │                                         │   │
│    │  │  Q5 config│           │           │     ██████│           │  SELECT value FROM config WHERE key ... │   │
│    │  │           │           │           │           │           │  16ms · 3 rows · default                │   │
│    │  │           │           │           │           │           │                                         │   │
│    │  │  ── Idle gaps shown as grey ────────                                                               │   │
│    │  │  │░░░░░░░░│     ▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓│░░░░░░░░░░│▓▓▓▓▓▓▓▓▓│░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░│  │   │
│    │  │  ^idle(72ms)   ^DB active(34ms)    ^idle       ^active   ^idle (non-DB work)                      │   │
│    │  └────────────────────────────────────────────────────────────────────────────────────────────────────┘   │
│    │                                                                                                            │
│    ├─── Detail ── Q2: orders ─────────────────────────────────── Duration: 34ms ── Start: 92ms ───────────────┤
│    │  [SQL]  [*Parameters*]  [Explain Plan]  [Stack Trace]                                                     │
│    │  ──────────────────────────────────────────────────────────────────────────────────────────────────────── │
│    │                                                                                                            │
│    │  ┌──────────────────┬──────────┬──────────────────────────────────────────────────────────────────────┐   │
│    │  │ Parameter        │ Type     │ Value                                                                │   │
│    │  ├──────────────────┼──────────┼──────────────────────────────────────────────────────────────────────┤   │
│    │  │ :user_id         │ int      │ 42                                                                   │   │
│    │  │ :since           │ string   │ "2026-01-01 00:00:00"                                                │   │
│    │  │ :status_1        │ string   │ "completed"                                                          │   │
│    │  │ :status_2        │ string   │ "shipped"                                                            │   │
│    │  └──────────────────┴──────────┴──────────────────────────────────────────────────────────────────────┘   │
│    │                                                                                                            │
└────┴─────────────────────────────────────────────────────────────────────────────────────────────────────────────┘
```

## Query List Overlay (Table Mode)

Toggle between timeline and table views using [Timeline | Table] switcher in the header.

```
│    │  ┌─ Query Table ── sorted by Duration (desc) ─────────────────────────────────────────────────────────┐  │
│    │  │ #  │ Start  │ Dur.  │ Conn.    │ Rows │ Query                                              │ Flags│  │
│    │  ├────┼────────┼───────┼──────────┼──────┼────────────────────────────────────────────────────┼──────┤  │
│    │  │  2 │  92ms  │ 34ms  │ default  │   15 │ SELECT o.* FROM orders o INNER JOIN products p ... │ ⚠    │  │
│    │  │  3 │ 112ms  │ 18ms  │ default  │   15 │ SELECT p.name, p.sku FROM products p WHERE p.i ... │      │  │
│    │  │  5 │ 148ms  │ 16ms  │ default  │    3 │ SELECT value FROM config WHERE key IN (:k1, :k ... │      │  │
│    │  │  4 │ 132ms  │ 14ms  │ replica  │   15 │ SELECT stock FROM inventory WHERE product_id IN... │      │  │
│    │  │  1 │  72ms  │ 12ms  │ default  │    1 │ SELECT * FROM users WHERE id = :id AND status = .. │      │  │
│    │  └────┴────────┴───────┴──────────┴──────┴────────────────────────────────────────────────────┴──────┘  │
│    │                                                                                                         │
│    │  Total: 5 queries  │  94ms DB time  │  38% of 247ms request  │  49 rows returned                        │
```

## N+1 Query Detection

When the system detects a potential N+1 pattern, a warning banner appears:

```
│    │  ┌─ ⚠ N+1 Query Pattern Detected ──────────────────────────────────────────────────────────────────┐    │
│    │  │                                                                                                  │    │
│    │  │  Pattern: SELECT * FROM order_items WHERE order_id = :id  (repeated 15 times)                    │    │
│    │  │  Total time: 45ms (15 x ~3ms each)                                                               │    │
│    │  │  Suggestion: Use eager loading or a single IN() query                                             │    │
│    │  │                                                                                                  │    │
│    │  │  0ms       50ms       100ms      150ms      200ms                                                │    │
│    │  │  ├──────────┼──────────┼──────────┼──────────┤                                                   │    │
│    │  │  │   ██ ██ ██ ██ ██ ██ ██ ██ ██ ██ ██ ██ ██ ██ ██  │  15 identical queries                      │    │
│    │  │                                                                                                  │    │
│    │  │  [Show all 15 queries]  [Dismiss]                                                                │    │
│    │  └──────────────────────────────────────────────────────────────────────────────────────────────────┘    │
```

## Explain Plan Tab

```
│    │  [SQL]  [Parameters]  [*Explain Plan*]  [Stack Trace]                                                     │
│    │  ──────────────────────────────────────────────────────────────────────────────────────────────────────── │
│    │                                                                                                            │
│    │  ┌────┬─────────┬──────────┬─────────────────────────┬──────┬─────────────┬────────────────────────────┐  │
│    │  │ id │ type    │ table    │ key                     │ rows │ filtered    │ Extra                      │  │
│    │  ├────┼─────────┼──────────┼─────────────────────────┼──────┼─────────────┼────────────────────────────┤  │
│    │  │  1 │ SIMPLE  │ o        │ idx_orders_user_id      │   15 │   100.00%   │ Using where                │  │
│    │  │  1 │ SIMPLE  │ p        │ PRIMARY                 │    1 │   100.00%   │                            │  │
│    │  └────┴─────────┴──────────┴─────────────────────────┴──────┴─────────────┴────────────────────────────┘  │
│    │                                                                                                            │
│    │  Index usage: ✓ Good — query uses idx_orders_user_id index                                                │
│    │  Scan type: Index lookup (not full table scan)                                                             │
│    │                                                                                                            │
└────┴─────────────────────────────────────────────────────────────────────────────────────────────────────────────┘
```

## Connection Summary

```
┌─ Connection Summary ─────────────────────────────────────────────────────────────────────────────────────────┐
│                                                                                                              │
│  ┌─ default (mysql) ─────────────────────┐  ┌─ replica (mysql) ──────────────────────┐                      │
│  │  Queries: 4  │  Time: 80ms            │  │  Queries: 1  │  Time: 14ms             │                      │
│  │  Rows: 34    │  Transactions: 0       │  │  Rows: 15    │  Transactions: 0        │                      │
│  │  Host: 127.0.0.1:3306                 │  │  Host: 127.0.0.1:3307                  │                      │
│  │  Database: app_dev                    │  │  Database: app_dev                     │                      │
│  └───────────────────────────────────────┘  └────────────────────────────────────────┘                      │
│                                                                                                              │
└──────────────────────────────────────────────────────────────────────────────────────────────────────────────┘
```
