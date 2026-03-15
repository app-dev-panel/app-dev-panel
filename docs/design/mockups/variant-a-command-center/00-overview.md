# Variant A: Command Center — Design Overview

## Design Philosophy

"Command Center" draws from professional IDE debuggers (Chrome DevTools, PhpStorm, VS Code).
The goal is **maximum information density** with **zero navigation overhead**. Every pixel earns
its place. The interface assumes a power user who wants all data visible simultaneously.

Core metaphor: a mission control dashboard where the operator monitors a running system.

## Layout Model

```
┌──────────────────────────────────────────────────────────────────────────────────────────────────────────────────────┐
│ CONTEXT BAR (48px)  — current debug entry summary, always visible                                                  │
├────┬─────────────────────────────────────────────────────────────────────────────────────────────────────────────────┤
│    │ TAB BAR (40px) — collector/page tabs                                                                          │
│ S  ├─────────────────────────────────────────────────────────────────────────────────────────────────────────────────┤
│ I  │                                                                                                               │
│ D  │                                                                                                               │
│ E  │ MAIN CONTENT AREA                                                                                             │
│ B  │ (flex, scrollable)                                                                                            │
│ A  │                                                                                                               │
│ R  │                                                                                                               │
│    │                                                                                                               │
│ 48 │                                                                                                               │
│ px │                                                                                                               │
│    │                                                                                                               │
├────┴─────────────────────────────────────────────────────────────────────────────────────────────────────────────────┤
│ STATUS BAR (24px) — last action, SSE indicator, version                                                            │
└──────────────────────────────────────────────────────────────────────────────────────────────────────────────────────┘
```

## Color System

### Surface Colors (dark theme — default)

| Token                | Value       | Usage                                    |
|----------------------|-------------|------------------------------------------|
| `--bg-base`          | `#0d1117`   | App background                           |
| `--bg-surface`       | `#161b22`   | Cards, panels                            |
| `--bg-overlay`       | `#1c2128`   | Dropdowns, popovers, command palette     |
| `--bg-inset`         | `#010409`   | Inset areas (code blocks, table stripes) |
| `--border-default`   | `#30363d`   | Borders, dividers                        |
| `--border-muted`     | `#21262d`   | Subtle dividers                          |

### Semantic Colors

| Token                | Value       | Usage                                    |
|----------------------|-------------|------------------------------------------|
| `--accent-primary`   | `#58a6ff`   | Links, active tabs, focus rings          |
| `--accent-success`   | `#3fb950`   | 2xx status, pass indicators              |
| `--accent-warning`   | `#d29922`   | 3xx status, slow queries                 |
| `--accent-danger`    | `#f85149`   | 4xx/5xx status, errors, exceptions       |
| `--accent-info`      | `#79c0ff`   | Informational badges                     |

### HTTP Method Colors

| Method   | Background | Text      |
|----------|------------|-----------|
| GET      | `#1f3a2a`  | `#3fb950` |
| POST     | `#2a1f3a`  | `#bc8cff` |
| PUT      | `#3a2a1f`  | `#d29922` |
| DELETE   | `#3a1f1f`  | `#f85149` |
| PATCH    | `#1f2a3a`  | `#58a6ff` |

### Status Code Colors

| Range | Color        |
|-------|--------------|
| 2xx   | `--accent-success` |
| 3xx   | `--accent-warning` |
| 4xx   | `--accent-danger`  |
| 5xx   | `--accent-danger` (bold)  |

## Typography

| Element          | Font                        | Size  | Weight | Line Height |
|------------------|-----------------------------|-------|--------|-------------|
| Context bar text | `Inter, system-ui`          | 13px  | 500    | 1.2         |
| Tab labels       | `Inter, system-ui`          | 12px  | 500    | 1.2         |
| Table headers    | `Inter, system-ui`          | 11px  | 600    | 1.2         |
| Table cells      | `JetBrains Mono, monospace` | 12px  | 400    | 1.4         |
| Status bar       | `JetBrains Mono, monospace` | 11px  | 400    | 1.0         |
| Code blocks      | `JetBrains Mono, monospace` | 13px  | 400    | 1.5         |
| Badges           | `Inter, system-ui`          | 10px  | 700    | 1.0         |

## Spacing Scale

Uses a 4px base unit:

| Token  | Value | Usage                          |
|--------|-------|--------------------------------|
| `xs`   | 4px   | Inline padding, badge padding  |
| `sm`   | 8px   | Table cell padding, gap        |
| `md`   | 12px  | Section padding                |
| `lg`   | 16px  | Card padding, panel margins    |
| `xl`   | 24px  | Page margins                   |
| `2xl`  | 32px  | Section gaps                   |

## Z-Index Layers

| Layer            | z-index |
|------------------|---------|
| Status bar       | 100     |
| Context bar      | 200     |
| Sidebar          | 300     |
| Sidebar expanded | 350     |
| Dropdown         | 400     |
| Command palette  | 500     |
| Modal            | 600     |
| Toast            | 700     |

## Iconography

- Icon set: Material Icons (already in use via MUI)
- Sidebar icons: 24px, outlined variant
- Inline icons: 16px, filled variant
- Monochrome, inherits text color

## Responsive Behavior

| Breakpoint | Sidebar        | Context bar          | Status bar    |
|------------|----------------|----------------------|---------------|
| >= 1280px  | Rail (48px)    | Full                 | Full          |
| 960-1279px | Rail (48px)    | Condensed (no time)  | Condensed     |
| < 960px    | Hidden (drawer)| Stacked              | Hidden        |

The primary target is >= 1280px (developer workstation).

## Animation

- Sidebar expand: 150ms ease-out
- Tab switch: instant (no transition)
- Command palette: 100ms fade-in
- Dropdown: 100ms scale-y from top
- Toast: 200ms slide-in from bottom-right

## Keyboard Shortcuts

| Shortcut        | Action                          |
|-----------------|---------------------------------|
| `Ctrl+K`        | Open command palette            |
| `Ctrl+[`        | Previous debug entry            |
| `Ctrl+]`        | Next debug entry                |
| `Ctrl+Shift+C`  | Toggle compare mode             |
| `1-9`           | Switch to collector tab N       |
| `Ctrl+Shift+D`  | Toggle density (table)          |
| `Escape`        | Close overlay / exit compare    |
