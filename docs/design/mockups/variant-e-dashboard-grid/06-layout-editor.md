# Variant E: Dashboard Grid — Layout Editor

## Overview

The layout editor allows users to add, remove, resize, and rearrange widgets on a dashboard.
Activated by clicking the pencil (edit) icon in the toolbar. The grid shows visible guides and
drop zones. Widgets gain visible resize handles and a delete button.

## Edit Mode Active

```
┌────────────────────────────────────────────────────────────────────────────────────────────────────────────────┐
│  ADP   ◀ ▶  GET /api/users ▾  #a3f7c1  2026-03-15 14:32:07          200 OK 145ms         + ✎ ⚙              │
│  ┌─────────┐ ┌───────────┐                                                                                   │
│  │ Debug   │ │ Inspector │  +                                                                                 │
│  │ ▀▀▀▀▀▀▀ │ │           │                                                                                   │
├──────────────────────────────────── EDITING LAYOUT ────────────────────────── [ Save ]  [ Cancel ] ────────────┤
│                                                                                                                │
│  ┌─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ┐   │
│  :  1    2    3    4    5    6    7    8    9    10   11   12                                          :   │
│  :  ┌══ Request Info ════ ✕ ┐  ┌══ Response ═════ ✕ ┐  ┌══ Timeline ═══════════════════════ ✕ ┐     :   │
│  :  │                       │  │                     │  │                                      │     :   │
│  :  │  GET /api/users       │  │  200 OK             │  │  ████░░░░████░░░░████████░░░████    │     :   │
│  :  │  145ms  12.4 MB       │  │  application/json   │  │                                      │     :   │
│  :  │                       │  │  2.4 KB             │  │                                      │     :   │
│  :  │                      ◢│  │                    ◢│  │                                     ◢│     :   │
│  :  └───────────────────────┘  └─────────────────────┘  └──────────────────────────────────────┘     :   │
│  :                                                                                                    :   │
│  :  ┌══ DB Queries (8) ═══════════════════════ ✕ ┐  ┌══ Logs (23) ══════════════════════ ✕ ┐        :   │
│  :  │                                            │  │                                       │        :   │
│  :  │  # Query              Time    Rows         │  │  14:32:07  INFO  App booted           │        :   │
│  :  │  1 SELECT * FROM...   12.3ms  42           │  │  14:32:07  DEBUG Route matched        │        :   │
│  :  │  2 SELECT * FROM...   8.7ms   5            │  │  14:32:07  INFO  Action start         │        :   │
│  :  │  3 SELECT * FROM...   5.2ms   12           │  │  14:32:07  DEBUG DB connect           │        :   │
│  :  │  4 INSERT INTO...     5.7ms   1            │  │  14:32:07  DEBUG Query executed        │        :   │
│  :  │                                            │  │                                       │        :   │
│  :  │                                           ◢│  │                                      ◢│        :   │
│  :  └────────────────────────────────────────────┘  └───────────────────────────────────────┘        :   │
│  :                                                                                                    :   │
│  :  ┌══ Events (12) ═══════════════════════════════════════════════════════════════════ ✕ ┐           :   │
│  :  │                                                                                     │           :   │
│  :  │  # Event                         Listeners  Time                                    │           :   │
│  :  │  1 BeforeRequest                 3          0.8ms                                   │           :   │
│  :  │  2 RouteMatched                  2          0.3ms                                   │           :   │
│  :  │                                                                                    ◢│           :   │
│  :  └─────────────────────────────────────────────────────────────────────────────────────┘           :   │
│  :                                                                                                    :   │
│  :  ┌ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ┐      :   │
│  :  :                                                                                        :      :   │
│  :  :                     + Drop widget here or click + to add                               :      :   │
│  :  :                                                                                        :      :   │
│  :  └ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ┘      :   │
│  └ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ┘   │
│                                                                                                                │
└────────────────────────────────────────────────────────────────────────────────────────────────────────────────┘
```

Key visual differences in edit mode:
- `◢` resize handle visible in bottom-right corner of each widget
- `✕` close button prominent on each widget
- Dashed border around the grid canvas
- Column guides visible at top
- Drop zone at bottom for adding new widgets
- "EDITING LAYOUT" banner with Save and Cancel buttons

## Widget Picker (Click + Button)

```
┌── Add Widget ─────────────────────────────────────────────────────────────────────────────────┐
│                                                                                               │
│  🔍 Search widgets...                                                                        │
│                                                                                               │
│  COLLECTORS                                                                                   │
│  ┌──────────────────┐  ┌──────────────────┐  ┌──────────────────┐  ┌──────────────────┐      │
│  │  Request Info     │  │  Response         │  │  DB Queries      │  │  Logs             │      │
│  │  Status Card      │  │  Status Card      │  │  Table           │  │  Log Stream       │      │
│  │  ☑ on dashboard   │  │  ☑ on dashboard   │  │  ☑ on dashboard  │  │  ☑ on dashboard   │      │
│  └──────────────────┘  └──────────────────┘  └──────────────────┘  └──────────────────┘      │
│  ┌──────────────────┐  ┌──────────────────┐  ┌──────────────────┐  ┌──────────────────┐      │
│  │  Events           │  │  Timeline         │  │  Exception       │  │  HTTP Client      │      │
│  │  Table            │  │  Timeline         │  │  JSON Tree       │  │  Table            │      │
│  │  ☑ on dashboard   │  │  ☑ on dashboard   │  │  ☐ not added     │  │  ☐ not added      │      │
│  └──────────────────┘  └──────────────────┘  └──────────────────┘  └──────────────────┘      │
│  ┌──────────────────┐  ┌──────────────────┐  ┌──────────────────┐  ┌──────────────────┐      │
│  │  Service Actions  │  │  Middleware       │  │  Memory Profile  │  │  Mailer           │      │
│  │  Table            │  │  Table            │  │  Chart           │  │  Table            │      │
│  │  ☐ not added      │  │  ☐ not added      │  │  ☐ not added     │  │  ☐ not added      │      │
│  └──────────────────┘  └──────────────────┘  └──────────────────┘  └──────────────────┘      │
│                                                                                               │
│  INSPECTOR                                                                                    │
│  ┌──────────────────┐  ┌──────────────────┐  ┌──────────────────┐  ┌──────────────────┐      │
│  │  Routes           │  │  Container        │  │  Commands         │  │  Configuration    │      │
│  │  Table            │  │  Table            │  │  Table            │  │  JSON Tree        │      │
│  │  ☐ not added      │  │  ☐ not added      │  │  ☐ not added      │  │  ☐ not added      │      │
│  └──────────────────┘  └──────────────────┘  └──────────────────┘  └──────────────────┘      │
│                                                                                               │
│  VISUALIZATIONS                                                                               │
│  ┌──────────────────┐  ┌──────────────────┐  ┌──────────────────┐                             │
│  │  Query Duration   │  │  Log Levels       │  │  Memory Usage    │                             │
│  │  Bar Chart        │  │  Pie Chart        │  │  Line Chart      │                             │
│  │  ☐ not added      │  │  ☐ not added      │  │  ☐ not added     │                             │
│  └──────────────────┘  └──────────────────┘  └──────────────────┘                             │
│                                                                                               │
│                                                                         [ Cancel ]            │
└───────────────────────────────────────────────────────────────────────────────────────────────┘
```

## Widget Resize Interaction

Dragging the `◢` handle snaps to grid column/row boundaries:

```
  BEFORE RESIZE                               DURING RESIZE                            AFTER RESIZE

  ┌══ Logs ══════ ✕ ┐                        ┌══ Logs ══════════════════ ✕ ┐           ┌══ Logs ══════════════════ ✕ ┐
  │                  │                        │                             │           │                             │
  │  log entries     │  ──── drag ◢ ────▶    │  log entries                │           │  log entries                │
  │                  │     to the right       │                             │           │  more visible rows          │
  │                 ◢│                        │                            ◢│  release  │  even more entries          │
  └──────────────────┘                        └─────────────────────────────┘   ──▶     │                             │
                                                                                        │                            ◢│
  5 cols x 2 rows                             8 cols x 2 rows (preview)                └─────────────────────────────┘

                                                                                        8 cols x 3 rows (final)
```

## Widget Drag-and-Drop Reorder

```
  STEP 1: Grab title bar              STEP 2: Drag to new position         STEP 3: Drop

  ┌══ A ════┐  ┌══ B ════┐           ┌══ A ════┐  ┌ ─ ─ ─ ─ ┐           ┌══ B ════┐  ┌══ A ════┐
  │         │  │ (grab)  │           │         │  : empty   :           │         │  │         │
  │         │  │  ═══    │  ──▶      │         │  : drop    :  ──▶      │         │  │         │
  │         │  │         │           │         │  : zone    :           │         │  │         │
  └─────────┘  └─────────┘           └─────────┘  └ ─ ─ ─ ─ ┘           └─────────┘  └─────────┘
  ┌══ C ════┐                        ┌══ C ════┐   ┌══ B ════┐           ┌══ C ════┐
  │         │                        │         │   │ (ghost) │           │         │
  │         │                        │         │   │  ═══    │           │         │
  └─────────┘                        └─────────┘   └─────────┘           └─────────┘

                                     B is dragged;                        B and A swapped
                                     ghost follows cursor
```

## Dashboard Templates

When creating a new dashboard, users can choose from templates:

```
┌── New Dashboard ──────────────────────────────────────────────────────────────────────┐
│                                                                                       │
│  Dashboard Name: [ My Custom Dashboard          ]                                     │
│                                                                                       │
│  Choose a template:                                                                   │
│                                                                                       │
│  ┌─────────────────────┐  ┌─────────────────────┐  ┌─────────────────────┐           │
│  │  ┌────┬────┐        │  │  ┌──────┐           │  │  ┌───┬───┬───┬───┐ │           │
│  │  │    │    │        │  │  │      │           │  │  ├───┼───┼───┼───┤ │           │
│  │  ├────┴────┤        │  │  ├──────┤           │  │  ├───┼───┼───┼───┤ │           │
│  │  │         │        │  │  ├──────┤           │  │  └───┴───┴───┴───┘ │           │
│  │  └─────────┘        │  │  └──────┘           │  │                     │           │
│  │  Debug (default)    │  │  Single Column      │  │  Grid 4x3           │           │
│  └─────────────────────┘  └─────────────────────┘  └─────────────────────┘           │
│                                                                                       │
│  ┌─────────────────────┐  ┌─────────────────────┐  ┌─────────────────────┐           │
│  │  ┌────┬────────┐    │  │  ┌─────┐ ┌─────┐   │  │                     │           │
│  │  │    │        │    │  │  │     │ │     │   │  │                     │           │
│  │  ├────┤        │    │  │  │     │ │     │   │  │       Empty         │           │
│  │  │    │        │    │  │  │     │ │     │   │  │                     │           │
│  │  └────┴────────┘    │  │  └─────┘ └─────┘   │  │                     │           │
│  │  Sidebar + Main     │  │  Side by Side       │  │  Blank Canvas       │           │
│  └─────────────────────┘  └─────────────────────┘  └─────────────────────┘           │
│                                                                                       │
│                                                        [ Cancel ]  [ Create ]         │
└───────────────────────────────────────────────────────────────────────────────────────┘
```

## Export/Import Layout

```
┌── Export Layout ──────────────────────────────────────────────────────────┐
│                                                                           │
│  Dashboard: Debug                                                         │
│                                                                           │
│  ┌─────────────────────────────────────────────────────────────────────┐ │
│  │ {                                                                   │ │
│  │   "name": "Debug",                                                  │ │
│  │   "columns": 12,                                                    │ │
│  │   "widgets": [                                                      │ │
│  │     {                                                               │ │
│  │       "type": "status",                                             │ │
│  │       "collector": "request-info",                                  │ │
│  │       "grid": {"col": 1, "row": 1, "colSpan": 6, "rowSpan": 1}    │ │
│  │     },                                                              │ │
│  │     {                                                               │ │
│  │       "type": "status",                                             │ │
│  │       "collector": "response",                                      │ │
│  │       "grid": {"col": 7, "row": 1, "colSpan": 6, "rowSpan": 1}    │ │
│  │     },                                                              │ │
│  │     ...                                                             │ │
│  │   ]                                                                 │ │
│  │ }                                                                   │ │
│  └─────────────────────────────────────────────────────────────────────┘ │
│                                                                           │
│                                  [ Copy to Clipboard ]  [ Download JSON ] │
└───────────────────────────────────────────────────────────────────────────┘
```
