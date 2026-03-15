# Variant C: Timeline-Driven — App Shell

## Full Shell Layout

```
┌──────────────────────────────────────────────────────────────────────────────────────────────────────────────────┐
│ ┌─ADP─┐  GET /api/users/42  ─  200 OK  ─  247ms  ─  ID: 6f3a9b  ─  2026-03-15 14:32:07   [◀ Prev] [Next ▶]  │
├────┬───┴─────────────────────────────────────────────────────────────────────────────────────────────────────────┤
│    │  ┌─ Minimap ───────────────────────────────────────────────────────────────────────────────────────────┐   │
│    │  │  ░░░░░░░░░░░░░░░░▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░ │   │
│    │  └─────────────────────────────────────────────────────────────────────────────────────────────────────┘   │
│ ┌──┤  ┌─ Time Axis ────────────────────────────────────────────────────────────────────────────────────────┐   │
│ │  │  │  0ms       50ms       100ms      150ms      200ms      250ms                                      │   │
│ │🔍│  │  ├──────────┼──────────┼──────────┼──────────┼──────────┤                                          │   │
│ │  │  ├────────────────────────────────────────────────────────────────────────────────────────────────────┤   │
│ │📋│  │                                                                                                    │   │
│ │  │  │  Request   ████████████████████████████████████████████████████████████████████████████████████████ │   │
│ │⏱ │  │  Middlewar  ██████████████████████████████████████████████████████████████████████████████████████  │   │
│ │  │  │    Auth      ████████████                                                                          │   │
│ │📊│  │    CORS           ██████                                                                           │   │
│ │  │  │    Debug            ██████████████████████████████████████████████████████████████████████████████  │   │
│ │🗄 │  │  Router              ████████████                                                                  │   │
│ │  │  │  Handler                          ██████████████████████████████████████████████                    │   │
│ │🔔│  │    DB #1                            ████████                                                       │   │
│ │  │  │    DB #2                                    ████████████                                            │   │
│ │🌐│  │    Event                                                ██████                                     │   │
│ │  │  │  Response                                                       ██████████████████████████████████ │   │
│ │⚙ │  │                                                                                                    │   │
│ └──┘  └────────────────────────────────────────────────────────────────────────────────────────────────────┘   │
│    │  ┌─ Detail Panel ─────────────────────────────────────────────────────────────────────────────────────┐   │
│    │  │  (Click a timeline segment to view details)                                                        │   │
│    │  │                                                                                                    │   │
│    │  │                         No segment selected                                                        │   │
│    │  │                                                                                                    │   │
│    │  └────────────────────────────────────────────────────────────────────────────────────────────────────┘   │
└────┴─────────────────────────────────────────────────────────────────────────────────────────────────────────────┘
```

## Shell Components

### Context Bar (Top, persistent)

```
┌──────────────────────────────────────────────────────────────────────────────────────────────────────────────────┐
│ ┌─ADP─┐  GET /api/users/42  ─  200 OK  ─  247ms  ─  ID: 6f3a9b  ─  2026-03-15 14:32:07   [◀ Prev] [Next ▶]  │
└──────────────────────────────────────────────────────────────────────────────────────────────────────────────────┘
  ^logo    ^method+URL            ^status    ^time      ^entry ID     ^timestamp             ^entry navigation
```

- Fixed height: 48px
- Background: surface color (dark: #1E1E1E, light: #FAFAFA)
- Always visible regardless of scroll position
- Prev/Next buttons cycle through recent debug entries

### Icon Rail (Left sidebar)

```
┌──┐
│🔍│  Debug (Timeline view)          — active: filled icon + accent left border
│📋│  Debug List (Entry browser)
│⏱ │  Performance (Flamechart)
│📊│  Profiler (Memory/CPU)
│🗄 │  Database (Query list)
│🔔│  Events (Dispatch log)
│🌐│  HTTP Client (Outbound)
│⚙ │  Inspector (Config/Routes/DI)
└──┘
```

- Width: 56px
- Icons only, tooltip on hover shows label
- Active module: accent-colored left border (4px)
- Hover: subtle background highlight

### Minimap (Below context bar)

```
┌─────────────────────────────────────────────────────────────────────────────────────────────────────────────┐
│  ░░░░░░░░░░░░░░░░▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░ │
└─────────────────────────────────────────────────────────────────────────────────────────────────────────────┘
  ^full timeline compressed            ^viewport rectangle (draggable)           ^areas outside current view
```

- Height: 24px
- Shows entire timeline in compressed form
- Dark rectangle indicates current viewport
- Drag rectangle to pan; click to jump
- Only visible on Debug Timeline view

### Timeline Zone (Center, main content)

```
┌─ Time Axis ────────────────────────────────────────────────────────────────────────────────────────────────┐
│  0ms       50ms       100ms      150ms      200ms      250ms                                              │
│  ├──────────┼──────────┼──────────┼──────────┼──────────┤                                                  │
├────────────────────────────────────────────────────────────────────────────────────────────────────────────┤
│                                                                                                            │
│  [Label]   [═══════════════ Bar ═══════════════]                                                           │
│  [Label]     [══════ Nested Bar ══════]                                                                    │
│                                                                                                            │
└────────────────────────────────────────────────────────────────────────────────────────────────────────────┘
```

- Occupies 60% of vertical space when detail panel is collapsed
- Resizable split with detail panel (drag divider)
- Sticky time axis at top during vertical scroll
- Labels column: fixed 120px, right-aligned, truncated with ellipsis

### Detail Panel (Bottom, collapsible)

```
┌─ Detail Panel ── DB Query #1 ────────────────────────────────────────────────────────── Duration: 12ms ───┐
│  [SQL]  [Parameters]  [Explain]  [Stack Trace]                                                            │
├──────────────────────────────────────────────────────────────────────────────────────────────────────────  ┤
│  SELECT * FROM users WHERE id = :id AND status = :status                                                  │
│                                                                                                            │
│  Parameters: {:id => 42, :status => "active"}                                                              │
│  Connection: default (mysql)                                                                               │
│  Rows returned: 1                                                                                          │
└────────────────────────────────────────────────────────────────────────────────────────────────────────────┘
```

- Default: 40% of vertical space
- Collapsible: double-click divider or press Escape
- Content changes based on selected segment type
- Tabs within panel vary by collector type

## Responsive Behavior

```
≥1440px (Desktop XL):  Full layout as shown above
≥1024px (Desktop):     Same layout, minimap hidden, narrower label column
≥768px  (Tablet):      Icon rail collapses to hamburger, timeline scrolls horizontally
<768px  (Mobile):      Not supported — show "use desktop" message
```
