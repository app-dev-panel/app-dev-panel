# Variant A: Command Center — Debug Entries List

## Full Layout

```
┌──────────────────────────────────────────────────────────────────────────────────────────────────────────────────────┐
│  No entry selected — viewing all entries                                                         ⌘K Search        │
├────┬─────────────────────────────────────────────────────────────────────────────────────────────────────────────────┤
│    │                                                                                                               │
│ 🔍 │  Debug Entries                                                                   142 entries │ ⤓ Export       │
│    │                                                                                                               │
│ 📋 │  ┌─ Toolbar ───────────────────────────────────────────────────────────────────────────────────────────────┐   │
│    │  │  [Clear All]   Auto-refresh: [● On]   Method: [All ▾]   Status: [All ▾]   │ ☰  ☰  ☰ │  ⤓ CSV         │   │
│ 🔧 │  └────────────────────────────────────────────────────────────────────────────────────────────────────────┘   │
│    │                                                                                                               │
│ 📊 │  ┌─────║──────║─────────────────────────────────────────────║──────────║─────────║────────────║──────────────┐ │
│    │  │     ║Method║ URL                                  ▲     ║ Status   ║ Time ▼  ║ Memory     ║ Timestamp    │ │
│ 📁 │  ├─────║──────║─────────────────────────────────────────────║──────────║─────────║────────────║──────────────┤ │
│    │  │     ║[____]║ [________________________________]          ║ [______] ║ [_____] ║ [________] ║ [__________] │ │
│ 🛠  │  ├─────╬══════╬═══════════════════════════════════════════════╬══════════╬═════════╬════════════╬══════════════┤ │
│    │  │  □  ║ GET  ║ /api/users?page=2&limit=25                 ║ 200      ║   87ms  ║ 4.1 MB     ║ 14:23:07     │ │
│    │  │  □  ║ POST ║ /api/users                                 ║ 201      ║  145ms  ║ 8.2 MB     ║ 14:22:58     │ │
│    │  │  □  ║ GET  ║ /api/users/42/profile                      ║ 200      ║   52ms  ║ 3.8 MB     ║ 14:22:41     │ │
│    │  │  □  ║ GET  ║ /api/users/999                              ║ 404      ║   23ms  ║ 2.1 MB     ║ 14:22:30     │ │
│    │  │  □  ║ POST ║ /api/auth/refresh                          ║ 500      ║  340ms  ║ 12.4 MB    ║ 14:22:15     │ │
│    │  │  □  ║ CLI  ║ app:import-users --force                    ║ 0        ║ 2340ms  ║ 24.1 MB    ║ 14:20:00     │ │
│    │  │  □  ║ GET  ║ /api/products?category=electronics          ║ 200      ║   98ms  ║ 5.3 MB     ║ 14:19:45     │ │
│    │  │  □  ║ GET  ║ /old-path                                   ║ 301      ║   12ms  ║ 1.8 MB     ║ 14:19:30     │ │
│    │  │  □  ║ PUT  ║ /api/users/42                               ║ 200      ║  110ms  ║ 6.7 MB     ║ 14:19:12     │ │
│    │  │  □  ║DELETE║ /api/cache/products                         ║ 204      ║   34ms  ║ 2.0 MB     ║ 14:18:55     │ │
│    │  │  □  ║PATCH ║ /api/settings/theme                         ║ 200      ║   45ms  ║ 3.2 MB     ║ 14:18:40     │ │
│    │  │  □  ║ GET  ║ /api/dashboard/stats                        ║ 200      ║  234ms  ║ 9.8 MB     ║ 14:18:22     │ │
│    │  │     ║      ║                                             ║          ║         ║            ║              │ │
│    │  └─────║──────║─────────────────────────────────────────────║──────────║─────────║────────────║──────────────┘ │
│    │                                                                                                               │
│    │  Showing 1-12 of 142                                                        ◀  1  2  3  ...  12  ▶            │
│    │                                                                                                               │
├────┴─────────────────────────────────────────────────────────────────────────────────────────────────────────────────┤
│  GET /api/debug -> 200 OK (28ms)                                           ● SSE Connected │ ADP v1.2.0 │  ⚙     │
└──────────────────────────────────────────────────────────────────────────────────────────────────────────────────────┘
```

## Table Anatomy

### Column Headers with Sort

```
┌─────║──────║─────────────────────────────────────────────║──────────║─────────║────────────║──────────────┐
│     ║Method║ URL                                  ▲     ║ Status   ║ Time ▼  ║ Memory     ║ Timestamp    │
└─────║──────║─────────────────────────────────────────────║──────────║─────────║────────────║──────────────┘
       ▲                                             ▲                    ▲
       │                                             │                    │
       Column header text                            Sort ASC             Sort DESC (active)
                                                     (inactive,           (highlighted)
                                                      clickable)
```

Sort states: unsorted (no arrow), ascending (▲ highlighted), descending (▼ highlighted).
Click header to cycle: unsorted -> ascending -> descending -> unsorted.

### Filter Row

```
┌─────║──────║─────────────────────────────────────────────║──────────║─────────║────────────║──────────────┐
│     ║[____]║ [________________________________]          ║ [______] ║ [_____] ║ [________] ║ [__________] │
└─────║──────║─────────────────────────────────────────────║──────────║─────────║────────────║──────────────┘
       ▲       ▲                                            ▲          ▲
       │       │                                            │          │
       Method  URL text search                              Status     Time range
       dropdown (debounced 300ms)                           dropdown   min-max input
       filter                                               filter     filter
```

Filter row sits directly below the header row and above the data separator line.
Each column has an appropriate filter input type.

### Resizable Column Borders

The `║` characters between columns are **drag handles**. Dragging horizontally resizes
adjacent columns. Cursor changes to `col-resize` on hover.

```
            ║
            ║  <- drag handle (cursor: col-resize)
            ║     double-click: auto-fit column width
            ║
```

### Row States

```
Normal row:
│  □  ║ GET  ║ /api/users?page=2&limit=25                 ║ 200      ║   87ms  ║ 4.1 MB     ║ 14:23:07     │

Hovered row (light background):
│  □  ║ GET  ║ /api/users?page=2&limit=25                 ║ 200      ║   87ms  ║ 4.1 MB     ║ 14:23:07     │ ◀━━ bg: --bg-overlay

Selected row (accent left border):
│▎ ■  ║ GET  ║ /api/users?page=2&limit=25                 ║ 200      ║   87ms  ║ 4.1 MB     ║ 14:23:07     │ ◀━━ left border accent

Error row (danger tint):
│  □  ║ POST ║ /api/auth/refresh                          ║ 500      ║  340ms  ║ 12.4 MB    ║ 14:22:15     │ ◀━━ bg: danger tint
```

### Density Modes

Compact (default for list view):
```
│ GET  ║ /api/users  ║ 200  ║  87ms │   <- 32px row height, 4px padding
```

Comfortable:
```
│                                    │
│ GET  ║ /api/users  ║ 200  ║  87ms │   <- 44px row height, 10px padding
│                                    │
```

Spacious:
```
│                                    │
│                                    │
│ GET  ║ /api/users  ║ 200  ║  87ms │   <- 56px row height, 16px padding
│                                    │
│                                    │
```

Density toggle in toolbar (three horizontal lines, varying spacing):
```
  ☰   <- compact (lines close)
  ☰   <- comfortable (lines medium)
  ☰   <- spacious (lines far)
```

### Checkbox Column

First column contains a checkbox for bulk actions:

```
Header checkbox (select all on page):
│ ☐ │   <- unchecked
│ ■ │   <- all selected
│ ▣ │   <- some selected (indeterminate)
```

When rows are selected, a bulk action bar appears:

```
┌─ Bulk Actions ──────────────────────────────────────────────────────────────────┐
│  3 entries selected     [Compare]   [Delete]   [Export Selected]    [Clear]     │
└────────────────────────────────────────────────────────────────────────────────────┘
```

## Method Badge Colors

```
  ┌─────┐      ┌──────┐      ┌─────┐      ┌────────┐      ┌───────┐      ┌─────┐
  │ GET │      │ POST │      │ PUT │      │ DELETE │      │ PATCH │      │ CLI │
  └─────┘      └──────┘      └─────┘      └────────┘      └───────┘      └─────┘
  green bg     purple bg     amber bg     red bg          blue bg        gray bg
```

## Status Code Colors

```
  ┌─────┐  ┌─────┐  ┌─────┐  ┌─────┐  ┌─────┐  ┌─────┐
  │ 200 │  │ 201 │  │ 301 │  │ 404 │  │ 500 │  │  0  │
  └─────┘  └─────┘  └─────┘  └─────┘  └─────┘  └─────┘
  green    green    amber    red      red/bold  gray (CLI exit code)
```

## Pagination

```
  Showing 1-12 of 142                                              ◀  1  2  [3]  4  5  ...  12  ▶
                                                                       ▲       ▲                 ▲
                                                                       │       │                 │
                                                                       Page    Active page       Next
                                                                       link    (accent bg)       page
```

## Auto-Refresh Behavior

When auto-refresh is ON:
- New entries appear at the top with a slide-in animation
- A "New entries" badge pulses on the tab if user has scrolled down
- SSE pushes trigger refetch of the list

When auto-refresh is OFF:
- A banner appears when new entries are available:

```
┌────────────────────────────────────────────────────────────────────────────────────────────────────┐
│  ⓘ  5 new entries available since last refresh                                     [Refresh Now]  │
└────────────────────────────────────────────────────────────────────────────────────────────────────┘
```

## Interaction Notes

- Click row to navigate to Debug Main with that entry selected
- Double-click row to open in new tab
- Ctrl+Click to add to compare selection
- Right-click for context menu: Open, Open in New Tab, Compare With..., Copy ID, Delete
- Column reordering via drag-and-drop on column headers
- Shift+Click on column header for multi-column sort

## State Management

| State               | Storage      | Rationale                              |
|---------------------|-------------|----------------------------------------|
| Current page        | URL param   | `?page=3` — bookmarkable               |
| Sort column + dir   | URL param   | `?sort=time&dir=desc` — shareable       |
| Active filters      | URL param   | `?method=GET&status=200` — shareable    |
| Column widths       | localStorage| User preference, persists              |
| Column order        | localStorage| User preference, persists              |
| Density mode        | localStorage| User preference, persists              |
| Selected rows       | Local state | Transient selection                    |
| Auto-refresh toggle | Redux       | Affects SSE subscription behavior      |
| Entries data        | Redux       | API response cache                     |
