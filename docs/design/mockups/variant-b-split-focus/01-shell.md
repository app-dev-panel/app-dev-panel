# 01 — App Shell (3-Column Layout)

## Full Shell — Default State

```
┌──────┬───────────────────────────────┬──────────────────────────────────────────────────────────────────────────────┐
│      │ Search...            [x]     │  Debug > #a1b2c3 > Request                                                 │
│ ┌──┐ ├───────────────────────────────┤──────────────────────────────────────────────────────────────────────────────│
│ │🔍│ │                               │                                                                            │
│ └──┘ │  Web Requests                 │                                                                            │
│      │  ─────────────────────────    │                                                                            │
│ ┌──┐ │  GET  /api/users     200 23ms │                                                                            │
│ │📊│ │ >POST /api/users     201 45ms<│                         Content Area                                       │
│ └──┘ │  GET  /api/users/5   200 12ms │                                                                            │
│      │  GET  /dashboard     200 89ms │                    (loads selected entry                                    │
│ ┌──┐ │  DELETE /api/u/3     204 31ms │                     detail here)                                            │
│ │🔧│ │                               │                                                                            │
│ └──┘ │  Console Commands             │                                                                            │
│      │  ─────────────────────────    │                                                                            │
│ ┌──┐ │  CLI  migrate        0   1.2s │                                                                            │
│ │⚙ │ │  CLI  cache:clear    0   0.3s │                                                                            │
│ └──┘ │                               │                                                                            │
│      │                               │                                                                            │
│      │                               │                                                                            │
│      │                               │                                                                            │
│      ├───────────────────────────────┤                                                                            │
│ ┌──┐ │ ● SSE    [Auto-latest: ON ]  │                                                                            │
│ │☾ │ │ 8 entries       Page 1 of 1  │                                                                            │
└──┴──┴───────────────────────────────┴──────────────────────────────────────────────────────────────────────────────┘
  48px          280px                                          flex
```

## Column Specifications

### Nav Rail (48px fixed)

```
┌──────┐
│      │
│ ┌──┐ │  Debug module (home)
│ │ D│ │
│ └──┘ │
│      │
│ ┌──┐ │  Inspector module
│ │ I│ │
│ └──┘ │
│      │
│ ┌──┐ │  Config viewer
│ │ C│ │
│ └──┘ │
│      │
│ ┌──┐ │  Settings
│ │ S│ │
│ └──┘ │
│      │
│      │
│      │  (spacer)
│      │
│      │
│ ┌──┐ │  Theme toggle (light/dark)
│ │ T│ │
│ └──┘ │
│      │
└──────┘
```

### Entry List (280px, resizable 200-500px)

```
┌───────────────────────────────┐
│ [🔍 Search entries...    ] [x]│  <-- filter input, clear button
├───────────────────────────────┤
│ [All ▾] [Status ▾] [Time ▾]  │  <-- filter dropdowns
├───────────────────────────────┤
│                               │
│  Web Requests            (5)  │  <-- group header with count
│  ─────────────────────────    │
│  GET  /api/users     200 23ms │  <-- method | path | status | time
│ >POST /api/users     201 45ms<│  <-- selected (highlighted)
│  GET  /api/users/5   200 12ms │
│  GET  /dashboard     200 89ms │
│  DEL  /api/users/3   204 31ms │
│                               │
│  Console Commands        (2)  │  <-- group header with count
│  ─────────────────────────    │
│  CLI  migrate          0 1.2s │  <-- command | exit code | time
│  CLI  cache:clear      0 0.3s │
│                               │
│                               │
│                               │
│                               │
├───────────────────────────────┤
│ ● SSE Connected               │  <-- green dot = connected
│ [Auto-latest: ON ]            │  <-- toggle: auto-select newest
│ 7 entries       Page 1 of 1   │  <-- entry count + pagination
└───────────────────────────────┘
```

### Resize Handle

```
         ┃
         ┃  <-- 4px drag handle between Entry List and Content
         ┃      cursor: col-resize
         ┃      visual: subtle vertical line, highlights on hover
         ┃
```

### Content Area (flex, min 400px)

```
┌──────────────────────────────────────────────────────────────────────────────┐
│  Debug > #a1b2c3 > Request                                          [...]  │  <-- breadcrumb + overflow menu
├──────────────────────────────────────────────────────────────────────────────┤
│                                                                            │
│  (content changes based on selected entry and active collector)            │
│                                                                            │
│  Modes:                                                                    │
│   - Accordion view (default): all collectors as expandable sections        │
│   - Single collector: focused view of one collector                        │
│   - Compare mode: horizontal split showing two entries                     │
│                                                                            │
└──────────────────────────────────────────────────────────────────────────────┘
```

## Shell with Narrow List (resized to 200px)

```
┌──────┬───────────────────┬──────────────────────────────────────────────────────────────────────────────────────────┐
│      │ Search...     [x] │  Debug > #a1b2c3 > Request                                                             │
│ ┌──┐ ├───────────────────┤────────────────────────────────────────────────────────────────────────────────────────  │
│ │ D│ │                   │                                                                                        │
│ └──┘ │ Web Requests  (5) │                                                                                        │
│      │ ───────────────── │                                                                                        │
│ ┌──┐ │ GET /api/u~ 200   │                          More space for content                                        │
│ │ I│ │>POST /api~ 201  < │                                                                                        │
│ └──┘ │ GET /api/u~ 200   │                                                                                        │
│      │ GET /dash~  200   │                                                                                        │
│ ┌──┐ │ DEL /api/~ 204    │                                                                                        │
│ │ C│ │                   │                                                                                        │
│ └──┘ │ Console       (2) │                                                                                        │
│      │ ───────────────── │                                                                                        │
│ ┌──┐ │ CLI migrate   0   │                                                                                        │
│ │ S│ │ CLI cache:c~  0   │                                                                                        │
│ └──┘ │                   │                                                                                        │
│      ├───────────────────┤                                                                                        │
│ ┌──┐ │ ● SSE  [Auto: ON]│                                                                                        │
│ │ T│ │ 7 items    P1/1   │                                                                                        │
└──┴──┴───────────────────┴──────────────────────────────────────────────────────────────────────────────────────────┘
```

## Shell — No Entry Selected (Empty State)

```
┌──────┬───────────────────────────────┬──────────────────────────────────────────────────────────────────────────────┐
│      │ Search...            [x]     │                                                                            │
│ ┌──┐ ├───────────────────────────────┤                                                                            │
│ │ D│ │                               │                                                                            │
│ └──┘ │  Web Requests            (5)  │                                                                            │
│      │  ─────────────────────────    │                                                                            │
│ ┌──┐ │  GET  /api/users     200 23ms │                    Select an entry from the list                            │
│ │ I│ │  POST /api/users     201 45ms │                    to view its debug data.                                  │
│ └──┘ │  GET  /api/users/5   200 12ms │                                                                            │
│      │  GET  /dashboard     200 89ms │                    Tip: Use arrow keys to navigate,                         │
│ ┌──┐ │  DEL  /api/users/3   204 31ms │                    Enter to select.                                         │
│ │ C│ │                               │                                                                            │
│ └──┘ │  Console Commands        (2)  │                                                                            │
│      │  ─────────────────────────    │                                                                            │
│      │  CLI  migrate          0 1.2s │                                                                            │
│      │  CLI  cache:clear      0 0.3s │                                                                            │
│      │                               │                                                                            │
│      ├───────────────────────────────┤                                                                            │
│ ┌──┐ │ ● SSE    [Auto-latest: OFF]  │                                                                            │
│ │ T│ │ 7 entries       Page 1 of 1  │                                                                            │
└──┴──┴───────────────────────────────┴──────────────────────────────────────────────────────────────────────────────┘
```

## Context Menu (Right-Click on Entry)

```
                  ┌─────────────────────────┐
  GET /api/users  │  Compare with...    ^C  │
 >POST /api/users │  Bookmark           ^B  │  <-- context menu overlays
  GET /api/users  │  Copy as cURL       ^K  │
                  │  Copy entry ID          │
                  │  ─────────────────────  │
                  │  Delete entry       Del │
                  └─────────────────────────┘
```
