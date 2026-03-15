# Variant C: Timeline-Driven — Toolbar with Mini-Timeline

## Toolbar Overview

The toolbar is an embeddable widget that sits at the bottom of the target application's page.
In the timeline-driven design, the toolbar's centerpiece is a mini-timeline showing the current
request's performance profile at a glance. Clicking the toolbar opens the full ADP panel.

## Collapsed Toolbar (Default State)

```
┌──────────────────────────────────────────────────────────────────────────────────────────────────────────────────┐
│                                                                                                                  │
│                                    (target application content)                                                   │
│                                                                                                                  │
├──────────────────────────────────────────────────────────────────────────────────────────────────────────────────┤
│ ADP │ 200 │ 247ms │ ██▓░▓▓░░████░██ │ DB:5/94ms │ Logs:4 │ Mem:4.2MB │ PHP 8.4 │ Yii 3.0 │     [▲ Open]     │
└──────────────────────────────────────────────────────────────────────────────────────────────────────────────────┘
```

Components left to right:
- **ADP** logo/badge (clickable, opens panel)
- **HTTP status** with color badge (200=green, 404=orange, 500=red)
- **Total duration** with color coding (green <200ms, orange <500ms, red >=500ms)
- **Mini-timeline sparkline** — compressed request lifecycle
- **Quick stats** — DB query count/time, log count, peak memory
- **Environment** — PHP version, framework version
- **Open button** — expands to full toolbar

## Expanded Toolbar

```
┌──────────────────────────────────────────────────────────────────────────────────────────────────────────────────┐
│                                                                                                                  │
│                                    (target application content)                                                   │
│                                                                                                                  │
├──────────────────────────────────────────────────────────────────────────────────────────────────────────────────┤
│ ADP │ GET /api/users/42 │ 200 OK │ 247ms                                           [Open Panel] [▼ Collapse]   │
├──────────────────────────────────────────────────────────────────────────────────────────────────────────────────┤
│                                                                                                                  │
│  ┌─ Timeline ──────────────────────────────────────────────────────────────────────────────────────────────────┐ │
│  │  0ms       50ms       100ms      150ms      200ms      247ms                                                │ │
│  │  ├──────────┼──────────┼──────────┼──────────┼──────────┤                                                   │ │
│  │  Request   ██████████████████████████████████████████████████████████████████████████████████  247ms         │ │
│  │  Middlewar  ██████████████████████████████████████████████████████████████████████████████████  239ms         │ │
│  │  Router              ██████████████                                                            28ms          │ │
│  │  Handler                         ████████████████████████████████████████████                  145ms         │ │
│  │    DB (5)                          ██  ████████  ████  ████  ██████                            94ms total    │ │
│  │  Response                                                       ████████████████████████████   51ms          │ │
│  └─────────────────────────────────────────────────────────────────────────────────────────────────────────────┘ │
│                                                                                                                  │
│  ┌─ Quick Panels ──────────────────────────────────────────────────────────────────────────────────────────────┐ │
│  │                                                                                                              │ │
│  │  ┌─ Database ─────────────┐  ┌─ Logs ──────────────────┐  ┌─ Performance ───────────────────────────────┐  │ │
│  │  │ 5 queries, 94ms total  │  │ 4 entries               │  │ Duration: 247ms                             │  │ │
│  │  │                        │  │                          │  │ Memory:   4.2MB (peak)                     │  │ │
│  │  │ 1. users     12ms     │  │ INFO  User loaded    ... │  │ DB time:  94ms (38%)                       │  │ │
│  │  │ 2. orders    34ms  ⚠  │  │ INFO  Orders fetched ... │  │ PHP:      8.4.5                            │  │ │
│  │  │ 3. products  18ms     │  │ WARN  Low inventory  ... │  │ Framework: Yii 3.0.1                       │  │ │
│  │  │ 4. inventory 14ms     │  │ INFO  Response sent  ... │  │ Route:    user.show                        │  │ │
│  │  │ 5. config    16ms     │  │                          │  │                                             │  │ │
│  │  │                        │  │                          │  │ ┌─ Time Breakdown ───────┐                 │  │ │
│  │  │ [View all in Panel]    │  │ [View all in Panel]      │  │ │ App code   104ms ████  │                 │  │ │
│  │  └────────────────────────┘  └──────────────────────────┘  │ │ DB          94ms ███   │                 │  │ │
│  │                                                             │ │ Framework   49ms ██   │                 │  │ │
│  │                                                             │ └──────────────────────  ┘                 │  │ │
│  │                                                             └────────────────────────────────────────────┘  │ │
│  └──────────────────────────────────────────────────────────────────────────────────────────────────────────────┘ │
│                                                                                                                  │
└──────────────────────────────────────────────────────────────────────────────────────────────────────────────────┘
```

## Toolbar States

### Error State (5xx Response)

```
├──────────────────────────────────────────────────────────────────────────────────────────────────────────────────┤
│ ADP │ 500 │ 142ms │ ██▓░▓▓░░██✖     │ DB:2/23ms │ Logs:5 (1 error) │ Mem:3.8MB │ Exception! │   [▲ Open]     │
└──────────────────────────────────────────────────────────────────────────────────────────────────────────────────┘
  ^red background tint for error state       ^sparkline ends with ✖ for exception     ^error badge
```

### AJAX Request Indicator

When the toolbar detects AJAX requests via SSE, it briefly shows a notification:

```
├──────────────────────────────────────────────────────────────────────────────────────────────────────────────────┤
│ ADP │ 200 │ 247ms │ ██▓░▓▓░░████░██ │ DB:5/94ms │ Logs:4 │ Mem:4.2MB │          │            │   [▲ Open]     │
│                                                                                                                  │
│  ┌─ AJAX ─────────────────────────────────────────────────┐                                                      │
│  │ POST /api/orders  201  534ms  ██▓░▓▓▓▓████░██  DB:8   │  ◀── slides up, auto-hides after 3s                  │
│  └────────────────────────────────────────────────────────┘                                                      │
└──────────────────────────────────────────────────────────────────────────────────────────────────────────────────┘
```

## Mini-Timeline Sparkline Details

The sparkline in the collapsed toolbar is a compressed representation of the full timeline.

```
  ██▓░▓▓░░████░██
  │              │
  └──── 247ms ───┘

  Character mapping:
  █ = request/middleware/response phases (blue)
  ▓ = handler/router phases (blue-grey)
  ░ = gaps / application code
  █ = database segments (green — rendered with color in actual UI)
  █ = HTTP client segments (purple)
  ░ = events (orange)
  ✖ = exception occurred (red, replaces last char)

  Width: proportional to duration (max 30 characters)
  Fast request (<50ms):    ██▓░█          (short sparkline)
  Normal request (50-500ms): ██▓░▓▓░░████░██    (medium sparkline)
  Slow request (>500ms):   ██▓░░░▓▓▓▓▓▓▓▓████░░░░██████   (long sparkline)
```

## Toolbar Position and Size

```
┌────────────────┬──────────────────────────────────────────────────────────────────────────────────────────────┐
│ Property       │ Value                                                                                       │
├────────────────┼──────────────────────────────────────────────────────────────────────────────────────────────┤
│ Position       │ Fixed bottom of viewport (position: fixed; bottom: 0)                                       │
│ Width          │ 100% viewport width                                                                         │
│ Collapsed H    │ 36px                                                                                        │
│ Expanded H     │ 360px (configurable, draggable top edge)                                                    │
│ Z-index        │ 99999 (above target app content)                                                            │
│ Background     │ Surface color with subtle border-top                                                        │
│ Transition     │ 200ms ease-in-out for expand/collapse                                                       │
│ Draggable      │ Top edge of expanded toolbar can be dragged to resize                                       │
│ Dismissible    │ Double-click ADP logo to hide toolbar entirely                                               │
│ Keyboard       │ Ctrl+Shift+D toggles expand/collapse                                                        │
└────────────────┴──────────────────────────────────────────────────────────────────────────────────────────────┘
```

## "Open Panel" Navigation

Clicking "Open Panel" in the expanded toolbar navigates to the full ADP SPA (separate tab/window)
with the current entry pre-selected. The toolbar remains in the target app for continued monitoring.
