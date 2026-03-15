# Variant E: Dashboard Grid — Smart Table Widget Spec

## Overview

The Smart Table is the most complex widget type. It provides sortable columns, inline filtering,
row expansion, pagination, column resizing, and export. Used by DB Queries, Events, Routes,
Container, Commands, Middleware, and HTTP Client collectors.

## Basic Table

```
┌══ DB Queries (8) ════════════════════════════════════════════════════════════════════ ─ □ ✕ ─────┐
│                                                                                                  │
│  🔍 Filter queries...                                                                           │
│                                                                                                  │
│  #    Query                                              Duration ▾   Rows     Status            │
│  ──── ──────────────────────────────────────────────     ──────────   ──────   ──────             │
│  1    SELECT * FROM "user" WHERE "active" = 1            12.3ms       42       OK                 │
│  2    SELECT * FROM "role" WHERE "id" IN (1, 2, 3)      8.7ms        5        OK                 │
│  3    INSERT INTO "audit_log" (user_id, action,...)      5.7ms        1        OK                 │
│  4    SELECT * FROM "permission" WHERE "role_id"...      5.2ms        12       OK                 │
│  5    SELECT * FROM "user_profile" WHERE "user_id"...    4.5ms        42       OK                 │
│                                                                                                  │
├──────────────────────────────────────────────────────────────────────────────────────────────────┤
│  Showing 1-5 of 8  │  Total: 42.8ms  │  105 rows                            ◀  1  2  ▶          │
└──────────────────────────────────────────────────────────────────────────────────────────────────┘
```

## Column Sorting

Click a column header to sort. First click: ascending. Second: descending. Third: reset.

```
  Unsorted:     Query
  Ascending:    Query ▴
  Descending:   Query ▾
```

Multi-column sort with Shift+click:

```
  Status ▴¹   Duration ▾²

  (Primary sort: Status ascending, Secondary: Duration descending)
```

## Column Filtering

Click the filter icon in a column header to open an inline filter:

```
┌══ Events (12) ═══════════════════════════════════════════════════════════════════════ ─ □ ✕ ─────┐
│                                                                                                  │
│  #    Event Class ▾                  Listeners    Duration    Propagation                        │
│       ┌──────────────────────────┐                                                               │
│       │ 🔍 Contains...           │                                                               │
│       │ ☑ Application\Event\*    │                                                               │
│       │ ☑ Router\Event\*         │                                                               │
│       │ ☑ Controller\Event\*     │                                                               │
│       │ ☑ Database\Event\*       │                                                               │
│       │ ☐ Cache\Event\*          │                                                               │
│       │ ☐ User\Event\*           │                                                               │
│       │                          │                                                               │
│       │  [ Select All ] [ Clear ]│                                                               │
│       └──────────────────────────┘                                                               │
│  ──── ──────────────────────────────  ──────────   ──────────  ──────────────                    │
│  1    Application\Event\BeforeReq.    3            0.8ms       continued                         │
│  2    Router\Event\RouteMatched       2            0.3ms       continued                         │
│  3    Controller\Event\BeforeAction   1            0.1ms       continued                         │
│  4    Database\Event\QueryExecuted    1            0.2ms       continued                         │
│                                                                                                  │
│  Filtered: 4 of 12 events                                                                       │
└──────────────────────────────────────────────────────────────────────────────────────────────────┘
```

## Row Expansion

Click a row to expand and show detail panel below it:

```
┌══ DB Queries (8) ════════════════════════════════════════════════════════════════════ ─ □ ✕ ─────┐
│                                                                                                  │
│  #    Query                                              Duration    Rows     Status             │
│  ──── ──────────────────────────────────────────────     ─────────   ──────   ──────             │
│  1    SELECT * FROM "user" WHERE "active" = 1            12.3ms      42       OK                 │
│  ▼ 2  SELECT * FROM "role" WHERE "id" IN (...)           8.7ms       5        OK                 │
│  ├────────────────────────────────────────────────────────────────────────────────────────────┐  │
│  │                                                                                            │  │
│  │  Full Query:                                                                               │  │
│  │  SELECT * FROM "role" WHERE "id" IN (:id0, :id1, :id2)                                    │  │
│  │                                                                                            │  │
│  │  Parameters:                                                                               │  │
│  │  ┌──────────┬──────────┬──────────┐                                                        │  │
│  │  │ Param    │ Value    │ Type     │                                                        │  │
│  │  ├──────────┼──────────┼──────────┤                                                        │  │
│  │  │ :id0     │ 1        │ integer  │                                                        │  │
│  │  │ :id1     │ 2        │ integer  │                                                        │  │
│  │  │ :id2     │ 3        │ integer  │                                                        │  │
│  │  └──────────┴──────────┴──────────┘                                                        │  │
│  │                                                                                            │  │
│  │  Source:     RoleRepository::findByIds() at line 28                                        │  │
│  │  Connection: default (pgsql)                                                               │  │
│  │  Explain:    Index Scan using role_pkey on role (cost=0.15..12.55 rows=5 width=64)        │  │
│  │                                                                                            │  │
│  │                                                        [ Copy Query ]  [ Explain Analyze ]  │  │
│  └────────────────────────────────────────────────────────────────────────────────────────────┘  │
│  3    INSERT INTO "audit_log" (user_id, action,...)      5.7ms       1        OK                 │
│  4    SELECT * FROM "permission" WHERE "role_id"...      5.2ms       12       OK                 │
│                                                                                                  │
└──────────────────────────────────────────────────────────────────────────────────────────────────┘
```

## Global Search Filter

The search box at the top filters across all visible columns:

```
┌══ Events (12) ═══════════════════════════════════════════════════════════════════════ ─ □ ✕ ─────┐
│                                                                                                  │
│  🔍 Query                                                              ✕ Clear                   │
│                                                                                                  │
│  #    Event Class                          Listeners    Duration    Propagation                  │
│  ──── ──────────────────────────────────   ──────────   ──────────  ──────────────               │
│  4    Database\Event\[Query]Executed       1            0.2ms       continued                    │
│  5    Database\Event\[Query]Executed       1            0.1ms       continued                    │
│  6    Database\Event\[Query]Executed       1            0.2ms       continued                    │
│  7    Database\Event\[Query]Executed       1            0.1ms       continued                    │
│  8    Database\Event\[Query]Executed       1            0.1ms       continued                    │
│                                                                                                  │
│  Filtered: 5 of 12 events matching "Query"                                                      │
└──────────────────────────────────────────────────────────────────────────────────────────────────┘
```

Search term highlighted in results: `[Query]` indicates matching text.

## Column Resizing

Drag column borders to resize. Double-click to auto-fit:

```
  BEFORE                                      AFTER (dragged "Query" column wider)

  #    Query               Duration           #    Query                              Duration
  ──── ──────────────────  ─────────          ──── ──────────────────────────────────  ─────────
  1    SELECT * FROM...    12.3ms             1    SELECT * FROM "user" WHERE "act...  12.3ms
  2    SELECT * FROM...    8.7ms              2    SELECT * FROM "role" WHERE "id"...  8.7ms
            │                                                    │
            │◀── drag border ──▶│                                │
```

## Pagination Controls

```
  ◀  1  2  3  ...  8  ▶     Per page: [ 10 ▾ ]     Showing 1-10 of 78

  ◀  Previous page
  ▶  Next page
  1  Jump to page 1
  ...  Gap indicator
```

## Table Actions Menu

Right-click a column header for column options:

```
  Duration ▾
  ┌────────────────────┐
  │  Sort Ascending     │
  │  Sort Descending    │
  │  ──────────────     │
  │  Filter...          │
  │  ──────────────     │
  │  Hide Column        │
  │  Auto-fit Width     │
  │  ──────────────     │
  │  Pin Left           │
  │  Pin Right          │
  │  ──────────────     │
  │  Reset All Columns  │
  └────────────────────┘
```

## Table Toolbar (Fullscreen Mode)

In fullscreen, the table gains an extended toolbar:

```
┌══ DB Queries (8) ═══════════════════════════════════════════════════════════════════════ ─ ◻ ✕ ──┐
│                                                                                                  │
│  🔍 Filter queries...      [Slow > 10ms ☐]  [Failed ☐]  [Duplicate ☐]         [Columns ▾]      │
│                                                                        [Export ▾: CSV | JSON]    │
│                                                                                                  │
│  ...table content...                                                                             │
│                                                                                                  │
├──────────────────────────────────────────────────────────────────────────────────────────────────┤
│  Total: 8 queries  │  42.8ms total  │  105 rows  │  0 failed  │  1 conn       Page 1 of 1       │
└──────────────────────────────────────────────────────────────────────────────────────────────────┘
```

## Column Visibility Picker

```
  [Columns ▾]
  ┌────────────────────┐
  │  ☑ #               │
  │  ☑ Query           │
  │  ☑ Duration        │
  │  ☑ Rows            │
  │  ☑ Status          │
  │  ☐ Connection      │
  │  ☐ Source          │
  │  ☐ Parameters      │
  │  ──────────────     │
  │  [ Show All ]       │
  │  [ Reset ]          │
  └────────────────────┘
```

## Responsive Behavior

When the widget is narrow (fewer than 6 grid columns), the table adapts:

```
  WIDE (8+ columns)                          NARROW (3-5 columns)

  #  Query              Duration  Rows       #  Query              Duration
  ── ──────────────     ────────  ────       ── ──────────────     ────────
  1  SELECT * FROM...   12.3ms    42         1  SELECT * FROM...   12.3ms
  2  SELECT * FROM...   8.7ms     5          2  SELECT * FROM...   8.7ms
  3  INSERT INTO...     5.7ms     1          3  INSERT INTO...     5.7ms

                                              Low-priority columns hidden
                                              (Rows, Status, Connection)
                                              Click row to see all fields
```
