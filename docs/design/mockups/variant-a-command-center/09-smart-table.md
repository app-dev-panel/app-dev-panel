# Variant A: Command Center — Smart Table Component

## Overview

SmartTable is the core reusable table component used across all list views in ADP:
Debug List, Events, Routes, Logs, DB Queries, Commands, Files, Translations, etc.

It provides a consistent, power-user-friendly table experience.

## Full Anatomy

```
┌─ Table Title (N items) ───────────────────────────────────────────────────────── Density: ☰ ☰ ☰  ⤓ Export ──┐
│                                                                                                               │
│  ┌──────║──────────────║──────────────────────────────────────║──────────║──────────║────────────║────────────┐ │
│  │  ☐   ║ Column A  ▲  ║ Column B                            ║ Column C ║ Col D ▼  ║ Column E   ║ Column F   │ │
│  ├──────║──────────────║──────────────────────────────────────║──────────║──────────║────────────║────────────┤ │
│  │      ║ [__________] ║ [______________________________]     ║ [All  ▾] ║ [______] ║ [________] ║ [________] │ │
│  ├══════╬══════════════╬══════════════════════════════════════╬══════════╬══════════╬════════════╬════════════┤ │
│  │  ☐   ║ value-a1     ║ value-b1                            ║ type-1   ║    12ms  ║ val-e1     ║ val-f1     │ │
│  │  ☐   ║ value-a2     ║ value-b2                            ║ type-2   ║    34ms  ║ val-e2     ║ val-f2     │ │
│  │  ☐   ║ value-a3     ║ value-b3                            ║ type-1   ║     8ms  ║ val-e3     ║ val-f3     │ │
│  │  ☐   ║ value-a4     ║ value-b4 (this is a longer value    ║ type-3   ║    56ms  ║ val-e4     ║ val-f4     │ │
│  │      ║              ║ that wraps to next line)             ║          ║          ║            ║            │ │
│  │  ☐   ║ value-a5     ║ value-b5                            ║ type-1   ║     3ms  ║ val-e5     ║ val-f5     │ │
│  │  ☐   ║ value-a6     ║ value-b6                            ║ type-2   ║    21ms  ║ val-e6     ║ val-f6     │ │
│  │  ☐   ║ value-a7     ║ value-b7                            ║ type-1   ║    15ms  ║ val-e7     ║ val-f7     │ │
│  │  ☐   ║ value-a8     ║ value-b8                            ║ type-3   ║    42ms  ║ val-e8     ║ val-f8     │ │
│  │  ☐   ║ value-a9     ║ value-b9                            ║ type-1   ║     7ms  ║ val-e9     ║ val-f9     │ │
│  │  ☐   ║ value-a10    ║ value-b10                           ║ type-2   ║    28ms  ║ val-e10    ║ val-f10    │ │
│  └──────║──────────────║──────────────────────────────────────║──────────║──────────║────────────║────────────┘ │
│                                                                                                               │
│  Showing 1-10 of 142                                                       ◀  1  2  [3]  4  ...  15  ▶       │
│                                                                                                               │
└───────────────────────────────────────────────────────────────────────────────────────────────────────────────┘
```

## Header Row Detail

```
┌──────║──────────────║──────────────────────────────────────║──────────║──────────║────────────║────────────┐
│  ☐   ║ Column A  ▲  ║ Column B                            ║ Column C ║ Col D ▼  ║ Column E   ║ Column F   │
└──────║──────────────║──────────────────────────────────────║──────────║──────────║────────────║────────────┘
   ▲    ▲    ▲      ▲   ▲                                                    ▲
   │    │    │      │   │                                                    │
   │    │    │      │   Column header (not sorted)                           Sort descending (active)
   │    │    │      Sort ascending (active, highlighted)
   │    │    Drag handle ║ (cursor: col-resize)
   │    Column header text
   Select-all checkbox
```

**Header interactions:**
- Click header text: toggle sort (none -> asc -> desc -> none)
- Shift+Click: add as secondary sort column
- Drag `║` border: resize column
- Double-click `║` border: auto-fit column width to content
- Right-click header: column visibility menu
- Drag header text: reorder columns

## Filter Row Detail

```
┌──────║──────────────║──────────────────────────────────────║──────────║──────────║────────────║────────────┐
│      ║ [__________] ║ [______________________________]     ║ [All  ▾] ║ [______] ║ [________] ║ [________] │
└──────║──────────────║──────────────────────────────────────║──────────║──────────║────────────║────────────┘
         ▲               ▲                                     ▲          ▲
         │               │                                     │          │
         Text input      Text input (search)                   Dropdown   Numeric range
         (exact/fuzzy)   (debounced 300ms)                     select     (min-max)
```

**Filter input types by column data type:**

| Data Type  | Filter Widget          | Example                    |
|------------|------------------------|----------------------------|
| String     | Text input (fuzzy)     | `[user_____________]`      |
| Enum       | Dropdown select        | `[GET ▾]`                  |
| Number     | Range input (min-max)  | `[10] - [100]`             |
| Boolean    | Toggle/tri-state       | `[All ▾]` / `[Yes ▾]`     |
| DateTime   | Date range picker      | `[2026-03-15] - [_______]` |
| Status     | Multi-select chips     | `[200] [201] [✕ 404]`     |

## Resize Handles

```
                    ║
                    ║  <- Drag handle between columns
                    ║
                    ▲
                    │
        cursor: col-resize on hover
        visual indicator: handle highlights on hover (accent color line)
        minimum column width: 60px
        double-click: auto-fit to widest content in column
```

Resize behavior:
- Dragging right: expands left column, shrinks right column
- Dragging left: shrinks left column, expands right column
- Total table width stays constant (no horizontal scroll unless content overflows)
- Column widths saved to localStorage per table instance

## Row Count Badge

```
  ┌─ Events (31 items) ──────────────────────────────────────────┐
              ▲
              │
              Row count badge — updates when filters change
              Shows filtered count vs total: "Events (12 of 31)"
```

When filtered:
```
  ┌─ Events (12 of 31 items) ──────────────── [✕ Clear Filters] ─┐
```

## Density Toggle

Three density levels controlled by a button group in the toolbar:

```
  Density:  [☰] [☰] [☰]
             ▲   ▲   ▲
             │   │   │
             │   │   Spacious (56px row, 16px padding)
             │   Comfortable (44px row, 10px padding)
             Compact (32px row, 4px padding)  <- default
```

Visual representation of each density:

**Compact (32px):**
```
│ GET ║ /api/users ║ 200 ║  87ms │
│ POST║ /api/users ║ 201 ║ 145ms │
```

**Comfortable (44px):**
```
│     ║            ║     ║       │
│ GET ║ /api/users ║ 200 ║  87ms │
│     ║            ║     ║       │
│ POST║ /api/users ║ 201 ║ 145ms │
│     ║            ║     ║       │
```

**Spacious (56px):**
```
│     ║            ║     ║       │
│     ║            ║     ║       │
│ GET ║ /api/users ║ 200 ║  87ms │
│     ║            ║     ║       │
│     ║            ║     ║       │
│     ║            ║     ║       │
│ POST║ /api/users ║ 201 ║ 145ms │
│     ║            ║     ║       │
│     ║            ║     ║       │
```

## Export Button

```
  [⤓ Export ▾]
  ┌─────────────────────┐
  │  📋 Copy to Clipboard│
  │  ⤓  Export as CSV    │
  │  ⤓  Export as JSON   │
  │  ⤓  Export as TSV    │
  └─────────────────────┘
```

Export respects current filters and sort order. Exports all matching rows (not just current page).

## Column Visibility Menu (right-click header)

```
  ┌─ Columns ─────────────────────┐
  │  ☑ Column A                   │
  │  ☑ Column B                   │
  │  ☑ Column C                   │
  │  ☐ Column D (hidden)          │
  │  ☑ Column E                   │
  │  ☑ Column F                   │
  │  ─────────────────────────    │
  │  [Reset to Default]           │
  └───────────────────────────────┘
```

## Multi-Column Sort Indicator

When multiple columns are sorted (via Shift+Click):

```
│  Column A  ▲¹ ║ Column B     ║ Column C ▼² ║
```

The superscript number indicates sort priority.

## Empty State

```
┌──────────────────────────────────────────────────────────────────────────────────────────────────────────────────┐
│                                                                                                                │
│                                                                                                                │
│                                         No data to display                                                     │
│                                                                                                                │
│                                    There are no entries matching                                                │
│                                    your current filters.                                                       │
│                                                                                                                │
│                                       [Clear Filters]                                                          │
│                                                                                                                │
│                                                                                                                │
└──────────────────────────────────────────────────────────────────────────────────────────────────────────────────┘
```

## Loading State

```
┌──────║──────────────║──────────────────────────────────────║──────────║──────────║────────────║────────────┐
│  ☐   ║ Column A  ▲  ║ Column B                            ║ Column C ║ Col D ▼  ║ Column E   ║ Column F   │
├══════╬══════════════╬══════════════════════════════════════╬══════════╬══════════╬════════════╬════════════┤
│  ░░░ ║ ░░░░░░░░░░░░ ║ ░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░ ║ ░░░░░░░░ ║ ░░░░░░░░ ║ ░░░░░░░░░░ ║ ░░░░░░░░░░ │
│  ░░░ ║ ░░░░░░░░░░░░ ║ ░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░ ║ ░░░░░░░░ ║ ░░░░░░░░ ║ ░░░░░░░░░░ ║ ░░░░░░░░░░ │
│  ░░░ ║ ░░░░░░░░░░░░ ║ ░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░ ║ ░░░░░░░░ ║ ░░░░░░░░ ║ ░░░░░░░░░░ ║ ░░░░░░░░░░ │
└──────║──────────────║──────────────────────────────────────║──────────║──────────║────────────║────────────┘
 ░ = skeleton shimmer animation (pulse)
```

## Sticky Header

The header row and filter row are sticky (position: sticky, top: 0).
When the user scrolls down, headers remain visible:

```
  ┌── Column headers ──────────────────────────────────────┐  <- sticky
  ├── Filter row ──────────────────────────────────────────┤  <- sticky
  ├════════════════════════════════════════════════════════════┤
  │  (scrolled content...)                                   │
  │  row 15 ║ data ║ data ║ data                            │
  │  row 16 ║ data ║ data ║ data                            │
  │  row 17 ║ data ║ data ║ data                            │
  └──────────────────────────────────────────────────────────┘
```

## Pagination

```
  Showing 26-50 of 142                                                 ◀  1  [2]  3  4  5  ...  6  ▶

  ── Pagination controls ─────────────────────────────────────────────────────
  ◀          Previous page (disabled on page 1)
  1          First page link
  [2]        Current page (accent background, not clickable)
  3 4 5      Adjacent page links
  ...        Ellipsis (pages omitted)
  6          Last page link
  ▶          Next page (disabled on last page)

  ── Page size selector (optional, in toolbar) ─────────────────────
  Rows per page: [10 ▾] [25 ▾] [50 ▾] [100 ▾]
```

## Props Interface (TypeScript)

For implementation reference, the SmartTable component interface:

```
SmartTableProps<T> {
  data: T[]
  columns: ColumnDef<T>[]
  defaultSort?: { column: string, direction: 'asc' | 'desc' }
  defaultDensity?: 'compact' | 'comfortable' | 'spacious'
  pageSize?: number
  pageSizeOptions?: number[]
  selectable?: boolean
  onRowClick?: (row: T) => void
  onSelectionChange?: (selected: T[]) => void
  exportFormats?: ('csv' | 'json' | 'tsv')[]
  stickyHeader?: boolean
  storageKey?: string           // localStorage key for persisting column widths, order, visibility
  emptyMessage?: string
  loading?: boolean
}

ColumnDef<T> {
  key: string
  header: string
  sortable?: boolean
  filterable?: boolean
  filterType?: 'text' | 'select' | 'range' | 'date-range' | 'boolean'
  filterOptions?: { value: string, label: string }[]
  width?: number                // initial width in px
  minWidth?: number
  maxWidth?: number
  resizable?: boolean
  hidden?: boolean
  render?: (value: any, row: T) => ReactNode
  exportValue?: (value: any, row: T) => string
}
```

## State Management

| State                | Storage      | Rationale                                |
|----------------------|-------------|------------------------------------------|
| Sort column + dir    | URL param   | Shareable, bookmarkable                  |
| Active filters       | URL param   | Shareable, bookmarkable                  |
| Current page         | URL param   | Shareable, bookmarkable                  |
| Page size            | URL param   | Shareable                                |
| Column widths        | localStorage| User preference, persists per table      |
| Column order         | localStorage| User preference, persists per table      |
| Column visibility    | localStorage| User preference, persists per table      |
| Density mode         | localStorage| User preference, global                  |
| Selected rows        | Local state | Transient, cleared on navigation         |
