# 09 — Embedded Toolbar with Preview Panel

## Design Concept

The toolbar is a thin bar embedded at the bottom of the target application's page.
It provides at-a-glance debug info and expands into a preview panel for quick
inspection without switching to the full ADP panel.

## Collapsed Toolbar (Default)

```
Target application page content here...
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
┌────────────────────────────────────────────────────────────────────────────────────────────────┐
│ ADP │ POST /api/users │ 201 │ 45ms │ 3 queries │ 5 logs │ 12 events │ ● SSE │ [▲ Open Panel] │
└────────────────────────────────────────────────────────────────────────────────────────────────┘
```

### Toolbar Segments

```
┌──────┬──────────────────┬──────┬───────┬────────────┬────────┬───────────┬───────┬─────────────┐
│ Logo │ Route            │Status│ Time  │ DB         │ Logs   │ Events    │ SSE   │ Action      │
│ ADP  │ POST /api/users  │ 201  │ 45ms  │ 3q / 12ms │ 5 (1!) │ 12 fired  │ ●     │ [▲ Open]    │
└──────┴──────────────────┴──────┴───────┴────────────┴────────┴───────────┴───────┴─────────────┘
  │         │                │       │         │          │          │         │          │
  │         │                │       │         │          │          │         │          └─ Open full panel
  │         │                │       │         │          │          │         └─ Green=connected
  │         │                │       │         │          │          └─ Event count
  │         │                │       │         │          └─ Log count (! = has errors/warnings)
  │         │                │       │         └─ Query count / total DB time
  │         │                │       └─ Total request time
  │         │                └─ HTTP status (colored)
  │         └─ Method + path of current request
  └─ ADP logo / click to toggle
```

## Toolbar — Segment Hover States

Hovering over a segment shows a quick tooltip:

```
                                    ┌──────────────────────┐
                                    │  Request time: 45ms  │
                                    │  Server: 38ms        │
                                    │  Network: 7ms        │
┌──────┬──────────────────┬──────┬──┴───┬────────────┬─────┴──┬───────────┬───────┬─────────────┐
│ ADP  │ POST /api/users  │ 201  │ 45ms │ 3q / 12ms │ 5 (1!) │ 12 fired  │ ●     │ [▲ Open]    │
└──────┴──────────────────┴──────┴──────┴────────────┴────────┴───────────┴───────┴─────────────┘
```

## Preview Panel — Expanded (Click a Segment)

Clicking a segment (e.g., "3q / 12ms") expands a preview panel above the toolbar.
The preview panel is 300px tall by default, resizable by dragging the top edge.

### Database Preview

```
Target application page content here...
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
┌────────────────────────────────────────────────────────────────────────────────────────────────┐
│  Database Queries                                              3 queries, 12ms [Open in ADP]  │
├────────────────────────────────────────────────────────────────────────────────────────────────┤
│                                                                                              │
│  ┌────┬──────────────────────────────────────────────────────────────┬───────┬──────┬───────┐ │
│  │ #  │ Query                                                      │ Time  │ Rows │ Status│ │
│  ├────┼──────────────────────────────────────────────────────────────┼───────┼──────┼───────┤ │
│  │ 1  │ INSERT INTO users (name, email, created_at) VALUES (?, ?)  │  8ms  │   1  │ OK    │ │
│  │ 2  │ SELECT * FROM roles WHERE active = 1                       │  2ms  │   3  │ OK    │ │
│  │ 3  │ INSERT INTO user_roles (user_id, role_id) VALUES (?, ?)    │  2ms  │   3  │ OK    │ │
│  └────┴──────────────────────────────────────────────────────────────┴───────┴──────┴───────┘ │
│                                                                                              │
│  Total: 12ms  |  7 rows  |  0 errors                                                        │
│                                                                                              │
├────────────────────────────────────────────────────────────────────────────────────────────────┤
│ ADP │ POST /api/users │ 201 │ 45ms │ [3q / 12ms] │ 5 logs │ 12 events │ ● SSE│ [▼ Close]   │
└────────────────────────────────────────────────────────────────────────────────────────────────┘
                                       ^^^^^^^^^^^^
                                       active segment highlighted
```

### Logs Preview

```
┌────────────────────────────────────────────────────────────────────────────────────────────────┐
│  Logs                                                     5 entries, 1 error [Open in ADP]    │
├────────────────────────────────────────────────────────────────────────────────────────────────┤
│                                                                                              │
│  [All] [Debug: 2] [Info: 1] [Warning: 1] [Error: 1]                                         │
│                                                                                              │
│  ┌────────────┬─────────┬──────────────────────────────────────────────┬──────────────────┐   │
│  │ Time       │ Level   │ Message                                      │ Category         │   │
│  ├────────────┼─────────┼──────────────────────────────────────────────┼──────────────────┤   │
│  │ 14:23:07.1 │ DEBUG   │ Routing matched: POST /api/users             │ router           │   │
│  │ 14:23:07.1 │ DEBUG   │ Resolving UserController::store              │ di               │   │
│  │ 14:23:07.2 │ INFO    │ Creating new user: john@example.com          │ app.user         │   │
│  │ 14:23:07.3 │ WARNING │ Email domain not in allowlist                 │ app.validation   │   │
│  │ 14:23:07.4 │ ERROR   │ Failed to send welcome email: SMTP timeout   │ app.mailer       │   │
│  └────────────┴─────────┴──────────────────────────────────────────────┴──────────────────┘   │
│                                                                                              │
├────────────────────────────────────────────────────────────────────────────────────────────────┤
│ ADP │ POST /api/users │ 201 │ 45ms │ 3q / 12ms │ [5 (1!)] │ 12 events │ ● SSE│ [▼ Close]   │
└────────────────────────────────────────────────────────────────────────────────────────────────┘
```

### Events Preview

```
┌────────────────────────────────────────────────────────────────────────────────────────────────┐
│  Events                                                        12 events [Open in ADP]        │
├────────────────────────────────────────────────────────────────────────────────────────────────┤
│                                                                                              │
│  ┌────┬────────────┬──────────────────────────────────────┬──────────┬────────┬─────────────┐ │
│  │ #  │ Time       │ Event                                │ Listeners│ Time   │ Status      │ │
│  ├────┼────────────┼──────────────────────────────────────┼──────────┼────────┼─────────────┤ │
│  │ 1  │ 14:23:07.0 │ Router\BeforeRoute                  │    2     │  1ms   │ OK          │ │
│  │ 2  │ 14:23:07.0 │ Router\AfterRoute                   │    1     │  0ms   │ OK          │ │
│  │ 3  │ 14:23:07.1 │ Controller\BeforeAction              │    3     │  2ms   │ OK          │ │
│  │ .. │ ...        │ (9 more)                             │          │        │             │ │
│  └────┴────────────┴──────────────────────────────────────┴──────────┴────────┴─────────────┘ │
│                                                                                              │
│  Showing 3 of 12                                                          [Show all in ADP]  │
│                                                                                              │
├────────────────────────────────────────────────────────────────────────────────────────────────┤
│ ADP │ POST /api/users │ 201 │ 45ms │ 3q / 12ms │ 5 logs │ [12 events] │ ● SSE│ [▼ Close]   │
└────────────────────────────────────────────────────────────────────────────────────────────────┘
```

### Request Summary Preview

```
┌────────────────────────────────────────────────────────────────────────────────────────────────┐
│  Request Summary                                                           [Open in ADP]      │
├────────────────────────────────────────────────────────────────────────────────────────────────┤
│                                                                                              │
│  ┌─ Request ────────────────────────────────┬─ Response ─────────────────────────────────┐    │
│  │ Method:  POST                            │ Status:   201 Created                      │    │
│  │ URL:     /api/users                      │ Time:     45ms                              │    │
│  │ Host:    localhost:8080                   │ Size:     256 B                             │    │
│  │ Content: application/json                │ Content:  application/json                  │    │
│  └──────────────────────────────────────────┴────────────────────────────────────────────┘    │
│                                                                                              │
│  Breakdown:                                                                                  │
│  ┌────────────────────────────────────────────────────────────────────────────────────┐       │
│  │ Routing │ Controller │ DB Queries │ Events │ Response │                            │       │
│  │  2ms    │   18ms     │   12ms     │  8ms   │   5ms    │         Total: 45ms        │       │
│  │  ████   │ █████████  │ ██████     │ ████   │ ██       │                            │       │
│  └────────────────────────────────────────────────────────────────────────────────────┘       │
│                                                                                              │
├────────────────────────────────────────────────────────────────────────────────────────────────┤
│ [ADP] │ POST /api/users │ 201 │ 45ms │ 3q / 12ms │ 5 logs │ 12 events│ ● SSE│ [▼ Close]    │
└────────────────────────────────────────────────────────────────────────────────────────────────┘
```

## Toolbar — Error State

When the current request has errors, the toolbar shows a red indicator:

```
┌────────────────────────────────────────────────────────────────────────────────────────────────┐
│ ADP │ GET /api/fail    │ 500 │ 92ms │ 1q / 3ms  │ 3 (1!) │ 4 events  │ ● SSE │ [▲ Open]    │
└────────────────────────────────────────────────────────────────────────────────────────────────┘
        ^^^^^^^^^^^^^^^^   ^^^                        ^^^^^
        normal text        red status                 red badge (has error)
```

## Toolbar — SSE Disconnected

```
┌────────────────────────────────────────────────────────────────────────────────────────────────┐
│ ADP │ POST /api/users  │ 201 │ 45ms │ 3q / 12ms │ 5 logs │ 12 events │ ○ SSE │ [▲ Open]    │
└────────────────────────────────────────────────────────────────────────────────────────────────┘
                                                                          ^
                                                                          hollow red dot
```

Clicking "SSE" shows reconnect option:

```
                                                              ┌─────────────────┐
                                                              │ SSE Disconnected│
                                                              │                 │
                                                              │ Last event: 2m  │
                                                              │ ago             │
                                                              │                 │
                                                              │ [Reconnect]     │
                                                              └─────────────────┘
┌──────────────────────────────────────────────────────────────────────────────────────────┐
│ ADP │ POST /api/users  │ 201 │ 45ms │ 3q / 12ms │ 5 logs │ 12 events │ ○ SSE │ [▲ Open]│
└──────────────────────────────────────────────────────────────────────────────────────────┘
```

## Toolbar Positioning Options

```
Position: Bottom (default)           Position: Top
┌────────────────────────┐          ┌────────────────────────┐
│                        │          │ ADP │ POST... │ 201 .. │
│   Application content  │          ├────────────────────────┤
│                        │          │                        │
│                        │          │   Application content  │
├────────────────────────┤          │                        │
│ ADP │ POST... │ 201 .. │          │                        │
└────────────────────────┘          └────────────────────────┘

Position: Floating                   Position: Hidden (icon only)
┌────────────────────────┐          ┌────────────────────────┐
│                        │          │                        │
│   Application content  │          │   Application content  │
│                        │          │                        │
│  ┌──────────────────┐  │          │                        │
│  │ ADP │ POST.. 201 │  │          │                   ┌──┐ │
│  └──────────────────┘  │          │                   │AP│ │
└────────────────────────┘          └───────────────────┴──┘─┘
```

## Resize Handle for Preview Panel

```
═══════════════════════ drag to resize ═══════════════════  <-- top edge of preview panel
┌────────────────────────────────────────────────────────┐     cursor: row-resize
│  Preview panel content                                 │     min-height: 150px
│  ...                                                   │     max-height: 60vh
│                                                        │     default: 300px
├────────────────────────────────────────────────────────┤
│ ADP │ POST /api/users │ 201 │ 45ms │ ... │ [▼ Close]  │
└────────────────────────────────────────────────────────┘
```
