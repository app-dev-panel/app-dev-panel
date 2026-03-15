# Variant E: Dashboard Grid — Toolbar as Mini-Dashboard

## Overview

The toolbar is a persistent, embeddable widget strip that sits at the bottom of the target application's
page (not the ADP panel). It acts as a mini-dashboard showing key metrics from the most recent request.
Clicking the toolbar opens the full ADP panel. The toolbar itself uses the same widget rendering engine
as the main dashboard, but in a compact single-row format.

## Collapsed Toolbar (Default)

The toolbar starts as a thin strip at the bottom of the target application:

```
┌────────────────────────────────────────────────────────────────────────────────────────────────────────────────┐
│                                                                                                                │
│                                     TARGET APPLICATION PAGE                                                    │
│                                                                                                                │
│                                                                                                                │
│                                                                                                                │
│                                                                                                                │
│                                                                                                                │
│                                                                                                                │
│                                                                                                                │
│                                                                                                                │
│                                                                                                                │
│                                                                                                                │
│                                                                                                                │
│                                                                                                                │
│                                                                                                                │
│                                                                                                                │
├────────────────────────────────────────────────────────────────────────────────────────────────────────────────┤
│ ADP  GET /api/users  200  145ms  12.4MB  8 queries  23 logs  0 errors  12 events       [▲ Expand]  [✕ Close] │
└────────────────────────────────────────────────────────────────────────────────────────────────────────────────┘
```

## Expanded Toolbar (Click Expand)

Expanding the toolbar reveals a mini-dashboard with configurable widget slots:

```
┌────────────────────────────────────────────────────────────────────────────────────────────────────────────────┐
│                                                                                                                │
│                                     TARGET APPLICATION PAGE                                                    │
│                                                                                                                │
│                                                                                                                │
│                                                                                                                │
│                                                                                                                │
│                                                                                                                │
│                                                                                                                │
│                                                                                                                │
│                                                                                                                │
├────────────────────────────────────────────────────────────────────────────────────────────────────────────────┤
│ ADP  GET /api/users  200  145ms  12.4MB  8 queries  23 logs  0 errors  12 events       [▼ Collapse] [⚙] [✕]  │
├────────────────────────────────────────────────────────────────────────────────────────────────────────────────┤
│                                                                                                                │
│  ┌─ Request ────────────┐  ┌─ Timeline ──────────────────────────┐  ┌─ Logs (recent) ────────────────────┐   │
│  │ GET /api/users       │  │                                     │  │ 14:32:07 INFO  Request completed   │   │
│  │ 200 OK  145ms        │  │ ████░░░████░░██████████░░░░░░██░██ │  │ 14:32:07 WARN  Deprecated method   │   │
│  │ Handler: UserCtrl    │  │ Boot Route  Action+DB     View Resp │  │ 14:32:07 WARN  Deprecated method   │   │
│  │ Memory: 12.4 MB      │  │                                     │  │ 14:32:07 WARN  Memory threshold   │   │
│  └──────────────────────┘  └─────────────────────────────────────┘  └────────────────────────────────────┘   │
│                                                                                                                │
│  ┌─ Queries ─────────────────────────────────────────┐  ┌─ Performance ──────────────────────────────────┐   │
│  │ 8 queries  │  42.8ms total  │  0 slow  │  0 fail  │  │ Total: 145ms  DB: 43ms  Boot: 18ms  View: 15ms│   │
│  │                                                    │  │                                                │   │
│  │ Slowest: SELECT * FROM "user"... (12.3ms)         │  │ ████████████████████████████████████████████   │   │
│  │ Most rows: SELECT * FROM "user"... (42 rows)      │  │ DB 30%  Action 54%  Boot 12%  View 10%        │   │
│  └────────────────────────────────────────────────────┘  └────────────────────────────────────────────────┘   │
│                                                                                                                │
│                                                                               [ Open Full Panel ▸ ]            │
│                                                                                                                │
└────────────────────────────────────────────────────────────────────────────────────────────────────────────────┘
```

## Toolbar Widget Slots

The expanded toolbar has a mini-grid with 4-6 widget slots. Each slot is a compact version of a
full dashboard widget. Users can configure which collectors appear via the gear icon.

### Toolbar Settings Panel

```
┌─ Toolbar Settings ───────────────────────────────────────────┐
│                                                               │
│  Toolbar Position:   [ Bottom ▾ ]                             │
│  Auto-expand:        [ On Error ▾ ]                           │
│  Opacity:            [████████░░] 80%                         │
│                                                               │
│  Widget Slots (max 6):                                        │
│                                                               │
│  Slot 1: [ Request Info ▾  ]   Size: [ Small ▾ ]             │
│  Slot 2: [ Timeline ▾       ]   Size: [ Medium ▾ ]            │
│  Slot 3: [ Logs (recent) ▾  ]   Size: [ Medium ▾ ]            │
│  Slot 4: [ Query Summary ▾  ]   Size: [ Medium ▾ ]            │
│  Slot 5: [ Performance ▾    ]   Size: [ Medium ▾ ]            │
│  Slot 6: [ -- empty -- ▾   ]   Size: [          ]            │
│                                                               │
│  Available collectors:                                        │
│  Request Info, Response, Timeline, Logs, DB Queries,          │
│  Events, Exception, Memory, Performance Summary               │
│                                                               │
│                                 [ Reset to Default ]  [ Save ] │
└───────────────────────────────────────────────────────────────┘
```

## Toolbar States

### Normal Request (200)

```
│ ADP  GET /api/users  200  145ms  12.4MB  8 queries  23 logs  0 errors  12 events       [▲ Expand]  [✕ Close] │
```

### Slow Request (> 500ms)

```
│ ADP  GET /dashboard  200  1.2s   34MB   22 queries  45 logs  0 errors  18 events  SLOW [▲ Expand]  [✕ Close] │
│ ▀▀▀▀▀▀▀▀▀▀▀▀▀▀▀▀▀▀▀▀▀▀▀▀▀▀▀▀▀▀▀▀▀▀▀▀▀▀▀▀▀▀▀▀▀▀▀▀▀▀▀▀▀▀▀▀▀▀▀▀▀▀▀▀▀▀▀▀▀▀▀▀▀▀▀▀▀▀ (yellow accent bar)     │
```

### Error Request (4xx/5xx)

```
│ ADP  GET /api/products  500  1.0s  34MB  3 queries  15 logs  2 errors  8 events  ERR  [▲ Expand]  [✕ Close]  │
│ ▀▀▀▀▀▀▀▀▀▀▀▀▀▀▀▀▀▀▀▀▀▀▀▀▀▀▀▀▀▀▀▀▀▀▀▀▀▀▀▀▀▀▀▀▀▀▀▀▀▀▀▀▀▀▀▀▀▀▀▀▀▀▀▀▀▀▀▀▀▀▀▀▀▀▀▀▀▀▀ (red accent bar)        │
```

### Auto-Expanded on Error

When `Auto-expand: On Error` is set, the toolbar automatically expands for error responses,
showing the exception widget prominently:

```
├────────────────────────────────────────────────────────────────────────────────────────────────────────────────┤
│ ADP  GET /api/products  500  1.0s  34MB  3 queries  15 logs  2 errors                  [▼ Collapse] [⚙] [✕] │
├────────────────────────────────────────────────────────────────────────────────────────────────────────────────┤
│                                                                                                                │
│  ┌─ Exception ───────────────────────────────────────────────────────────────────────────────────────────┐    │
│  │                                                                                                       │    │
│  │  RuntimeException: Database connection timed out                                                      │    │
│  │                                                                                                       │    │
│  │  in src/Repository/ProductRepo.php:42                                                                 │    │
│  │  #0 src/Controller/ProductController.php:28                                                           │    │
│  │  #1 vendor/framework/Router.php:156                                                                   │    │
│  │                                                                                                       │    │
│  └───────────────────────────────────────────────────────────────────────────────────────────────────────┘    │
│                                                                                                                │
│  ┌─ Recent Logs ────────────────────────────────┐  ┌─ Request ──────────────────────────────────────────┐    │
│  │ 14:31:10 ERROR DB connection timeout (800ms) │  │ GET /api/products  500 Internal Server Error       │    │
│  │ 14:31:10 ERROR RuntimeException: Database... │  │ Handler: ProductController::index  Time: 1024ms    │    │
│  │ 14:31:11 WARN  Error handler caught exc.     │  │ Memory: 34.2 MB  PHP: 8.4.5                       │    │
│  └──────────────────────────────────────────────┘  └────────────────────────────────────────────────────┘    │
│                                                                                                                │
│                                                                               [ Open Full Panel ▸ ]            │
│                                                                                                                │
└────────────────────────────────────────────────────────────────────────────────────────────────────────────────┘
```

## Toolbar Interaction with Full Panel

```
  TARGET APP with TOOLBAR                       FULL ADP PANEL
  ───────────────────────                       ──────────────

  ┌──────────────────────┐                     ┌──────────────────────┐
  │                      │                     │  ADP   ◀ ▶  ...     │
  │   Target App Page    │                     │  Debug | Inspector   │
  │                      │  "Open Full Panel"  ├──────────────────────┤
  │                      │  ──────────────────▶ │  ┌────┬────┐        │
  │                      │                     │  │    │    │        │
  ├──────────────────────┤                     │  ├────┼────┤        │
  │  ADP toolbar         │                     │  │    │    │        │
  └──────────────────────┘                     │  └────┴────┘        │
                                                └──────────────────────┘

  Clicking "Open Full Panel" opens ADP in a new tab or side panel,
  pre-selected to the same debug entry shown in the toolbar.
```

## Mini-Widget Variants

Each widget type has a compact variant for toolbar use:

### Status (Compact)

```
┌─ Request ────────┐
│ GET /api/users   │
│ 200 OK  145ms    │
│ Memory: 12.4 MB  │
└──────────────────┘
```

### Timeline (Compact)

```
┌─ Timeline ──────────────────────────┐
│ ████░░░████░░██████████░░░░░░██░██ │
│ Boot Route  Action+DB     View Resp │
└─────────────────────────────────────┘
```

### Log Summary (Compact)

```
┌─ Logs ──────────────────────────────┐
│ Last: Request completed (145ms)     │
│ INFO: 5  DEBUG: 14  WARN: 3  ERR: 1│
└─────────────────────────────────────┘
```

### Query Summary (Compact)

```
┌─ Queries ──────────────────────────────┐
│ 8 queries  42.8ms  0 slow  0 failed   │
│ Slowest: SELECT * FROM "user"  12.3ms │
└────────────────────────────────────────┘
```

### Exception (Compact)

```
┌─ Exception ─────────────────────────────────┐
│ RuntimeException                             │
│ Database connection timed out                │
│ in src/Repository/ProductRepo.php:42        │
└──────────────────────────────────────────────┘
```
