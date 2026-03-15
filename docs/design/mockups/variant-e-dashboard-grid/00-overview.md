# Variant E: Dashboard Grid — Overview

## Design Philosophy

Dashboard Grid transforms the debug panel into a widget-based workspace inspired by Grafana, Kibana,
and Azure DevOps. Every collector and inspector becomes a self-contained widget that users arrange on
a CSS Grid canvas. Multiple dashboards support different workflows: debugging requests, inspecting
configuration, monitoring performance, or custom compositions.

## Core Concepts

```
 DASHBOARD GRID ARCHITECTURE
 ============================

 ┌─ Shell ─────────────────────────────────────────────────────────────────────────────────────────────────────────┐
 │  ┌─ Header ───────────────────────────────────────────────────────────────────────────────────────────────────┐ │
 │  │  [Entry Selector]          [Dashboard Tabs: Debug | Inspector | Custom 1 | +]          [Settings] [Help]  │ │
 │  └────────────────────────────────────────────────────────────────────────────────────────────────────────────┘ │
 │  ┌─ Grid Canvas ──────────────────────────────────────────────────────────────────────────────────────────────┐ │
 │  │                                                                                                           │ │
 │  │   ┌─ Widget ─────┐  ┌─ Widget ─────┐  ┌─ Widget ──────────────────────┐                                  │ │
 │  │   │              │  │              │  │                               │                                  │ │
 │  │   │   Content    │  │   Content    │  │         Content               │                                  │ │
 │  │   │              │  │              │  │                               │                                  │ │
 │  │   └──────────────┘  └──────────────┘  └───────────────────────────────┘                                  │ │
 │  │   ┌─ Widget ────────────────────────┐  ┌─ Widget ─────────────────────┐                                  │ │
 │  │   │                                │  │                              │                                  │ │
 │  │   │         Content                │  │         Content              │                                  │ │
 │  │   │                                │  │                              │                                  │ │
 │  │   └────────────────────────────────┘  └──────────────────────────────┘                                  │ │
 │  │                                                                                                           │ │
 │  └───────────────────────────────────────────────────────────────────────────────────────────────────────────┘ │
 └─────────────────────────────────────────────────────────────────────────────────────────────────────────────────┘
```

## Widget Types

| Type     | Purpose                         | Example Collectors                        |
|----------|---------------------------------|-------------------------------------------|
| Status   | Key-value summary cards         | Request Info, Response, PHP Info           |
| Table    | Sortable, filterable data grids | DB Queries, Events, Service Container      |
| Log      | Scrolling log stream            | Application Logs, Profiler Output          |
| Chart    | Bar, line, pie visualizations   | Memory Usage, Timeline, Query Duration     |
| JSON     | Collapsible JSON tree viewer    | Request Body, Config Dump, Container Dump  |
| Timeline | Horizontal bar timeline         | Request Lifecycle, Event Sequence           |

## Grid System

The canvas uses a 12-column CSS Grid. Each widget occupies a rectangular area defined by column span
and row span. Minimum widget size is 3 columns x 1 row. Maximum is 12 columns x any rows.

```
 12-COLUMN GRID
 ════════════════════════════════════════════════════════════════════════════════════════════════════════════════

  1    2    3    4    5    6    7    8    9    10   11   12
  ├────┼────┼────┼────┼────┼────┼────┼────┼────┼────┼────┼────┤

  ┌─── 3 cols ───┐  ┌──── 4 cols ────┐  ┌──────── 5 cols ────────┐
  │   Status     │  │   Table        │  │   Timeline             │
  │   (3x1)      │  │   (4x2)        │  │   (5x1)                │
  └──────────────┘  │                │  └────────────────────────┘
                    │                │  ┌──────── 5 cols ────────┐
                    └────────────────┘  │   Logs                 │
                                        │   (5x3)                │
  ┌─────────────────────── 6 cols ──────────────────────┐        │
  │   Chart (6x1)                                       │        │
  └─────────────────────────────────────────────────────┘        │
                                                                  └────────────────────────┘
  ┌──────────────────────────────── 12 cols (full width) ────────────────────────────────────────────────────┐
  │   Events Table (12x2)                                                                                    │
  └──────────────────────────────────────────────────────────────────────────────────────────────────────────┘
```

## Widget Anatomy

Every widget follows a consistent structure:

```
 ┌══ Widget Title ═══════════════════════════════════════════════════════════════════── ─  □  ✕ ──┐
 │                                                                                               │
 │   Widget content area                                                                         │
 │   - Varies by widget type                                                                     │
 │   - Scrollable when content overflows                                                         │
 │   - Supports loading/error/empty states                                                       │
 │                                                                                               │
 ├───────────────────────────────────────────────────────────────────────────────────────────────┤░
 │   Optional footer: pagination, totals, filters                                               │░
 └───────────────────────────────────────────────────────────────────────────────────────────────┘░
  ░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░
                                                                                        ↑ resize handle
```

Title bar controls:
- `══` left of title: drag handle (grab to reposition)
- `─` minimize: collapse widget to title bar only
- `□` maximize: expand widget to full screen (Escape to return)
- `✕` close: remove widget from dashboard

## Dashboard Persistence

Layouts are saved as JSON and stored in browser localStorage. Users can:
- Create new dashboards from blank or template
- Rename, duplicate, delete dashboards
- Export/import layout JSON
- Reset to default layout

## Interaction Model

| Action                  | Trigger                                      |
|-------------------------|----------------------------------------------|
| Move widget             | Drag title bar                               |
| Resize widget           | Drag bottom-right corner handle              |
| Maximize widget         | Click □ or double-click title bar             |
| Restore from maximize   | Press Escape or click □ again                 |
| Minimize widget         | Click ─ (collapses to title-bar-only strip)   |
| Close widget            | Click ✕ (removed from grid, can re-add)       |
| Add widget              | Toolbar "+" button opens widget picker         |
| Switch dashboard        | Click dashboard tab                           |
| Edit layout             | Click pencil icon to enter edit mode           |
