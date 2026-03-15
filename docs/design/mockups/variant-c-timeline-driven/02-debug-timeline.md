# Variant C: Timeline-Driven — Debug Timeline View

## Main Timeline — Default State

```
┌──────────────────────────────────────────────────────────────────────────────────────────────────────────────────┐
│ ┌─ADP─┐  GET /api/users/42  ─  200 OK  ─  247ms  ─  ID: 6f3a9b  ─  2026-03-15 14:32:07   [◀ Prev] [Next ▶]  │
├────┬───┴─────────────────────────────────────────────────────────────────────────────────────────────────────────┤
│    │  ░░░░░░░▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓░░░░ │
│    │                                                                                                            │
│    │  Time ──▶ 0ms        50ms        100ms       150ms       200ms       247ms                                 │
│    │           ├───────────┼───────────┼───────────┼───────────┼───────────┤                                     │
│ ┌──┤           │           │           │           │           │           │                                     │
│ │🔍│  Request  ████████████████████████████████████████████████████████████████████████████████  247ms           │
│ │  │           │           │           │           │           │           │                                     │
│ │📋│  Middlew. │██████████████████████████████████████████████████████████████████████████████│  239ms           │
│ │  │    Auth   │████████████████│      │           │           │           │                    42ms             │
│ │⏱ │    CORS   │       │████████│      │           │           │           │                    18ms             │
│ │  │    Debug  │          │█████████████████████████████████████████████████████████████████████  197ms           │
│ │📊│           │           │           │           │           │           │                                     │
│ │  │  Router   │           ████████████│           │           │           │                    28ms             │
│ │🗄 │           │           │           │           │           │           │                                     │
│ │  │  Handler  │           │     ██████████████████████████████████████████│                    145ms            │
│ │🔔│    DB #1  │           │       ████│           │           │           │                    12ms             │
│ │  │    DB #2  │           │           │████████████│           │           │                    34ms             │
│ │🌐│    HTTP   │           │           │    ████████████████████│           │                    52ms             │
│ │  │    Event  │           │           │           │      ██████│           │                    18ms             │
│ │⚙ │    View   │           │           │           │           │██████████ │                    28ms             │
│ └──┤           │           │           │           │           │           │                                     │
│    │  Respons  │           │           │           │           │  █████████████████████████████  51ms             │
│    │           │           │           │           │           │           │                                     │
│    │  Logs     │   ▼info   │    ▼info  │           │  ▼warn    │           │  ▼info                              │
│    │           ├───────────┼───────────┼───────────┼───────────┼───────────┤                                     │
│    │                                                                                                            │
│    │  Legend: ████ Request  ████ Database  ████ HTTP Client  ████ Events  ████ View  ████ Response               │
│    │                                                                                                            │
│    ├─────────────────────────────────────────────────────────────────────────────────────────────────────────────┤
│    │  Detail Panel ─── (Click a timeline segment to view details)                                               │
│    │                                                                                                            │
│    │                              No segment selected                                                           │
│    │                                                                                                            │
└────┴─────────────────────────────────────────────────────────────────────────────────────────────────────────────┘
```

## Hover Tooltip

When the user hovers over a timeline bar, a tooltip appears anchored to the cursor.

```
                             ┌───────────────────────────────┐
                             │  DB Query #2                  │
                             │  ─────────────────────────    │
                             │  Duration:  34ms              │
                             │  Start:     92ms              │
                             │  Table:     orders            │
                             │  Rows:      15                │
                             │  ─────────────────────────    │
                             │  Click to view SQL            │
    ─────────────████████████████████████─────────            │
                             └───────────────────────────────┘
                             ^tooltip anchored above bar
```

- Tooltip appears after 200ms hover delay
- Positioned above bar, centered horizontally
- Falls back to below-bar if near top edge
- Shows collector-specific summary (varies by type)

## Tooltip Content by Collector Type

```
┌─────────────────┬───────────────────────────────────────────────────────────────────────────────────────────┐
│ Collector       │ Tooltip Fields                                                                           │
├─────────────────┼───────────────────────────────────────────────────────────────────────────────────────────┤
│ Request         │ Method, URL, Status, Duration                                                            │
│ Middleware      │ Class name, Duration, Order index                                                        │
│ Router          │ Matched route, Pattern, Action                                                           │
│ Handler         │ Controller::action, Duration                                                             │
│ Database        │ Query preview (truncated 80ch), Duration, Rows                                           │
│ HTTP Client     │ Method + URL, Status, Duration                                                           │
│ Event           │ Event class, Listener count, Duration                                                    │
│ View            │ Template name, Duration, Variables count                                                  │
│ Response        │ Status, Content-Type, Size, Duration                                                     │
│ Log             │ Level, Message (truncated 60ch), Channel                                                 │
└─────────────────┴───────────────────────────────────────────────────────────────────────────────────────────┘
```

## Click to Expand — Selected Segment

When a bar is clicked, it gains a highlight border and the detail panel opens below.

```
│    │  Time ──▶ 0ms        50ms        100ms       150ms       200ms       247ms                                 │
│    │           ├───────────┼───────────┼───────────┼───────────┼───────────┤                                     │
│    │           │           │           │           │           │           │                                     │
│    │  Request  ████████████████████████████████████████████████████████████████████████████████  247ms           │
│    │  Middlew. │██████████████████████████████████████████████████████████████████████████████│  239ms           │
│    │    Auth   │████████████████│      │           │           │           │                    42ms             │
│    │    CORS   │       │████████│      │           │           │           │                    18ms             │
│    │    Debug  │          │█████████████████████████████████████████████████████████████████████  197ms           │
│    │  Router   │           ████████████│           │           │           │                    28ms             │
│    │  Handler  │           │     ██████████████████████████████████████████│                    145ms            │
│    │  ┏DB #1┓  │           │       ┏━━━┓           │           │           │                    12ms  ◀ selected │
│    │    DB #2  │           │           │████████████│           │           │                    34ms             │
│    │    HTTP   │           │           │    ████████████████████│           │                    52ms             │
│    │    Event  │           │           │           │      ██████│           │                    18ms             │
│    │    View   │           │           │           │           │██████████ │                    28ms             │
│    │  Respons  │           │           │           │           │  █████████████████████████████  51ms             │
│    │           │           │           │           │           │           │                                     │
│    ├─── Detail ── DB Query #1 ───────────────────────────────────────────── Duration: 12ms ── Start: 72ms ────┤
│    │  [SQL]  [Parameters]  [Explain]  [Stack Trace]                                                            │
│    │  ────────────────────────────────────────────────────────────────────────────────────────────────────────  │
│    │  SELECT u.id, u.name, u.email, u.created_at                                                               │
│    │  FROM users u                                                                                              │
│    │  WHERE u.id = :id AND u.status = :status                                                                   │
│    │  ORDER BY u.created_at DESC                                                                                │
│    │                                                                                                            │
│    │  Connection: default (mysql)  │  Rows: 1  │  Cached: No  │  Transaction: None                              │
└────┴─────────────────────────────────────────────────────────────────────────────────────────────────────────────┘
```

## Zoomed-In View

After mousewheel zoom on the Handler region (100ms–200ms range):

```
│    │  Time ──▶ 70ms     90ms     110ms    130ms    150ms    170ms    190ms    210ms                             │
│    │           ├─────────┼─────────┼─────────┼─────────┼─────────┼─────────┼─────────┤                         │
│    │           │         │         │         │         │         │         │         │                         │
│    │  Handler  │  ███████████████████████████████████████████████████████████████████████████████████           │
│    │           │         │         │         │         │         │         │         │                         │
│    │    DB #1  │  █████████████████│         │         │         │         │         │   12ms                   │
│    │           │         │         │         │         │         │         │         │                         │
│    │    DB #2  │         │  ███████████████████████████│         │         │         │   34ms                   │
│    │           │         │         │         │         │         │         │         │                         │
│    │    HTTP   │         │         │ ██████████████████████████████████████████████  │   52ms                   │
│    │           │         │         │         │         │         │         │         │                         │
│    │    Event  │         │         │         │         │ █████████████████ │         │   18ms                   │
│    │           │         │         │         │         │         │         │         │                         │
│    │    View   │         │         │         │         │         │ ████████████████████  28ms                   │
│    │           │         │         │         │         │         │         │         │                         │
│    │  Logs     │  ▼info  │         │  ▼warn  │         │         │  ▼info  │         │                         │
│    │           ├─────────┼─────────┼─────────┼─────────┼─────────┼─────────┼─────────┤                         │
```

## Waterfall Nesting Rules

```
Nesting depth:
  Level 0:  Request (root span)
  Level 1:    Middleware, Router, Handler, Response
  Level 2:      Individual middleware (Auth, CORS, Debug)
  Level 2:      DB queries, HTTP calls, Events, View rendering
  Level 3:        Sub-queries, nested events
  Level 4+:       Further nesting (rare)

Visual indentation:
  Level 0:  No indent
  Level 1:  12px indent + connecting line
  Level 2:  24px indent + connecting line
  Level 3:  36px indent + connecting line

Collapse behavior:
  - Click the arrow (▶/▼) next to a parent bar to collapse/expand children
  - Collapsed parent shows aggregated child count: "Handler ████ (5 children)"
  - Default: first 2 levels expanded, deeper levels collapsed
```

## Collapsed Waterfall

```
│    │  Request  ████████████████████████████████████████████████████████████████████████████████  247ms           │
│    │  ▶ Midlw  │██████████████████████████████████████████████████████████████████████████████│  239ms  (3)     │
│    │  Router   │           ████████████│           │           │           │                    28ms             │
│    │  ▶ Handl  │           │     ██████████████████████████████████████████│                    145ms  (5)      │
│    │  Respons  │           │           │           │           │  █████████████████████████████  51ms             │
│    │           │           │           │           │           │           │                                     │
│    │  Logs ▶   │                           4 log entries                                                        │
```

## Log Markers on Timeline

Log entries appear as diamond markers on a dedicated "Logs" row at the bottom of the timeline.

```
│    │  Logs     ▼          ▼           ▼                       ▲                       ▼                         │
│    │           info       info        info                    warn                    info                       │
```

- ▼ = info/debug level (grey)
- ▲ = warning level (orange, upward triangle for visibility)
- ✖ = error/critical level (red)
- Hover shows log message preview
- Click opens log detail in detail panel

## Slow Operation Indicators

Operations exceeding configured thresholds get visual emphasis:

```
│    │    DB #2  │           │████░░░░░░░░│           │           │                    34ms  ⚠ SLOW              │
                                  ^hash pattern overlay indicates slow query (>20ms threshold)
```

Thresholds (configurable):
- DB queries: >20ms
- HTTP client: >500ms
- Middleware: >50ms
- View rendering: >100ms
