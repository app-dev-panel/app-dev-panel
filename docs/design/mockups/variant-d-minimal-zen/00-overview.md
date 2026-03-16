# Variant D: Minimal Zen — Overview

## Philosophy

Inspired by Linear, Raycast, and Arc browser. The core idea: **remove everything that is not
essential, then remove a little more.** Navigation lives in a command palette (Ctrl+K), not a
sidebar. Content gets the full viewport width. The user focuses on one thing at a time.

## Design Principles

1. **No persistent sidebar** — Ctrl+K command palette is the single entry point for all navigation.
2. **Full-width content** — Every pixel of horizontal space belongs to the content area.
3. **Tiny top bar** — Logo, entry selector pill, and a few action icons. Nothing else.
4. **Cards over tables** — Dashboard-level views use cards. Detail views use clean tables.
5. **Progressive disclosure** — Start with a summary. Click to expand. Drill to reveal.
6. **Inline everything** — Edit, filter, and act without leaving the current context.
7. **One column** — Vertical scroll, not horizontal splits.
8. **Animated transitions** — Slide and fade between views for spatial orientation.

## Spacing System

```
Token        px     rem     Usage
─────────────────────────────────────────────────────────────
space-xs      4    0.25     Tight gaps inside components (icon-to-label)
space-sm      8    0.50     Intra-card padding, badge margins
space-md     16    1.00     Card internal padding, form field gaps
space-lg     24    1.50     Between cards in a grid, section gaps
space-xl     32    2.00     Page top/bottom padding
space-2xl    48    3.00     Hero spacing, empty-state breathing room
space-3xl    64    4.00     Maximum whitespace (above page titles)
```

## Progressive Disclosure Model

```
Level 0: Command Palette (Ctrl+K)
  │
  ├── Level 1: Dashboard — Cards grid (icon + name + count + sparkline)
  │     │
  │     └── Level 2: Expanded Card — Inline detail panel replaces card row
  │           │
  │           └── Level 3: Full Detail — Full-width page with all data + actions
  │
  └── Level 1: Inspector — Searchable card list (config, routes, database, etc.)
        │
        └── Level 2: Inspector Detail — Full-width, inline editable
```

### Visual Model

```
┌────────────────────────────────────────────────────────────────────────────────────────────────────────────────────┐
│                                                                                                                    │
│  Level 0: Command Palette                                                                                          │
│  ┌──────────────────────────────────────────┐                                                                      │
│  │ > search...                              │                                                                      │
│  │ Recent | Pages | Actions                 │                                                                      │
│  └──────────────────────────────────────────┘                                                                      │
│       │                                                                                                            │
│       ▼                                                                                                            │
│  Level 1: Dashboard (Cards Grid)                                                                                   │
│  ┌──────────┐  ┌──────────┐  ┌──────────┐                                                                         │
│  │ Request  │  │   Log    │  │  Event   │                                                                          │
│  │  1 req   │  │ 12 msgs  │  │ 8 fired  │                                                                         │
│  └──────────┘  └──────────┘  └──────────┘                                                                         │
│       │                                                                                                            │
│       ▼                                                                                                            │
│  Level 2: Expanded Card (Inline)                                                                                   │
│  ┌──────────────────────────────────────────────────────────────────────────────────────┐                           │
│  │ Request Collector                                                                [X]│                           │
│  │ GET /api/users  200  143ms  1.2KB                                                   │                           │
│  │ Headers | Body | Timing                                                              │                           │
│  └──────────────────────────────────────────────────────────────────────────────────────┘                           │
│       │                                                                                                            │
│       ▼                                                                                                            │
│  Level 3: Full Detail Page                                                                                         │
│  ┌──────────────────────────────────────────────────────────────────────────────────────────────────────────────┐   │
│  │ GET /api/users                                                                                              │   │
│  │                                                                                                              │   │
│  │ Request  Response  Headers  Timing  cURL                                                                     │   │
│  │ ─────────────────────────────────────────────────────────────                                                 │   │
│  │ Full content with all data, inline actions, copy buttons...                                                  │   │
│  └──────────────────────────────────────────────────────────────────────────────────────────────────────────────┘   │
│                                                                                                                    │
└────────────────────────────────────────────────────────────────────────────────────────────────────────────────────┘
```

## Color Palette

```
Role              Light Mode          Dark Mode
───────────────────────────────────────────────────────
Background        #FFFFFF             #0A0A0A
Surface           #FAFAFA             #141414
Border            #E5E5E5             #262626
Text primary      #171717             #EDEDED
Text secondary    #737373             #A3A3A3
Accent            #2563EB             #3B82F6
Success           #16A34A             #22C55E
Warning           #D97706             #F59E0B
Error             #DC2626             #EF4444
```

## Typography

```
Token          Size    Weight    Usage
──────────────────────────────────────────────────────
heading-xl     24px    600       Page titles
heading-lg     18px    600       Section headers
heading-md     15px    600       Card titles
body           14px    400       Default text
body-sm        13px    400       Secondary info
mono           13px    400       Code, values, paths
caption        12px    400       Timestamps, badges
```

## Key Interactions

| Shortcut         | Action                                      |
|------------------|---------------------------------------------|
| Ctrl+K           | Open command palette                        |
| Ctrl+D           | Go to Debug dashboard                       |
| Ctrl+I, C        | Go to Inspector Config                      |
| Ctrl+I, D        | Go to Inspector Database                    |
| Ctrl+R           | Repeat last request                         |
| Ctrl+Shift+C     | Copy cURL for current entry                 |
| Ctrl+E           | Export current entry                        |
| Ctrl+Shift+T     | Toggle dark/light mode                      |
| Escape           | Close overlay / collapse expanded card      |
| J / K            | Navigate items (vim-style)                  |
| Enter            | Open / expand selected item                 |
