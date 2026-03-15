# 02 — Debug Main View

## Full Layout: Entry List + Collector Accordion

```
┌──────┬───────────────────────────────┬──────────────────────────────────────────────────────────────────────────────┐
│      │ [🔍 Filter entries...   ] [x]│  Debug > #a1b2c3                                                    [...]  │
│ ┌──┐ ├───────────────────────────────┤──────────────────────────────────────────────────────────────────────────────│
│ │ D│ │ [All ▾] [Status ▾] [Time ▾]  │  POST /api/users  201  45ms                        2026-03-15 14:23:07    │
│ └──┘ ├───────────────────────────────┤  Request ID: a1b2c3d4-e5f6-7890-abcd-ef1234567890                         │
│      │                               ├──────────────────────────────────────────────────────────────────────────────│
│ ┌──┐ │  Web Requests            (5)  │                                                                            │
│ │ I│ │  ─────────────────────────    │  ┌─ 📋 Request/Response ──────────────────────────── 201 Created ── [📌▾]─┐│
│ └──┘ │                               │  │  Method: POST           Status: 201 Created                            ││
│      │  GET  /api/users     200 23ms │  │  URL: /api/users        Time: 45ms                                     ││
│ ┌──┐ │ >POST /api/users     201 45ms<│  │  Body: {"name":"Jo...}  Size: 1.2 KB                                   ││
│ │ C│ │  GET  /api/users/5   200 12ms │  └────────────────────────────────────────────────────────────────────────┘ │
│ └──┘ │  GET  /dashboard     200 89ms │                                                                            │
│      │  DEL  /api/users/3   204 31ms │  ┌─ 🗄  Database ────────────────────── 3 queries, 12ms total ── [📌▾]──┐ │
│ ┌──┐ │                               │  │  #  Query                              Time   Rows  Status            ││
│ │ S│ │  Console Commands        (2)  │  │  1  INSERT INTO users (name, em...)     8ms    1     OK                ││
│ └──┘ │  ─────────────────────────    │  │  2  SELECT * FROM roles WHERE...        2ms    3     OK                ││
│      │  CLI  migrate          0 1.2s │  │  3  INSERT INTO user_roles (us...)      2ms    3     OK                ││
│      │  CLI  cache:clear      0 0.3s │  └────────────────────────────────────────────────────────────────────────┘ │
│      │                               │                                                                            │
│      │                               │  ┌─ 📝 Log ──────────────────────────────── 5 entries, 1 warning ─ [▾]──┐ │
│      │                               │  │  (collapsed — click to expand)                                        │ │
│      │                               │  └────────────────────────────────────────────────────────────────────────┘ │
│      │                               │                                                                            │
│      │                               │  ┌─ 📡 Events ──────────────────────────────── 12 events fired ── [▾]──┐  │
│      │                               │  │  (collapsed — click to expand)                                       │ │
│      │                               │  └────────────────────────────────────────────────────────────────────────┘ │
│      │                               │                                                                            │
│      ├───────────────────────────────┤  ┌─ 🧩 Service ────────────────────────── 8 services resolved ── [▾]──┐   │
│ ┌──┐ │ ● SSE    [Auto-latest: ON ]  │  │  (collapsed — click to expand)                                      │  │
│ │ T│ │ 7 entries       Page 1 of 1  │  └────────────────────────────────────────────────────────────────────────┘ │
└──┴──┴───────────────────────────────┴──────────────────────────────────────────────────────────────────────────────┘
```

## Entry List Panel — Detailed

### Filter Bar

```
┌───────────────────────────────┐
│ [🔍 Filter entries...   ] [x]│
├───────────────────────────────┤
│ [All ▾] [Status ▾] [Time ▾]  │
├───────────────────────────────┤
```

Filter dropdown options:

```
[All ▾]                [Status ▾]             [Time ▾]
┌───────────────┐      ┌───────────────┐      ┌───────────────┐
│ ● All         │      │ ● Any status  │      │ ● Any time    │
│ ○ Web only    │      │ ○ 2xx Success │      │ ○ Last 5 min  │
│ ○ Console     │      │ ○ 3xx Redirect│      │ ○ Last 15 min │
│ ○ Bookmarked  │      │ ○ 4xx Error   │      │ ○ Last 1 hour │
└───────────────┘      │ ○ 5xx Server  │      │ ○ Today       │
                       └───────────────┘      └───────────────┘
```

### Entry Row States

```
Normal:
│  GET  /api/users     200 23ms │

Hover:
│ [GET  /api/users     200 23ms]│  <-- subtle background highlight

Selected:
│▎>POST /api/users     201 45ms<│  <-- left accent bar + background

Error entry:
│  GET  /api/fail      500 92ms │  <-- red status text

Slow entry:
│  GET  /api/heavy     200 2.3s │  <-- orange time text (> 1s threshold)

Bookmarked:
│★ GET  /api/users     200 23ms │  <-- star icon prefix
```

### Compact Entry Format

```
┌───────────────────────────────┐
│ ┌─────┐                      │
│ │ GET │ /api/users       200 │  Method badge: colored (green/blue/orange/red/purple)
│ └─────┘              23ms    │  Path: truncated if too long
│                              │  Status: colored by range
│ ┌─────┐                      │  Time: colored if slow
│ │POST │ /api/users       201 │
│ └─────┘              45ms    │
└───────────────────────────────┘
```

### Search Behavior

```
Searching for "user":
┌───────────────────────────────┐
│ [🔍 user                ] [x]│
├───────────────────────────────┤
│                               │
│  Web Requests            (3)  │  <-- count updates to match
│  ─────────────────────────    │
│  GET  /api/users     200 23ms │  <-- "user" highlighted in path
│ >POST /api/users     201 45ms<│
│  GET  /api/users/5   200 12ms │
│                               │
│  Console Commands        (0)  │  <-- no matches in this group
│  ─────────────────────────    │
│  (no matching entries)        │
│                               │
├───────────────────────────────┤
│ ● SSE    [Auto-latest: ON ]  │
│ 3 of 7 entries                │  <-- filtered count
└───────────────────────────────┘
```

## Content Area — Header Section

```
┌──────────────────────────────────────────────────────────────────────────────┐
│  Debug > #a1b2c3                                                    [...]  │
├──────────────────────────────────────────────────────────────────────────────┤
│  POST /api/users  201  45ms                        2026-03-15 14:23:07     │
│  Request ID: a1b2c3d4-e5f6-7890-abcd-ef1234567890                         │
├──────────────────────────────────────────────────────────────────────────────┤
```

The `[...]` overflow menu:

```
                                                               ┌──────────────────┐
                                                               │  Copy entry ID   │
                                                               │  Copy as cURL    │
                                                               │  Compare with... │
                                                               │  Bookmark        │
                                                               │  Open in new tab │
                                                               │  ────────────────│
                                                               │  Delete entry    │
                                                               └──────────────────┘
```

## Accordion Section States

### Collapsed (1-line summary)

```
┌─ 🗄  Database ─────────────────────────────── 3 queries, 12ms total ── [▾]──┐
│  (collapsed — click header or [▾] to expand)                                │
└─────────────────────────────────────────────────────────────────────────────┘
```

### Expanded

```
┌─ 🗄  Database ─────────────────────────────── 3 queries, 12ms total ── [▴]──┐
│                                                                              │
│  ┌────┬──────────────────────────────────────────┬───────┬──────┬──────────┐ │
│  │ #  │ Query                                    │ Time  │ Rows │ Status   │ │
│  ├────┼──────────────────────────────────────────┼───────┼──────┼──────────┤ │
│  │ 1  │ INSERT INTO users (name, email, crea...) │  8ms  │ 1    │ OK       │ │
│  │ 2  │ SELECT * FROM roles WHERE active = 1     │  2ms  │ 3    │ OK       │ │
│  │ 3  │ INSERT INTO user_roles (user_id, rol...) │  2ms  │ 3    │ OK       │ │
│  └────┴──────────────────────────────────────────┴───────┴──────┴──────────┘ │
│                                                                              │
└──────────────────────────────────────────────────────────────────────────────┘
```

### Pinned (stays open when others collapse)

```
┌─ 🗄  Database ──────────── [📌] ──────────── 3 queries, 12ms total ── [▴]──┐
│  (pin icon highlighted — this section stays expanded)                       │
│  ...content...                                                              │
└─────────────────────────────────────────────────────────────────────────────┘
```

### Error State (attention badge)

```
┌─ 📝 Log ─────────────────── [!] ─────── 5 entries, 1 error, 1 warning ── [▾]──┐
│  (collapsed — red attention indicator)                                          │
└────────────────────────────────────────────────────────────────────────────────┘
```

## Scroll Behavior

The entry list and content area scroll independently:

```
┌──────┬───────────────────────────────┬──────────────────────────────────────────┐
│      │ [🔍 Filter...           ] [x]│  Debug > #a1b2c3                  [...] │
│      ├───────────────────────────────┤─────────────────────────────────────────── │
│      │                         ▲     │  POST /api/users 201 45ms         ▲     │
│ Nav  │  (scrollable list)      █     │                                   │     │
│ Rail │                         █     │  (scrollable content)             █     │
│      │                         │     │                                   █     │
│      │                         ▼     │                                   ▼     │
│      ├───────────────────────────────┤                                         │
│      │ ● SSE   [Auto-latest: ON ]   │  (status bar is sticky at bottom)       │
└──────┴───────────────────────────────┴─────────────────────────────────────────┘
```
