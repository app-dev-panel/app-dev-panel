# Variant C: Timeline-Driven — Smart Table Component Spec

## Overview

The Smart Table is a reusable, configurable table component used across all list views in ADP.
It supports sorting, filtering, column resizing, row expansion, and integrates with the timeline
design by supporting sparkline columns and inline mini-visualizations.

## Base Table Structure

```
┌──────────────────────────────────────────────────────────────────────────────────────────────────────────────────┐
│  ┌─ Table Title ── 147 items ── filtered from 312 ──────────────────── [Columns ▼] [Export ▼] [Density ▼] ┐   │
│  │                                                                                                         │   │
│  │  ┌─ Filters ──────────────────────────────────────────────────────────────────────────────────────────┐ │   │
│  │  │ [Column Filter 1 ▼]  [Column Filter 2 ▼]  Search: [________________________]  [Clear Filters]    │ │   │
│  │  └──────────────────────────────────────────────────────────────────────────────────────────────────  ┘ │   │
│  │                                                                                                         │   │
│  │  ┌───────────┬──────────┬────────────────────────────────┬──────────┬────────────┬────────────────────┐ │   │
│  │  │ Column A ▼│ Column B │ Column C                       │Column D ▲│ Column E   │ Column F           │ │   │
│  │  ├───────────┼──────────┼────────────────────────────────┼──────────┼────────────┼────────────────────┤ │   │
│  │  │ value     │ value    │ value                          │ value    │ value      │ value              │ │   │
│  │  │ value     │ value    │ value                          │ value    │ value      │ value              │ │   │
│  │  │ value     │ value    │ value                          │ value    │ value      │ value              │ │   │
│  │  │ value     │ value    │ value                          │ value    │ value      │ value              │ │   │
│  │  │ value     │ value    │ value                          │ value    │ value      │ value              │ │   │
│  │  └───────────┴──────────┴────────────────────────────────┴──────────┴────────────┴────────────────────┘ │   │
│  │                                                                                                         │   │
│  │  ─── Showing 1–25 of 147 ────────────────────────────────── [◀ 1  2  3  4  5  6 ▶] ── 25 per page ──  │   │
│  └─────────────────────────────────────────────────────────────────────────────────────────────────────────┘   │
└──────────────────────────────────────────────────────────────────────────────────────────────────────────────────┘
```

## Column Types

```
┌────────────────────┬───────────────────────────────────────────────────────────────────────────────────────────┐
│ Column Type        │ Rendering                                                                                │
├────────────────────┼───────────────────────────────────────────────────────────────────────────────────────────┤
│ text               │ Plain text, left-aligned. Truncated with ellipsis at column width.                       │
│ number             │ Right-aligned, monospace font. Optional unit suffix (ms, KB, etc).                       │
│ badge              │ Colored pill/chip: GET=blue, POST=green, PUT=orange, DELETE=red, etc.                    │
│ status             │ HTTP status with color: 2xx=green, 3xx=blue, 4xx=orange, 5xx=red.                       │
│ timestamp          │ Relative by default ("2m ago"), absolute on hover. Sortable by epoch.                    │
│ duration           │ Number + unit, colored by threshold: green=fast, orange=medium, red=slow.                │
│ sparkline          │ Mini-timeline bar using block characters. Fixed-width column.                            │
│ code               │ Monospace, truncated. Click to expand or hover for full content.                         │
│ boolean            │ Checkmark or cross icon. Sortable.                                                       │
│ link               │ Clickable text, navigates to detail view.                                                │
│ actions            │ Icon buttons (view, copy, delete). Right-aligned, no sort.                               │
│ bar                │ Horizontal bar proportional to value. Overlaid with number.                              │
│ nested             │ Expandable row with child table (for grouped data).                                      │
└────────────────────┴───────────────────────────────────────────────────────────────────────────────────────────┘
```

## Concrete Example: Query Table

```
┌─ Database Queries ── 5 queries ── 94ms total ──────────────────────────────── [Columns ▼] [Export CSV] ───────┐
│                                                                                                               │
│  ┌────┬────────┬────────┬──────────┬──────┬──────────────────────────────────────────────────────────┬───────┐ │
│  │ #  │ Start  │ Dur. ▼ │ Conn.    │ Rows │ Query                                                    │ Flags │ │
│  │num │duration│duration│ badge    │number│ code                                                     │ badge │ │
│  ├────┼────────┼────────┼──────────┼──────┼──────────────────────────────────────────────────────────┼───────┤ │
│  │  2 │  92ms  │  34ms  │ default  │   15 │ SELECT o.* FROM orders o INNER JOIN products p ON p.i...│  ⚠    │ │
│  │  3 │ 112ms  │  18ms  │ default  │   15 │ SELECT p.name, p.sku FROM products p WHERE p.id IN (... │       │ │
│  │  5 │ 148ms  │  16ms  │ default  │    3 │ SELECT value FROM config WHERE key IN (:k1, :k2, :k3)  │       │ │
│  │  4 │ 132ms  │  14ms  │ replica  │   15 │ SELECT stock FROM inventory WHERE product_id IN (:p1... │       │ │
│  │  1 │  72ms  │  12ms  │ default  │    1 │ SELECT * FROM users WHERE id = :id AND status = :status │       │ │
│  └────┴────────┴────────┴──────────┴──────┴──────────────────────────────────────────────────────────┴───────┘ │
│                                                                                                               │
└───────────────────────────────────────────────────────────────────────────────────────────────────────────────┘
```

## Row Expansion

Clicking a row expands it inline to show additional detail without navigating away.

```
│  │  2 │  92ms  │  34ms  │ default  │   15 │ SELECT o.* FROM orders o INNER JOIN products p ON p.i...│  ⚠    │
│  ├────┴────────┴────────┴──────────┴──────┴──────────────────────────────────────────────────────────┴───────┤
│  │  ┌─ Expanded Detail ────────────────────────────────────────────────────────────────────────────────────┐ │
│  │  │                                                                                                      │ │
│  │  │  SELECT o.id, o.product_id, o.quantity, o.total_price, o.status,                                     │ │
│  │  │         p.name AS product_name, p.sku                                                                │ │
│  │  │  FROM orders o                                                                                       │ │
│  │  │  INNER JOIN products p ON p.id = o.product_id                                                        │ │
│  │  │  WHERE o.user_id = :user_id AND o.created_at >= :since                                               │ │
│  │  │    AND o.status IN (:status_1, :status_2)                                                            │ │
│  │  │  ORDER BY o.created_at DESC LIMIT 25                                                                 │ │
│  │  │                                                                                                      │ │
│  │  │  Params: {:user_id=42, :since="2026-01-01", :status_1="completed", :status_2="shipped"}              │ │
│  │  │                                                                                                      │ │
│  │  │  [View on Timeline]  [Copy SQL]  [Show Explain]  [Show Stack Trace]                                  │ │
│  │  └──────────────────────────────────────────────────────────────────────────────────────────────────────┘ │
│  ├────┬────────┬────────┬──────────┬──────┬──────────────────────────────────────────────────────────┬───────┤
│  │  3 │ 112ms  │  18ms  │ default  │   15 │ SELECT p.name, p.sku FROM products p WHERE p.id IN (... │       │
```

## Column Visibility Menu

```
  ┌─ Columns ──────────────┐
  │ [✓] #                  │
  │ [✓] Start              │
  │ [✓] Duration           │
  │ [✓] Connection         │
  │ [✓] Rows               │
  │ [✓] Query              │
  │ [✓] Flags              │
  │ [ ] Parameters         │
  │ [ ] Stack Trace        │
  │ [ ] Transaction ID     │
  │ ─────────────────────  │
  │ [Reset to Default]     │
  └────────────────────────┘
```

## Density Options

```
  ┌─ Density ──────────────┐
  │ ○ Compact (24px rows)  │
  │ ● Default (36px rows)  │
  │ ○ Relaxed (48px rows)  │
  └────────────────────────┘
```

## Export Options

```
  ┌─ Export ────────────────┐
  │ Export as CSV            │
  │ Export as JSON           │
  │ Copy to clipboard       │
  │ ─────────────────────── │
  │ Export all pages         │
  │ Export current page      │
  └─────────────────────────┘
```

## Sorting Behavior

```
Click column header:    Cycle through → ascending → descending → no sort
Current sort indicator: ▲ (ascending) or ▼ (descending) next to column name
Multi-sort:             Shift+Click adds secondary sort (shown as ▲1 ▲2)
Sticky sort:            Sort preference stored in localStorage per table ID
Default sort:           Defined per table (e.g., queries default to Start ascending)
```

## Filtering Behavior

```
Text columns:     Free-text search (case-insensitive substring match)
Number columns:   Range filter: [min] – [max], or comparison: >100, <50, =42
Badge columns:    Multi-select dropdown of distinct values
Status columns:   Preset groups: 2xx, 3xx, 4xx, 5xx, or individual codes
Duration columns: Preset ranges: <10ms, 10-50ms, 50-100ms, >100ms, or custom
Boolean columns:  Three-state: All, True, False
```

## Empty State

```
┌──────────────────────────────────────────────────────────────────────────────────────────────────────────────┐
│                                                                                                              │
│                                                                                                              │
│                                    No results match your filters                                              │
│                                                                                                              │
│                              Showing 0 of 312 entries after filtering                                        │
│                                                                                                              │
│                                       [Clear All Filters]                                                    │
│                                                                                                              │
│                                                                                                              │
└──────────────────────────────────────────────────────────────────────────────────────────────────────────────┘
```

## Keyboard Navigation

```
┌────────────────┬──────────────────────────────────────────────────────────────────────────────────────────────┐
│ Key            │ Action                                                                                      │
├────────────────┼──────────────────────────────────────────────────────────────────────────────────────────────┤
│ Arrow Up/Down  │ Move row focus                                                                              │
│ Enter          │ Expand/collapse row, or navigate to detail                                                  │
│ Space          │ Select/deselect row (for bulk actions)                                                      │
│ Ctrl+A         │ Select all visible rows                                                                     │
│ Escape         │ Clear selection, close expanded row                                                         │
│ /              │ Focus search input                                                                          │
│ Ctrl+C         │ Copy selected rows to clipboard                                                             │
│ Page Up/Down   │ Navigate pagination                                                                         │
└────────────────┴──────────────────────────────────────────────────────────────────────────────────────────────┘
```
