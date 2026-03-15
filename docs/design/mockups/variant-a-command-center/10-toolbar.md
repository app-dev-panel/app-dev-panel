# Variant A: Command Center — Embedded Toolbar

## Overview

The toolbar is a thin bar embedded at the bottom of the **target application** (not the ADP panel).
It provides at-a-glance metrics for the current request and a quick link to open the full debug panel.

The toolbar is injected via a small JS snippet and communicates with the ADP API independently.

## Full Layout — Collapsed (default)

The toolbar starts as a minimal tab on the bottom-right corner:

```
┌────────────────────────────────────────────────────────────────────────────────────────────────────────────────────┐
│                                                                                                                    │
│                                       TARGET APPLICATION CONTENT                                                   │
│                                                                                                                    │
│                                       (user's actual web page)                                                     │
│                                                                                                                    │
│                                                                                                                    │
│                                                                                                                    │
│                                                                                                                    │
│                                                                                                                    │
│                                                                                                                    │
│                                                                                                                    │
│                                                                                                           ┌──────┐│
│                                                                                                           │ ADP  ││
│                                                                                                           │ 87ms ││
│                                                                                                           └──────┘│
└────────────────────────────────────────────────────────────────────────────────────────────────────────────────────┘
```

The tab shows only the ADP logo/initials and the response time.
- Green text (< 100ms): fast response
- Amber text (100-500ms): moderate response
- Red text (> 500ms): slow response

## Full Layout — Expanded Bar

Clicking the tab expands the full toolbar across the bottom:

```
┌────────────────────────────────────────────────────────────────────────────────────────────────────────────────────┐
│                                                                                                                    │
│                                       TARGET APPLICATION CONTENT                                                   │
│                                                                                                                    │
│                                       (user's actual web page)                                                     │
│                                                                                                                    │
│                                                                                                                    │
│                                                                                                                    │
│                                                                                                                    │
│                                                                                                                    │
├────────────────────────────────────────────────────────────────────────────────────────────────────────────────────┤
│ ADP │ GET 200 │ 87ms │ 4.1 MB │ Logs: 5 │ DB: 8 (34ms) │ Events: 31 │ Errors: 0 │ Cache: 3/1 │ ⛶ Open │ ✕ │
└────────────────────────────────────────────────────────────────────────────────────────────────────────────────────┘
```

## Toolbar Segments (Expanded)

```
┌─────┬─────────┬──────┬────────┬─────────┬──────────────┬────────────┬───────────┬───────────┬────────┬───┐
│ ADP │ GET 200 │ 87ms │ 4.1 MB │ Logs: 5 │ DB: 8 (34ms) │ Events: 31 │ Errors: 0 │ Cache 3/1 │ ⛶ Open │ ✕ │
└─────┴─────────┴──────┴────────┴─────────┴──────────────┴────────────┴───────────┴───────────┴────────┴───┘
  ▲      ▲        ▲      ▲        ▲          ▲              ▲           ▲            ▲          ▲       ▲
  │      │        │      │        │          │              │           │            │          │       │
  Logo   Method   Resp   Peak     Log        DB queries     Event      Exception    Cache      Open    Close
  +link  +Status  time   memory   count      count+time     count      count        hit/miss   panel   toolbar
```

### Segment Details

**Logo (ADP):**
- Clickable, opens the full panel in a new tab
- Shows ADP version on hover tooltip

**Method + Status:**
```
  ┌─────────┐    ┌─────────┐    ┌─────────┐    ┌──────────┐
  │ GET 200 │    │ POST 201│    │ GET 404 │    │ POST 500 │
  └─────────┘    └─────────┘    └─────────┘    └──────────┘
  green bg       green bg       red bg         red bg, bold
```

**Response Time:**
```
  87ms     <- green (< 100ms)
  234ms    <- amber (100-500ms)
  1.2s     <- red (> 500ms)
```

**Memory:**
```
  4.1 MB   <- normal
  24.1 MB  <- amber (> 16MB)
  128 MB   <- red (> 64MB)
```

**Log Count:**
```
  Logs: 5       <- gray (no warnings/errors)
  Logs: 5 ⚠ 2  <- amber (2 warnings)
  Logs: 5 ✗ 1  <- red (1 error)
```

**DB Queries:**
```
  DB: 8 (34ms)       <- normal
  DB: 25 (340ms)     <- amber (many queries or slow total)
  DB: 8 (34ms) ● 2   <- has 2 slow individual queries
```

**Events:**
```
  Events: 31   <- always gray, informational only
```

**Errors:**
```
  Errors: 0    <- green
  Errors: 1    <- red, pulsing dot
  Errors: 3    <- red, bold, pulsing dot
```

**Cache:**
```
  Cache: 3/1   <- 3 hits, 1 miss (green if >50% hit rate)
  Cache: 1/5   <- 1 hit, 5 misses (red if <50% hit rate)
```

## Segment Hover Popover

Hovering over a segment shows a mini preview:

**Logs hover:**
```
                    ┌─ Logs ─────────────────────────────────────────────┐
                    │                                                     │
                    │  INFO   User query: page=2, limit=25               │
                    │  DEBUG  SELECT * FROM users LIMIT 25 OFFSET 25     │
                    │  INFO   Found 25 users (total: 142)                │
                    │  DEBUG  Cache HIT: user_count_total                 │
                    │  WARN   Rate limit approaching for IP 192.168.1.100│
                    │                                                     │
                    │                              [View in Debug Panel]  │
                    └─────────────────────────────────────────────────────┘
```

**DB hover:**
```
                    ┌─ DB Queries ───────────────────────────────────────┐
                    │                                                     │
                    │  1. SELECT * FROM "user" WHERE...          12ms    │
                    │  2. SELECT COUNT(*) FROM "user"...          3ms    │
                    │  3. SELECT "r"."name" FROM "role"...        8ms    │
                    │  4. SELECT "key" FROM "setting"...          2ms    │
                    │  ... 4 more queries                                │
                    │                                                     │
                    │  Total: 8 queries, 34ms                            │
                    │                              [View in Debug Panel]  │
                    └─────────────────────────────────────────────────────┘
```

**Errors hover (with error):**
```
                    ┌─ Exceptions ───────────────────────────────────────┐
                    │                                                     │
                    │  RuntimeException                                   │
                    │  User not found with ID 999                        │
                    │  /app/src/User/UserRepository.php:87               │
                    │                                                     │
                    │                              [View in Debug Panel]  │
                    └─────────────────────────────────────────────────────┘
```

## Toolbar Position Variants

Bottom (default):
```
│                    content                     │
├────────────────────────────────────────────────┤
│ ADP │ GET 200 │ 87ms │ ...              │ ✕   │
└────────────────────────────────────────────────┘
```

Top (configurable):
```
┌────────────────────────────────────────────────┐
│ ADP │ GET 200 │ 87ms │ ...              │ ✕   │
├────────────────────────────────────────────────┤
│                    content                     │
```

## Toolbar Themes

The toolbar adapts its background to not clash with the target app:

```
Dark (default):
  bg: #161b22, text: #c9d1d9, border-top: #30363d

Light:
  bg: #f6f8fa, text: #24292f, border-top: #d0d7de

Transparent:
  bg: rgba(22, 27, 34, 0.9), text: #c9d1d9, backdrop-filter: blur(8px)
```

Theme is configurable via toolbar settings or auto-detected from the target app's `color-scheme` meta tag.

## AJAX Request Tracking

When the target app makes AJAX requests, the toolbar updates to show the latest request.
A history dropdown allows switching between requests in the current page session:

```
┌─────────────────────────────────────────────────────────────────────────────────────────────────────────────────────┐
│ ADP │ GET 200 │ 87ms │ 4.1 MB │ ... │ ⛶ Open │ ▾ History (4) │ ✕                                                 │
└─────────────────────────────────────────────────────────────────────────────────────────────────────────────────────┘
                                                        │
                                                        ▼
                                        ┌──────────────────────────────────┐
                                        │  GET 200 /api/users   87ms  ● ← │
                                        │  GET 200 /api/roles   23ms      │
                                        │  POST 201 /api/users 145ms      │
                                        │  GET 200 /page         52ms     │
                                        └──────────────────────────────────┘
                                                                     ▲
                                                                     │
                                                                  Current
                                                                  request
```

## Minimized State

Double-clicking the toolbar minimizes it to a tiny floating badge:

```
                                                                                          ┌───┐
                                                                                          │ ● │
                                                                                          └───┘
                                                                                          ▲
                                                                                          │
                                                                                    Colored dot:
                                                                                    green = ok
                                                                                    red = errors
                                                                                    Click to restore
```

## Interaction Notes

- Toolbar is rendered in a shadow DOM to avoid CSS conflicts with the target app
- Toolbar z-index: 2147483647 (max) to always stay on top
- Toolbar can be dragged to reposition (top/bottom)
- Click "Open" to open ADP panel in new tab with the current debug entry pre-selected
- "View in Debug Panel" links in hover popovers open the specific collector tab
- Toolbar state (expanded/collapsed/minimized, position) persists in localStorage
- Toolbar auto-hides when DevTools panel is open in same-window mode

## Configuration (injected via data attributes)

```html
<script src="/adp-toolbar.js"
  data-adp-url="http://localhost:8081"
  data-adp-position="bottom"
  data-adp-theme="auto"
  data-adp-expanded="false"
  data-adp-segments="method,time,memory,logs,db,errors"
></script>
```

## State Management

| State                  | Storage      | Rationale                                |
|------------------------|-------------|------------------------------------------|
| Toolbar expanded       | localStorage| User preference, persists                |
| Toolbar position       | localStorage| User preference, persists                |
| Toolbar theme          | localStorage| User preference, persists                |
| Current debug entry    | Memory      | Set by page load, updated by AJAX        |
| Request history        | Memory      | Transient, per-page-session              |
| Hover popover open     | Local state | Transient mouse state                    |
| Minimized state        | localStorage| User preference, persists                |
| Visible segments       | localStorage| User preference via config               |
