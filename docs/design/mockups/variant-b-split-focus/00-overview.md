# Variant B: Split Focus — Design Overview

## Design Philosophy

"Split Focus" is inspired by Postman, Insomnia, and JetBrains IDEs. The core idea: **never leave
your context**. The entry list is always visible in a middle panel. Selecting an entry loads its
detail in the content area to the right. No full-page transitions, no back buttons, no lost state.

## Layout System

```
┌──────────────────────────────────────────────────────────────────────────────────────────────────────────────────────┐
│ 48px         280px (resizable)                          flex (remaining)                                           │
│ ┌──────┬─────────────────────────┬──────────────────────────────────────────────────────────────────────────────────┐│
│ │      │                         │                                                                                ││
│ │ Nav  │     Entry List          │              Content Area                                                      ││
│ │ Rail │     (always visible)    │              (detail / accordion / compare)                                     ││
│ │      │                         │                                                                                ││
│ │ 48px │     min:200 max:500     │              min:400                                                           ││
│ │      │                         │                                                                                ││
│ └──────┴─────────────────────────┴──────────────────────────────────────────────────────────────────────────────────┘│
└──────────────────────────────────────────────────────────────────────────────────────────────────────────────────────┘
```

## Three-Column Architecture

| Column       | Width         | Purpose                              | Resize  |
|--------------|---------------|--------------------------------------|---------|
| Nav Rail     | 48px fixed    | Module icons, settings, theme toggle | No      |
| Entry List   | 280px default | Filtered list of debug entries       | Yes     |
| Content Area | flex          | Detail view, accordions, compare     | Yes     |

## Color Coding

| Element                | Color       | Meaning                          |
|------------------------|-------------|----------------------------------|
| `GET` badge            | Green #4CAF | HTTP GET method                  |
| `POST` badge           | Blue #2196  | HTTP POST method                 |
| `PUT` badge            | Orange #FF9 | HTTP PUT method                  |
| `DELETE` badge         | Red #F44    | HTTP DELETE method               |
| `CLI` badge            | Purple #9C2 | Console command                  |
| Status 2xx             | Green       | Successful response              |
| Status 4xx             | Yellow      | Client error                     |
| Status 5xx             | Red         | Server error                     |
| SSE dot green          | Green       | Connected, receiving events      |
| SSE dot red            | Red         | Disconnected                     |
| Selected entry         | Primary/10% | Currently viewed entry           |
| Hover entry            | Gray/5%     | Mouse hover feedback             |
| Error row in table     | Red/5% bg   | Row contains error data          |
| Slow query highlight   | Orange/10%  | Query exceeding threshold        |

## Interaction Model

- **Click entry** in list -> loads detail in content area (no page change)
- **Ctrl+Click entry** -> opens compare mode (horizontal split)
- **Right-click entry** -> context menu (Compare, Bookmark, Copy cURL, Delete)
- **Drag column border** -> resize list/content split
- **Drag accordion section** -> reorder collectors
- **Pin accordion** -> keep section expanded when collapsing others
- **Keyboard arrows** in list -> navigate entries
- **Enter** on entry -> select it
- **Escape** -> deselect / close context menu
- **Ctrl+F** -> focus search in entry list
- **Ctrl+Shift+F** -> focus search in content area

## Content Area Modes

```
Normal Mode:                          Compare Mode:
┌────────────────────────────┐        ┌────────────────────────────┐
│                            │        │       Entry A              │
│    Full detail view        │        │                            │
│    of selected entry       │        ├────────────────────────────┤  <-- drag to resize
│                            │        │       Entry B              │
│                            │        │                            │
└────────────────────────────┘        └────────────────────────────┘
```

## Responsive Breakpoints

| Breakpoint | Behavior                                      |
|------------|-----------------------------------------------|
| >= 1280px  | Full 3-column layout                          |
| 960-1279px | Nav rail collapses to icons, list narrows      |
| < 960px    | List becomes overlay drawer, content fills     |

## Z-Index Layers

| Layer              | Z-Index | Elements                          |
|--------------------|---------|-----------------------------------|
| Base               | 0       | Content area                      |
| Entry list         | 10      | Middle panel                      |
| Nav rail           | 20      | Left icon bar                     |
| Floating actions   | 30      | Contextual action bar             |
| Context menu       | 40      | Right-click menus                 |
| Modal / Dialog     | 50      | Confirmations, settings           |
| Toast              | 60      | Notifications                     |
