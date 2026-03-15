# Variant C: Timeline-Driven — Debug Entry List

## Entry List with Mini-Timeline Sparklines

The entry list is the "index" view showing all captured debug entries. Each row includes a mini-timeline
sparkline giving an at-a-glance performance profile.

```
┌──────────────────────────────────────────────────────────────────────────────────────────────────────────────────┐
│ ┌─ADP─┐  Debug Entries ── 147 entries ── Last 24h                                      [◀ Prev] [Next ▶]      │
├────┬───┴─────────────────────────────────────────────────────────────────────────────────────────────────────────┤
│    │                                                                                                            │
│    │  ┌─ Filters ────────────────────────────────────────────────────────────────────────────────────────────┐  │
│    │  │ Method: [All ▼]  Status: [All ▼]  Min time: [___]ms  Search: [________________________]  [Apply]   │  │
│    │  └──────────────────────────────────────────────────────────────────────────────────────────────────────┘  │
│ ┌──┤                                                                                                            │
│ │🔍│  ┌─────────┬──────┬──────────────────────────────┬──────┬─────────┬─────────────────────────────┬────────┐ │
│ │  │  │ Time    │Method│ URL                          │Status│Duration │ Timeline                    │ Memory │ │
│ │📋│  ├─────────┼──────┼──────────────────────────────┼──────┼─────────┼─────────────────────────────┼────────┤ │
│ │  │  │ 14:32:07│ GET  │ /api/users/42                │  200 │  247ms  │ ██▓░▓▓░░████░██             │  4.2MB │ │
│ │⏱ │  │ 14:32:05│ POST │ /api/orders                  │  201 │  534ms  │ █▓░░░░▓▓▓▓▓▓▓▓████░░░██    │  8.1MB │ │
│ │  │  │ 14:32:03│ GET  │ /api/products?page=2         │  200 │   89ms  │ ██▓░███                     │  2.8MB │ │
│ │📊│  │ 14:32:01│ GET  │ /api/users/42/orders         │  200 │  312ms  │ ██▓░░▓▓▓▓████░░██           │  5.4MB │ │
│ │  │  │ 14:31:58│ PUT  │ /api/users/42                │  200 │  198ms  │ ██▓░▓▓░░████░█              │  3.9MB │ │
│ │🗄 │  │ 14:31:55│ GET  │ /dashboard                   │  200 │  423ms  │ █▓░░▓▓▓▓▓▓▓████░░░░██      │  6.7MB │ │
│ │  │  │ 14:31:52│ POST │ /api/auth/login              │  200 │  156ms  │ ██▓░░▓▓████░█               │  3.2MB │ │
│ │🔔│  │ 14:31:49│ GET  │ /api/products/99             │  404 │   45ms  │ ██▓░█░                      │  2.1MB │ │
│ │  │  │ 14:31:47│ POST │ /api/orders/15/cancel        │  422 │  267ms  │ ██▓░░▓▓░░████░░█            │  4.8MB │ │
│ │🌐│  │ 14:31:44│ GET  │ /api/users                   │  200 │  178ms  │ ██▓░▓▓████░██               │  4.0MB │ │
│ │  │  │ 14:31:40│ DELETE│/api/orders/12               │  204 │  112ms  │ ██▓░▓▓██░█                  │  2.9MB │ │
│ │⚙ │  │ 14:31:38│ GET  │ /health                      │  200 │   12ms  │ ██░                         │  1.8MB │ │
│ └──┤  └─────────┴──────┴──────────────────────────────┴──────┴─────────┴─────────────────────────────┴────────┘ │
│    │                                                                                                            │
│    │  ─── Page 1 of 13 ──────────────────────────────────── [◀ 1  2  3  4  5 ... 13 ▶] ──── 12 per page ────  │
│    │                                                                                                            │
└────┴─────────────────────────────────────────────────────────────────────────────────────────────────────────────┘
```

## Mini-Timeline Sparkline Legend

Each sparkline is a compressed timeline using block characters, colored by collector type:

```
  ██▓░▓▓░░████░██
  ││││││││││││││└─ Response (blue)
  │││││││││││││└── Response (blue)
  ││││││││││││└─── View render (amber)
  │││││││││└────── Event (orange, ░ = short)
  ││││││││└─────── DB queries (green, ████ = long)
  ││││││└───────── Handler gap
  │││││└────────── HTTP client (purple)
  ││││└─────────── HTTP client (purple)
  │││└──────────── Router (blue, ░ = short)
  ││└───────────── Middleware (blue, ▓ = medium)
  │└────────────── Request start (blue)
  └─────────────── Request start (blue)

Character density represents proportion of total request time.
Wider sparkline = longer request (scaled proportionally).
```

## Row Hover State

```
│  │ 14:32:07│ GET  │ /api/users/42                │  200 │  247ms  │ ██▓░▓▓░░████░██             │  4.2MB │
│  ├─────────┴──────┴──────────────────────────────┴──────┴─────────┴─────────────────────────────┴────────┤
│  │  ┌─ Preview ──────────────────────────────────────────────────────────────────────────────────────┐   │
│  │  │  Middleware: 3  │  DB Queries: 2 (46ms)  │  Events: 1  │  Logs: 4 (1 warning)  │  Cache: 0    │   │
│  │  │  Route: user.show  │  Controller: UserController::show  │  Peak memory: 4.2MB                  │   │
│  │  └────────────────────────────────────────────────────────────────────────────────────────────────┘   │
│  ├─────────┬──────┬──────────────────────────────┬──────┬─────────┬─────────────────────────────┬────────┤
│  │ 14:32:05│ POST │ /api/orders                  │  201 │  534ms  │ █▓░░░░▓▓▓▓▓▓▓▓████░░░██    │  8.1MB │
```

## Row Status Indicators

```
┌────────────────┬────────────────────────────────────────────────────────────────────────────────────────────┐
│ Visual         │ Meaning                                                                                   │
├────────────────┼────────────────────────────────────────────────────────────────────────────────────────────┤
│ Red left bar   │ HTTP 5xx response — server error                                                          │
│ Orange left bar│ HTTP 4xx response — client error                                                          │
│ No left bar    │ HTTP 2xx/3xx — success or redirect                                                        │
│ ⚠ icon         │ Contains slow operations (any span exceeding threshold)                                   │
│ ✖ icon         │ Contains uncaught exception                                                               │
│ Bold row       │ Currently selected entry (shown in timeline view)                                         │
│ Strikethrough  │ CLI command (vs HTTP request)                                                             │
│ 🔄 badge       │ AJAX/fetch request (vs page load)                                                        │
└────────────────┴────────────────────────────────────────────────────────────────────────────────────────────┘
```

## Sort Options

```
  ┌─ Sort by ──────────────┐
  │ ● Time (newest first)  │
  │ ○ Time (oldest first)  │
  │ ○ Duration (slowest)   │
  │ ○ Duration (fastest)   │
  │ ○ Memory (highest)     │
  │ ○ Status code          │
  │ ○ DB query count       │
  └────────────────────────┘
```

## Clicking a Row — Navigates to Timeline View

Clicking any row in the entry list navigates to the full timeline view (02-debug-timeline.md) for that entry.
The selected row is highlighted and the context bar updates with the entry's metadata.

## Bulk Actions (Shift-select multiple rows)

```
┌─ 3 entries selected ────────────────────────────────────────────────────────────────────────────────────────┐
│  [Compare Timelines]  [Export JSON]  [Delete]  [Clear Selection]                                            │
└─────────────────────────────────────────────────────────────────────────────────────────────────────────────┘
```

- "Compare Timelines" opens the comparison view (07-compare-timeline.md) with selected entries stacked
- Maximum 3 entries for comparison
