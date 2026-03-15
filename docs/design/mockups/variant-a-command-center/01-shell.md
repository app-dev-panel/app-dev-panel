# Variant A: Command Center — App Shell

## Full Layout (>= 1280px)

```
┌──────────────────────────────────────────────────────────────────────────────────────────────────────────────────────┐
│  POST  │ 200 │ /api/users/42/profile │ 145ms │ 8.2 MB │ 2026-03-15 14:23:07 │  ◀ ▶  │ ⊞ Compare │  ⌘K Search   │
├────┬───┴─────┴───────────────────────┴───────┴────────┴─────────────────────┴───────┴───────────┴─────────────────┤
│    │ Request │ Response │ Logs │ Events │ DB │ Exceptions │ Mail │ Service │ Profiling │ Assets │ Router │ Dump  │
│ 🔍 ├─────────────────────────────────────────────────────────────────────────────────────────────────────────────────┤
│    │                                                                                                               │
│ 📋 │                                                                                                               │
│    │                                                                                                               │
│ 🔧 │                            MAIN CONTENT AREA                                                                  │
│    │                                                                                                               │
│ 📊 │                            (varies by active tab / page)                                                       │
│    │                                                                                                               │
│ 📁 │                                                                                                               │
│    │                                                                                                               │
│ 🛠  │                                                                                                               │
│    │                                                                                                               │
│    │                                                                                                               │
│    │                                                                                                               │
│    │                                                                                                               │
│    │                                                                                                               │
│    │                                                                                                               │
│    │                                                                                                               │
├────┴─────────────────────────────────────────────────────────────────────────────────────────────────────────────────┤
│  POST /api/debug/view/abc123 -> 200 OK (45ms)                            ● SSE Connected │ ADP v1.2.0 │  ⚙       │
└──────────────────────────────────────────────────────────────────────────────────────────────────────────────────────┘
```

## Component Breakdown

### 1. Context Bar (top, 48px height)

```
┌──────────────────────────────────────────────────────────────────────────────────────────────────────────────────────┐
│ ┌──────┐ ┌─────┐                                                                                                  │
│ │ POST │ │ 200 │  /api/users/42/profile   145ms   8.2 MB   2026-03-15 14:23:07    ◀ ▶    ⊞ Compare    ⌘K Search  │
│ └──────┘ └─────┘                                                                                                  │
└──────────────────────────────────────────────────────────────────────────────────────────────────────────────────────┘
 ▲          ▲       ▲                        ▲       ▲        ▲                     ▲       ▲            ▲
 │          │       │                        │       │        │                     │       │            │
 Method     Status  URL path                 Time    Memory   Timestamp             Nav     Compare      Command
 badge      badge   (truncated if long)                                             arrows  toggle       palette
 (colored)  (colored)                                                                                    trigger
```

**Context bar states:**

No entry selected:
```
┌──────────────────────────────────────────────────────────────────────────────────────────────────────────────────────┐
│  No debug entry selected — pick one from the list or wait for incoming requests              ⌘K Search             │
└──────────────────────────────────────────────────────────────────────────────────────────────────────────────────────┘
```

Console command entry:
```
┌──────────────────────────────────────────────────────────────────────────────────────────────────────────────────────┐
│ ┌─────┐ ┌────┐                                                                                                    │
│ │ CLI │ │  0 │  app:import-users --force   2340ms   24.1 MB   2026-03-15 14:20:00   ◀ ▶   ⊞ Compare   ⌘K Search  │
│ └─────┘ └────┘                                                                                                    │
└──────────────────────────────────────────────────────────────────────────────────────────────────────────────────────┘
```

### 2. Sidebar Rail (left, 48px width)

Collapsed (default):
```
┌────┐
│    │
│ 🔍 │  <- Debug (active page indicator: left border accent)
│    │
│ 📋 │  <- Debug List
│    │
│ 🔧 │  <- Inspector
│    │
│ 📊 │  <- OpenAPI
│    │
│ 📁 │  <- Frames
│    │
│ 🛠  │  <- Gii
│    │
│    │
│    │
│    │
│    │
│    │
│    │
└────┘
```

Expanded on hover (200px width, overlays content):
```
┌────────────────────────┐
│                        │
│  🔍  Debug             │  <- left accent border on active
│                        │
│  📋  Debug List        │
│                        │
│  🔧  Inspector    ▸    │  <- submenu indicator
│                        │
│  📊  OpenAPI           │
│                        │
│  📁  Frames            │
│                        │
│  🛠   Gii               │
│                        │
│                        │
│                        │
│                        │
│                        │
└────────────────────────┘
```

Inspector submenu (flyout):
```
                         ┌────────────────────────┐
                         │  Configuration         │
                         │  Events                │
                         │  Routes                │
                         │  Commands              │
                         │  Database              │
                         │  Files                 │
                         │  Git                   │
                         │  PHP Info              │
                         │  Composer              │
                         │  Opcache               │
                         │  Cache                 │
                         │  Tests                 │
                         │  Analyse               │
                         │  Translations          │
                         └────────────────────────┘
```

### 3. Tab Bar (below context bar, 40px height)

```
┌─────────────────────────────────────────────────────────────────────────────────────────────────────────────────┐
│  Request │ Response │ Logs (23) │ Events (47) │ DB (12) │ Exceptions │ Mail │ Service │ Profiling │ ··· ▾     │
│  ═══════                ════                    ═══                                                            │
└─────────────────────────────────────────────────────────────────────────────────────────────────────────────────┘
 ▲                        ▲                       ▲                                              ▲
 Active tab               Badge with count        Badge with count                               Overflow menu
 (accent underline)       (warning if slow)       (danger if errors)                             for hidden tabs
```

Tab bar is only visible on the Debug page. Inspector pages, OpenAPI, Frames, and Gii
replace it with their own sub-navigation or have no tab bar.

### 4. Status Bar (bottom, 24px height)

```
┌──────────────────────────────────────────────────────────────────────────────────────────────────────────────────────┐
│  POST /api/debug/view/abc123 -> 200 OK (45ms)                              ● SSE Connected │ ADP v1.2.0 │  ⚙     │
└──────────────────────────────────────────────────────────────────────────────────────────────────────────────────────┘
 ▲                                                                            ▲                ▲             ▲
 │                                                                            │                │             │
 Last API call made by the panel itself                                       Green dot =      Build         Settings
 (method, path, status, timing)                                               connected        version       modal
                                                                              Red dot =
                                                                              disconnected
```

SSE status variants:
```
 ● SSE Connected           <- green dot, connected and receiving
 ○ SSE Disconnected        <- red dot, not connected
 ◐ SSE Reconnecting...     <- yellow dot, attempting reconnect
```

### 5. Main Content Area

The content area is a flex container that fills all remaining space.
It scrolls independently from the shell (context bar and status bar stay fixed).

Content area structure:
```
┌─────────────────────────────────────────────────────────────────────────────────────────────────────────────────┐
│                                                                                                               │
│   ┌─── Page-specific toolbar (optional) ────────────────────────────────────────────────────────────────────┐  │
│   │  Filter controls, density toggle, export button, etc.                                                  │  │
│   └────────────────────────────────────────────────────────────────────────────────────────────────────────────┘  │
│                                                                                                               │
│   ┌─── Content ─────────────────────────────────────────────────────────────────────────────────────────────┐  │
│   │                                                                                                        │  │
│   │  Table / Tree / Form / Code viewer / etc.                                                              │  │
│   │                                                                                                        │  │
│   │  (scrollable)                                                                                          │  │
│   │                                                                                                        │  │
│   └────────────────────────────────────────────────────────────────────────────────────────────────────────────┘  │
│                                                                                                               │
└─────────────────────────────────────────────────────────────────────────────────────────────────────────────────┘
```

## Interaction Notes

- Sidebar rail: hover to expand, click to navigate, mouse-leave to collapse
- Sidebar rail: keyboard accessible via Tab key, Enter to select
- Context bar: prev/next arrows cycle through debug entries in chronological order
- Context bar: clicking the URL opens the entry selector dropdown
- Status bar settings gear opens a modal with theme toggle, SSE URL config, etc.

## State Management

| State                  | Storage      | Rationale                                    |
|------------------------|-------------|----------------------------------------------|
| Current page           | URL path    | Bookmarkable, shareable                      |
| Active debug entry ID  | URL param   | `?id=abc123` — shareable                     |
| Active collector tab   | URL param   | `?tab=logs` — shareable                      |
| Sidebar expanded       | Local state | Transient hover state                        |
| SSE connection status  | Redux       | Global, needed by status bar and indicators  |
| Last API action        | Redux       | Updated by API middleware                    |
| Compare mode active    | URL param   | `?compare=def456` — shareable                |
| Table density          | localStorage| User preference, persists across sessions    |
| Theme (dark/light)     | localStorage| User preference                              |
