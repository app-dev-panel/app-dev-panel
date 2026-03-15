# Variant D: Minimal Zen — Command Palette

## Concept

The command palette is the heart of Minimal Zen navigation. Activated by Ctrl+K (or clicking the
search trigger in the top bar), it appears as a centered overlay with a backdrop blur. It replaces
all traditional navigation — sidebar, tabs, breadcrumbs. Type to search pages, actions, entries,
and collector data. Everything is reachable in 2 keystrokes.

## Default State — Just Opened (Ctrl+K)

```
┌────────────────────────────────────────────────────────────────────────────────────────────────────────────────────┐
│                                                                                                                    │
│                                                                                                                    │
│                                                                                                                    │
│              ┌─────────────────────────────────────────────────────────────────────────────────────┐                │
│              │                                                                                     │                │
│              │  >  Search pages, actions, entries…                                                 │                │
│              │                                                                                     │                │
│              ├─────────────────────────────────────────────────────────────────────────────────────┤                │
│              │                                                                                     │                │
│              │  Recent                                                                             │                │
│              │                                                                                     │                │
│              │    ⬡  Debug  ›  Request Collector                                     Ctrl+D       │                │
│              │    ⚙  Inspector  ›  Configuration                                     Ctrl+I, C    │                │
│              │    ⛁  Inspector  ›  Database                                          Ctrl+I, D    │                │
│              │                                                                                     │                │
│              │  Pages                                                                              │                │
│              │                                                                                     │                │
│              │    ⬡  Debug                                                           /debug        │                │
│              │    ⚙  Inspector  ›  Configuration                                     /config       │                │
│              │    🔀 Inspector  ›  Routes                                             /routes       │                │
│              │    ⛁  Inspector  ›  Database                                          /database     │                │
│              │    📦 Inspector  ›  Container                                          /container    │                │
│              │    🌐 Inspector  ›  Translations                                       /i18n         │                │
│              │                                                                                     │                │
│              │  Actions                                                                            │                │
│              │                                                                                     │                │
│              │    ↻  Repeat Request                                                  Ctrl+R        │                │
│              │    ⎘  Copy cURL                                                       Ctrl+Shift+C  │                │
│              │    ⇄  Compare Entries                                                 Ctrl+Shift+D  │                │
│              │    ↓  Export Entry                                                    Ctrl+E        │                │
│              │    ☀  Toggle Dark Mode                                                Ctrl+Shift+T  │                │
│              │                                                                                     │                │
│              └─────────────────────────────────────────────────────────────────────────────────────┘                │
│                                                                                                                    │
│                                                                                                                    │
│                                                                                                                    │
└────────────────────────────────────────────────────────────────────────────────────────────────────────────────────┘
```

## Searching — Filtered Results

When the user types, results are filtered in real time. Categories with no matches are hidden.

### Typing "req"

```
              ┌─────────────────────────────────────────────────────────────────────────────────────┐
              │                                                                                     │
              │  >  req▎                                                                            │
              │                                                                                     │
              ├─────────────────────────────────────────────────────────────────────────────────────┤
              │                                                                                     │
              │  Pages                                                                              │
              │                                                                                     │
              │  ● ⬡  Debug  ›  Request Collector                                     Ctrl+D       │
              │                                                                                     │
              │  Actions                                                                            │
              │                                                                                     │
              │    ↻  Repeat Request                                                  Ctrl+R        │
              │                                                                                     │
              │  Entries                                                                            │
              │                                                                                     │
              │    GET  /api/users              200   143ms   14:23:01                               │
              │    POST /api/users              201    89ms   14:22:58                               │
              │    GET  /api/users/42           200   112ms   14:22:45                               │
              │    POST /api/orders             500   342ms   14:22:12                               │
              │                                                                                     │
              └─────────────────────────────────────────────────────────────────────────────────────┘
```

### Typing "500" — Searching by Status Code

```
              ┌─────────────────────────────────────────────────────────────────────────────────────┐
              │                                                                                     │
              │  >  500▎                                                                            │
              │                                                                                     │
              ├─────────────────────────────────────────────────────────────────────────────────────┤
              │                                                                                     │
              │  Entries                                                                            │
              │                                                                                     │
              │  ● POST /api/orders             500   342ms   14:22:12                              │
              │                                                                                     │
              │  No matching pages or actions.                                                      │
              │                                                                                     │
              └─────────────────────────────────────────────────────────────────────────────────────┘
```

### Typing ">" — Action Mode

Prefixing with `>` enters action-only mode — like VS Code's command palette.

```
              ┌─────────────────────────────────────────────────────────────────────────────────────┐
              │                                                                                     │
              │  >  > ▎                                                                             │
              │                                                                                     │
              ├─────────────────────────────────────────────────────────────────────────────────────┤
              │                                                                                     │
              │  Actions                                                                            │
              │                                                                                     │
              │  ● ↻  Repeat Request                                                  Ctrl+R        │
              │    ⎘  Copy cURL                                                       Ctrl+Shift+C  │
              │    ⇄  Compare Entries                                                 Ctrl+Shift+D  │
              │    ↓  Export Entry                                                    Ctrl+E        │
              │    ☀  Toggle Dark Mode                                                Ctrl+Shift+T  │
              │    🗑  Clear All Entries                                                             │
              │    ↻  Reset Collectors                                                              │
              │    ⚙  Open Settings                                                                 │
              │                                                                                     │
              └─────────────────────────────────────────────────────────────────────────────────────┘
```

## Keyboard Navigation

```
              ┌─────────────────────────────────────────────────────────────────────────────────────┐
              │  >  req▎                                                                            │
              ├─────────────────────────────────────────────────────────────────────────────────────┤
              │                                                                                     │
              │  Pages                                                                              │
              │                                                                                     │
              │  ● ⬡  Debug  ›  Request Collector                                     Ctrl+D       │
              │      ▲                                                                              │
              │      │  ↑/↓ arrows move the selection dot (●)                                       │
              │      │  Enter activates the selected item                                           │
              │      │  Escape closes the palette                                                   │
              │      │  Tab cycles between sections (Recent/Pages/Actions)                          │
              │                                                                                     │
              └─────────────────────────────────────────────────────────────────────────────────────┘
```

## No Results

```
              ┌─────────────────────────────────────────────────────────────────────────────────────┐
              │                                                                                     │
              │  >  xyzzy▎                                                                          │
              │                                                                                     │
              ├─────────────────────────────────────────────────────────────────────────────────────┤
              │                                                                                     │
              │                                                                                     │
              │                      No results for "xyzzy"                                         │
              │                                                                                     │
              │                      Try a different search term, or                                 │
              │                      press Escape to close.                                         │
              │                                                                                     │
              │                                                                                     │
              └─────────────────────────────────────────────────────────────────────────────────────┘
```

## Visual Specifications

```
Overlay:
  - Backdrop: rgba(0,0,0,0.5) with backdrop-filter: blur(4px)
  - Palette width: 640px (fixed), centered
  - Border-radius: 12px
  - Shadow: 0 25px 50px -12px rgba(0,0,0,0.25)
  - Animation: fade in 150ms + scale from 0.95 to 1.0

Input field:
  - Height: 48px
  - Font: 16px/400 (slightly larger than body for focus)
  - No border — integrated into palette header
  - Auto-focused on open

Result items:
  - Height: 36px
  - Hover: background shifts to surface color
  - Selected (●): accent color left marker + background tint
  - Icon: 16px, secondary color
  - Label: body (14px), primary color
  - Shortcut: mono (13px), secondary color, right-aligned

Section headers:
  - Font: caption (12px/400), uppercase, secondary color
  - Margin-top: 12px, margin-bottom: 4px
```

## Palette in Dark Mode

```
              ┌─────────────────────────────────────────────────────────────────────────────────────┐
              │░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░│
              │░ >  Search pages, actions, entries…                                               ░│
              │░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░│
              ├─────────────────────────────────────────────────────────────────────────────────────┤
              │                                                                                     │
              │  Recent                                                     (#141414 background)    │
              │                                                             (#EDEDED text)          │
              │    ⬡  Debug  ›  Request Collector                           (#A3A3A3 secondary)     │
              │    ⚙  Inspector  ›  Configuration                           (#262626 borders)       │
              │    ⛁  Inspector  ›  Database                                (#3B82F6 accent)        │
              │                                                                                     │
              └─────────────────────────────────────────────────────────────────────────────────────┘
```
