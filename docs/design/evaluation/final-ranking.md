# Final Design Ranking & Integration Guide

## Consolidated Scores

| Variant | UX (weight 35%) | DX (weight 40%) | Engineering (weight 25%) | Weighted Total |
|---------|:---:|:---:|:---:|:---:|
| **A: Command Center** | 78 | 79 | 78 | **78.5** |
| **B: Split Focus** | 79 | 76 | 71 | **75.6** |
| **C: Timeline-Driven** | 64 | 68 | 41 | **59.7** |
| **D: Minimal Zen** | 60 | 61 | 83 | **66.8** |
| **E: Dashboard Grid** | 66 | 65 | 46 | **60.4** |

## Top 3

### 1. Variant A: Command Center (78.5 pts)

**Why it wins**: Best balance across all three evaluations. Highest DX score (developers use it 50+ times/day). Strong information density, keyboard shortcuts, and URL shareability. Feasible with zero new dependencies using MUI 5.

**Mockups**: `docs/design/mockups/variant-a-command-center/` (11 files)

**Key Design Elements**:
- Context bar (48px top): method badge, status code, URL, time, memory, prev/next entry arrows
- Sidebar rail (48px icons, 200px on hover): module navigation with flyout submenus
- Collector tab bar (40px): tabs with count badges, number-key switching (1-9)
- Status bar (24px bottom): last API action, SSE indicator, version, settings
- Command palette (Ctrl+K): fuzzy search across entries, pages, actions
- Compare mode: side-by-side horizontal split

### 2. Variant B: Split Focus (75.6 pts)

**Why it's #2**: Best context preservation and lowest context-switching cost. The always-visible entry list is the single best feature for debugging workflow. Loses points on engineering (needs split-pane library + virtualization).

**Mockups**: `docs/design/mockups/variant-b-split-focus/` (10 files)

**Key Design Elements**:
- 3-column layout: Nav Rail (48px) | Entry List (280px, resizable) | Content (flex)
- Entry list always visible with filters, grouping (Web/Console), SSE indicator
- Accordion content with pinnable/reorderable sections
- Right-click context menu on entries: Compare, Bookmark, Copy cURL
- Floating contextual action bar near selected content
- Compare mode via Ctrl+Click entry

### 3. Variant D: Minimal Zen (66.8 pts)

**Why it's #3**: Highest engineering score (83pts) — easiest to build, best migration path, zero new dependencies. Lower UX/DX scores due to progressive disclosure hiding info behind clicks. Best as a starting point that evolves toward A.

**Mockups**: `docs/design/mockups/variant-d-minimal-zen/` (9 files)

**Key Design Elements**:
- No sidebar — command palette (Ctrl+K) is primary navigation
- Thin top bar (48px): logo, entry selector pill, prev/next, search trigger
- Collector cards grid (3 per row) with sparklines and count badges
- Progressive disclosure: cards -> expanded inline -> full detail page
- Clean tables with no borders, no alternating rows
- Ultra-minimal toolbar (single 28px line)

---

## Recommended Hybrid Approach

All three evaluators converge on a similar recommendation: **build A as the base, borrow key features from B and C**.

### Core Shell (from A: Command Center)
```
┌─────────────────────────────────────────────────────────────────────┐
│ ■ Context Bar: [POST] [201] /api/users  145ms  12.4MB  ◄ ► [Compare] │
├──┬──────────────────────────────────────────────────────────────────┤
│  │ Tab Bar: [Request] [Logs(3)] [DB(5)] [Events(12)] [Timeline]    │
│  ├──────────────────────────────────────────────────────────────────┤
│  │                                                                  │
│R │                    Main Content Area                              │
│a │                    (collector panel)                              │
│i │                                                                  │
│l │                                                                  │
│  │                                                                  │
├──┴──────────────────────────────────────────────────────────────────┤
│ Status: POST /debug/api/view/abc → 200 OK (45ms)  ● SSE  v1.2.3 ⚙ │
└─────────────────────────────────────────────────────────────────────┘
```

### Borrowed from B: Collapsible Entry List
- Add a toggleable entry list panel (left side, between rail and content)
- Default: hidden (A's layout). Toggle: Ctrl+L or sidebar button
- When visible: 3-column layout like B. When hidden: full-width like A
- Entry list persists across collector tab switches

### Borrowed from C: Timeline Tab
- Add "Timeline" as one of the collector tabs
- Renders waterfall visualization of request lifecycle
- Not the primary view — an optional deep-dive for performance debugging

### Borrowed from D: Command Palette
- Ctrl+K opens fuzzy search across pages, entries, actions, collectors
- Sections: Recent, Pages, Actions
- Action prefix `>` for commands (Repeat Request, Copy cURL, etc.)

---

## Integration Instructions for Frontend Developer

### Phase 1: Shell Refactor (estimated: 3-5 days)

**Files to modify**:
- `packages/yii-dev-panel/src/Application/Component/Layout.tsx` — replace Drawer with SidebarRail + ContextBar + StatusBar
- `packages/yii-dev-panel-sdk/src/Component/MenuPanel.tsx` — refactor to SidebarRail (48px icon-only, hover-expand)

**New components to create**:
- `packages/yii-dev-panel-sdk/src/Component/ContextBar.tsx` — top bar with entry metadata
- `packages/yii-dev-panel-sdk/src/Component/StatusBar.tsx` — bottom bar with action feedback
- `packages/yii-dev-panel-sdk/src/Component/SidebarRail.tsx` — icon rail with hover flyout
- `packages/yii-dev-panel-sdk/src/Component/CommandPalette.tsx` — Ctrl+K overlay

**State changes**:
- Add to `ApplicationSlice`: `sidebarExpanded: boolean`, `lastAction: {method, url, status, duration} | null`
- Add URL params: `tab` (active collector), `compare` (comparison entry ID)

### Phase 2: Collector Tab Bar (estimated: 2-3 days)

**Files to modify**:
- `packages/yii-dev-panel/src/Module/Debug/Pages/Layout.tsx` — replace MenuPanel+Outlet with TabBar+TabPanel

**New components**:
- `packages/yii-dev-panel/src/Module/Debug/Component/CollectorTabBar.tsx` — MUI Tabs with badge counts
- Reuse existing collector panels (LogPanel, DatabasePanel, etc.) as tab content

**Keyboard shortcuts**:
- Number keys 1-9 switch tabs
- Ctrl+[ / Ctrl+] navigate entries

### Phase 3: SmartTable (estimated: 3-4 days)

**New component**:
- `packages/yii-dev-panel-sdk/src/Component/SmartTable.tsx` — replaces current Grid.tsx

**Features to implement**:
- Column sort with multi-column support (Shift+Click)
- Per-column filter row (type-appropriate: text search, numeric range, enum checkboxes)
- Column resize with drag handles (persist widths to localStorage)
- Density toggle: compact (32px) / comfortable (48px) / spacious (64px)
- Export menu: CSV, JSON, TSV
- Row count badge
- Sticky header

**Replace Grid.tsx usage** in: Debug ListPage, RoutesPage, EventsPage, DatabasePage, TablePage, TestsPage, AnalysePage, TranslationsPage, ComposerPage

### Phase 4: Entry List Panel (estimated: 2-3 days)

**New dependency**: `react-resizable-panels` (~8KB gzipped)

**New components**:
- `packages/yii-dev-panel/src/Module/Debug/Component/EntryListPanel.tsx`
- Toggleable via Ctrl+L, persists open/closed state in `ApplicationSlice`
- Filters: type (Web/Console), status (2xx/3xx/4xx/5xx), time range
- SSE indicator at bottom
- Right-click context menu: Compare, Copy cURL, Bookmark

### Phase 5: Compare Mode (estimated: 2-3 days)

**URL**: `?compare=<entryId>` alongside `?debugEntry=<entryId>`

**Implementation**:
- Horizontal split in content area (reuse `react-resizable-panels`)
- Both sides show same collector tab, synced
- Diff highlighting: green for improvement, red for regression
- Summary header: delta metrics (time, memory, query count)

### Phase 6: Timeline Tab (estimated: 4-5 days)

**New dependency**: `visx` (@visx/shape, @visx/scale, @visx/axis) ~20KB gzipped

**Implementation**:
- SVG-based waterfall within a collector tab
- Bars colored by collector type
- Click bar to see details inline
- Zoom with mousewheel, pan with drag

**Backend dependency**: Collectors need start/end timestamps per operation. Verify Kernel data model supports this.

---

## File Delivery Summary

| Directory | Contents | Files |
|-----------|----------|:-----:|
| `docs/design/requirements/` | Use cases, UX problems, requirements | 1 |
| `docs/design/mockups/variant-a-command-center/` | Command Center mockups | 11 |
| `docs/design/mockups/variant-b-split-focus/` | Split Focus mockups | 10 |
| `docs/design/mockups/variant-c-timeline-driven/` | Timeline-Driven mockups | 10 |
| `docs/design/mockups/variant-d-minimal-zen/` | Minimal Zen mockups | 9 |
| `docs/design/mockups/variant-e-dashboard-grid/` | Dashboard Grid mockups | 9 |
| `docs/design/evaluation/` | 3 evaluations + final ranking | 4 |
| **Total** | | **54** |
