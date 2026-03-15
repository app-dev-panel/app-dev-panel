# Variant A: Command Center — Command Palette

## Trigger

- Keyboard: `Ctrl+K` (or `Cmd+K` on macOS)
- Click the "Search" area in the context bar
- Always available regardless of current page

## Full Layout — Default State

```
┌──────────────────────────────────────────────────────────────────────────────────────────────────────────────────────┐
│                                                                                                                    │
│                                                                                                                    │
│         ┌─────────────────────────────────────────────────────────────────────────────────────────────┐             │
│         │                                                                                             │             │
│         │  🔍 [_____________________________________________________________]        [Esc to close]   │             │
│         │                                                                                             │             │
│         │  ── Recent ────────────────────────────────────────────────────────────────────────────────  │             │
│         │                                                                                             │             │
│         │   🕐  GET 200 /api/users?page=2&limit=25                               14:23:07            │             │
│         │   🕐  POST 201 /api/users                                              14:22:58            │             │
│         │   🕐  Inspector > Configuration                                        14:20:00            │             │
│         │                                                                                             │             │
│         │  ── Quick Actions ─────────────────────────────────────────────────────────────────────────  │             │
│         │                                                                                             │             │
│         │   📋  Go to Debug List                                                 Ctrl+Shift+L        │             │
│         │   🔧  Open Inspector > Config                                          Ctrl+Shift+I        │             │
│         │   ⊞   Toggle Compare Mode                                              Ctrl+Shift+C        │             │
│         │   🗑   Clear All Debug Entries                                                              │             │
│         │   ⚙   Open Settings                                                                        │             │
│         │                                                                                             │             │
│         └─────────────────────────────────────────────────────────────────────────────────────────────┘             │
│                                                                                                                    │
│                                                                                                                    │
│   ░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░   │
│   ░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░ backdrop (dark overlay) ░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░   │
│   ░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░   │
│                                                                                                                    │
└──────────────────────────────────────────────────────────────────────────────────────────────────────────────────────┘
```

## Searching — Debug Entries

Typing a URL fragment, method, or status code searches debug entries:

```
         ┌─────────────────────────────────────────────────────────────────────────────────────────────┐
         │                                                                                             │
         │  🔍 [/api/users________________________________________________]        [Esc to close]   │
         │                                                                                             │
         │  ── Debug Entries (4 matches) ──────────────────────────────────────────────────────────────  │
         │                                                                                             │
         │  ▸ GET   200  /api/users?page=2&limit=25                87ms   14:23:07                    │
         │    POST  201  /api/users                               145ms   14:22:58                    │
         │    GET   200  /api/users/42/profile                     52ms   14:22:41                    │
         │    GET   404  /api/users/999                            23ms   14:22:30                    │
         │                                                                                             │
         │  ── Pages ──────────────────────────────────────────────────────────────────────────────────  │
         │                                                                                             │
         │    (no page matches)                                                                        │
         │                                                                                             │
         │  ── Actions ────────────────────────────────────────────────────────────────────────────────  │
         │                                                                                             │
         │    (no action matches)                                                                      │
         │                                                                                             │
         │                                                              ▲/▼ navigate   Enter select   │
         │                                                                                             │
         └─────────────────────────────────────────────────────────────────────────────────────────────┘
```

## Searching — Pages

Typing a page name navigates to that page:

```
         ┌─────────────────────────────────────────────────────────────────────────────────────────────┐
         │                                                                                             │
         │  🔍 [config___________________________________________________]        [Esc to close]     │
         │                                                                                             │
         │  ── Pages (2 matches) ──────────────────────────────────────────────────────────────────────  │
         │                                                                                             │
         │  ▸ 🔧  Inspector > Configuration                                                            │
         │    🔧  Inspector > Opcache Configuration                                                    │
         │                                                                                             │
         │  ── Debug Entries ──────────────────────────────────────────────────────────────────────────  │
         │                                                                                             │
         │    (no matches)                                                                             │
         │                                                                                             │
         │  ── Actions ────────────────────────────────────────────────────────────────────────────────  │
         │                                                                                             │
         │    (no matches)                                                                             │
         │                                                                                             │
         └─────────────────────────────────────────────────────────────────────────────────────────────┘
```

## Searching — Actions (prefix: >)

Typing `>` switches to action mode:

```
         ┌─────────────────────────────────────────────────────────────────────────────────────────────┐
         │                                                                                             │
         │  🔍 [> clear_________________________________________________]          [Esc to close]    │
         │                                                                                             │
         │  ── Actions (2 matches) ────────────────────────────────────────────────────────────────────  │
         │                                                                                             │
         │  ▸ 🗑   Clear All Debug Entries                                                              │
         │    🗑   Clear Cache                                                                          │
         │                                                                                             │
         └─────────────────────────────────────────────────────────────────────────────────────────────┘
```

## Searching — Collector Tabs (prefix: #)

Typing `#` searches collector tabs within the current debug entry:

```
         ┌─────────────────────────────────────────────────────────────────────────────────────────────┐
         │                                                                                             │
         │  🔍 [# db____________________________________________________]          [Esc to close]    │
         │                                                                                             │
         │  ── Collectors (1 match) ───────────────────────────────────────────────────────────────────  │
         │                                                                                             │
         │  ▸ 💾  DB Queries (8)                                                     Shortcut: 5      │
         │                                                                                             │
         └─────────────────────────────────────────────────────────────────────────────────────────────┘
```

## Complete Action List

All available actions (shown with `>` prefix):

```
  ── Navigation ──────────────────────────────
  Go to Debug                    Ctrl+1
  Go to Debug List               Ctrl+2
  Go to Inspector > Config       Ctrl+3
  Go to Inspector > Events
  Go to Inspector > Routes
  Go to Inspector > Commands
  Go to Inspector > Database
  Go to Inspector > Files
  Go to Inspector > Git
  Go to Inspector > PHP Info
  Go to Inspector > Composer
  Go to Inspector > Opcache
  Go to Inspector > Cache
  Go to Inspector > Tests
  Go to Inspector > Analyse
  Go to Inspector > Translations
  Go to OpenAPI
  Go to Frames
  Go to Gii

  ── Debug Actions ───────────────────────────
  Previous Entry                 Ctrl+[
  Next Entry                     Ctrl+]
  Toggle Compare Mode            Ctrl+Shift+C
  Select Entry for Compare...

  ── Data Actions ────────────────────────────
  Clear All Debug Entries
  Clear Cache
  Refresh Current Data           Ctrl+R
  Export Current View as CSV
  Export Current View as JSON

  ── UI Actions ──────────────────────────────
  Toggle Table Density           Ctrl+Shift+D
  Toggle Theme (Dark/Light)
  Open Settings
  Toggle SSE Connection
```

## Keyboard Navigation

```
  ▲ / ▼           Move selection up/down
  Enter            Execute selected item
  Escape           Close palette
  Tab              Cycle through result groups
  Ctrl+Backspace   Clear search input
```

## Visual Design Notes

- Palette appears centered, 25% from top of viewport
- Width: 640px (fixed)
- Max height: 480px (scrollable results)
- Backdrop: semi-transparent black overlay (`rgba(0,0,0,0.5)`)
- Animation: 100ms fade-in for backdrop, 100ms scale-up for palette
- Input auto-focused on open
- Results update on every keystroke (no debounce — local filtering)
- Fuzzy matching: "dbq" matches "DB Queries", "insconf" matches "Inspector > Configuration"
- Match score ranking: exact match > prefix match > fuzzy match
- Each result shows an icon, label, and optional keyboard shortcut on the right

## State Management

| State                | Storage      | Rationale                                |
|----------------------|-------------|------------------------------------------|
| Palette open/closed  | Local state | Transient UI overlay                     |
| Search query         | Local state | Cleared on close                         |
| Selected result idx  | Local state | Keyboard navigation position             |
| Recent items         | localStorage| Persists across sessions                 |
| Available actions    | Redux       | Derived from current page + permissions  |
