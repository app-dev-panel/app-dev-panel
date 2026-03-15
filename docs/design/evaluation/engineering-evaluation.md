# Frontend Engineering Evaluation

Evaluated against: React 18, TypeScript 5.5, MUI 5 (with DataGrid + TreeView), Redux Toolkit + RTK Query, React Router 6, Vite.

Existing codebase: MUI Drawer-based sidebar (MenuPanel with permanent mini/expanded modes), Breadcrumbs navigation, Container-based layout, RTK Query API layer (debug, inspector, git, gii), SSE hook, SDK components (JsonRenderer, CodeHighlight, Grid, InfoBox, FilterInput, etc.).

## Scoring Matrix

| Criteria                | A: Command Center | B: Split Focus | C: Timeline-Driven | D: Minimal Zen | E: Dashboard Grid |
|-------------------------|:-----------------:|:--------------:|:-------------------:|:--------------:|:-----------------:|
| 1. MUI Compatibility    |        8          |       9        |         4           |       9        |        5          |
| 2. Redux Complexity     |        7          |       6        |         4           |       8        |        3          |
| 3. Migration Path       |        8          |       7        |         4           |       9        |        4          |
| 4. Component Reuse      |        8          |       7        |         4           |       8        |        5          |
| 5. Performance Risk     |        7          |       7        |         4           |       7        |        5          |
| 6. Bundle Size Impact   |        9          |       8        |         4           |       8        |        4          |
| 7. Testing Complexity   |        8          |       7        |         4           |       8        |        5          |
| 8. Accessibility        |        8          |       7        |         5           |       9        |        6          |
| 9. Responsive Design    |        8          |       7        |         5           |       9        |        6          |
| 10. Time to Build       |        7          |       6        |         3           |       8        |        3          |
| **Total**               |      **78**       |     **71**     |       **41**        |     **83**     |      **46**       |

## Implementation Analysis

### A: Command Center

**New Dependencies**: None required. All patterns achievable with MUI 5 + existing stack.

**MUI Components Used**: AppBar (context bar), Tabs (collector tabs), Drawer permanent variant (sidebar rail), styled Box (status bar), Menu (command palette could use Autocomplete or Dialog), Chip/Badge (status indicators), DataGrid (tables).

**Estimated New Components**:
- `ContextBar` -- top bar with entry metadata, nav arrows, compare toggle
- `StatusBar` -- bottom bar with SSE indicator, last action, version
- `SidebarRail` -- 48px icon rail with hover-expand overlay (refactor of existing MenuPanel)
- `CollectorTabBar` -- tab bar with badge counts per collector
- `CommandPalette` -- Ctrl+K dialog with fuzzy search (Autocomplete in Dialog)
- `SmartTable` -- unified table with sort/filter/resize/export/density

**Migration Strategy**: Incremental. The existing Layout uses a Drawer + Breadcrumbs pattern. Phase 1: Replace Drawer with SidebarRail (already similar to MenuPanel's mini variant). Phase 2: Add ContextBar above content, remove Breadcrumbs. Phase 3: Add StatusBar. Phase 4: Replace per-page tables with SmartTable. The module system (ModuleInterface) stays intact; only the shell changes.

**Risk Areas**: Command palette needs keyboard shortcut handling (Ctrl+K conflicts with browser). Sidebar hover-expand with flyout submenus requires careful mouse-tracking to avoid flicker. Column resize in SmartTable needs custom implementation (MUI DataGrid has this built-in, but the spec wants a lighter approach). Compare mode (horizontal split) adds moderate routing complexity.

---

### B: Split Focus

**New Dependencies**: A resizable split-pane library (e.g., `react-resizable-panels` ~8KB gzipped, or `allotment` ~15KB). MUI does not provide a native resizable panel splitter.

**MUI Components Used**: Drawer permanent variant (nav rail), List/ListItemButton (entry list), Accordion (content area collector sections), Tabs (content area modes), Menu (context menu), Breadcrumbs (content area header), DataGrid (tables).

**Estimated New Components**:
- `EntryListPanel` -- filterable, grouped entry list with SSE status footer
- `ResizableSplit` -- wrapper around split-pane library for list/content resize
- `ContentArea` -- accordion view, single-collector view, compare mode
- `AccordionCollectorView` -- all collectors as expandable sections
- `ContextMenuProvider` -- right-click menu on entries
- `SmartTable` -- same as Variant A

**Migration Strategy**: Medium difficulty. The current layout is a single-column Container; moving to a 3-column layout requires restructuring the Layout component entirely. Entry list is new -- currently entries are shown on a separate Debug List page, not a persistent panel. The content area can reuse existing collector page components by embedding them in accordion sections. Module routes need adjustment: Debug module stops being a separate page and becomes a panel within the split layout.

**Risk Areas**: Three-panel layout with resizable dividers is complex to get right with MUI. The always-visible entry list means SSE updates trigger re-renders of the list while the user reads detail -- needs virtualization (react-window) for large entry counts. Accordion reordering via drag requires a DnD library. Context menu (right-click) needs `onContextMenu` handling, which is fragile on different platforms.

---

### C: Timeline-Driven

**New Dependencies**: Significant. No MUI component or common React library provides a zoomable, pannable, interactive waterfall/timeline visualization. Options:
- Custom canvas/SVG implementation (high effort, full control)
- `react-flame-graph` (limited, not a waterfall)
- `d3` + custom React bindings (~30KB gzipped, steep learning curve)
- `visx` from Airbnb (~variable, modular, but still requires manual waterfall logic)

The minimap, zoom/pan, and segment selection are all custom work.

**MUI Components Used**: AppBar (context bar), icon rail (same as A), Tabs (detail panel tabs). Almost nothing else -- the timeline zone is entirely custom.

**Estimated New Components**:
- `TimelineCanvas` -- the core waterfall visualization (SVG or Canvas)
- `TimelineBar` -- individual span bar with hover/click/selection
- `TimelineMinimap` -- compressed overview with viewport drag
- `TimeAxis` -- auto-scaling time ruler
- `ZoomPanControls` -- mousewheel zoom, click-drag pan, keyboard shortcuts
- `DetailPanel` -- collapsible bottom panel with resizable divider
- `SegmentTooltip` -- hover tooltip with segment metadata
- `SmartTable` -- for non-timeline views (events list, query list, etc.)

**Migration Strategy**: Essentially a rewrite of the Debug module. The current debug page shows collector data in tabs; this variant replaces that with a timeline-first view. Inspector pages can remain largely unchanged. The data model also needs changes -- collectors currently return key-value data, but the timeline needs start/end timestamps per operation, which may not exist in all collector outputs today. This is a backend dependency, not just frontend.

**Risk Areas**: This is the highest-risk variant. The timeline visualization is a custom graphics component with zoom, pan, hit-testing, and responsive layout -- effectively building a mini-charting library. Performance is critical: hundreds of spans with zoom/pan must render at 60fps, which likely requires Canvas (not DOM). Canvas breaks MUI theming and accessibility. The minimap doubles the rendering cost. The data model dependency (needing timing spans from collectors) may require Kernel changes. Memory leaks from animation frames and resize observers are a real concern.

---

### D: Minimal Zen

**New Dependencies**: None required. A command palette can be built with MUI Autocomplete inside a Dialog/Modal. Page transitions (slide/fade) can use `framer-motion` (~30KB gzipped) or CSS transitions with React Router's built-in animation support.

**MUI Components Used**: AppBar (thin top bar), Autocomplete + Dialog (command palette), Card/CardContent (dashboard cards), Chip (entry pill), Table (clean, borderless tables using MUI Table with minimal styling), Collapse (progressive disclosure), Typography (generous whitespace design).

**Estimated New Components**:
- `TopBar` -- thin bar with logo, entry pill, nav arrows, search trigger, theme toggle
- `EntryPill` -- clickable pill with dropdown entry list
- `CommandPalette` -- Ctrl+K modal with sections (recent, pages, actions)
- `CollectorCard` -- summary card with icon, name, count, sparkline
- `ExpandedCardPanel` -- inline detail that replaces card on click
- `SmartTable` -- minimal chrome variant (no borders, no alternating rows)

**Migration Strategy**: Best incremental path of all variants. The current Layout already uses a top navigation pattern (Breadcrumbs + Menu button that opens a Drawer). Phase 1: Replace the Drawer + Breadcrumbs with the thin TopBar + EntryPill. Phase 2: Add CommandPalette (additive, no existing code changes). Phase 3: Refactor Debug page to use CollectorCards instead of tab-based navigation. Phase 4: Restyle tables to minimal chrome. Each phase is independently deployable. The module system and router stay unchanged -- this variant is purely a UI reskin plus progressive disclosure behavior.

**Risk Areas**: Low overall. The command palette must be performant with many items (routes, actions, recent entries). Vim-style keyboard navigation (J/K) needs global key handlers that do not conflict with form inputs. Page transition animations can cause layout shifts if not carefully implemented. The "full-width content with no sidebar" approach means every page must be self-contained for navigation context -- users may feel lost without persistent nav. User testing needed to validate the palette-only navigation model.

---

### E: Dashboard Grid

**New Dependencies**: Significant.
- Grid layout engine: `react-grid-layout` (~25KB gzipped) -- the de facto library for draggable/resizable grid dashboards
- Charting library for Chart widget type: `recharts` (~50KB) or `nivo` (~variable)
- Possibly `react-resizable` for widget resize handles

Total new dependency weight: ~75-100KB gzipped.

**MUI Components Used**: AppBar (header), Tabs (dashboard tabs), Menu (tab context menu, widget picker, column visibility), Card (widget wrapper), Dialog (settings), DataGrid (table widgets), various for individual widget content.

**Estimated New Components**:
- `GridCanvas` -- react-grid-layout wrapper with 12-column system
- `WidgetWrapper` -- standard widget chrome (title bar, minimize/maximize/close, resize handle)
- `WidgetPicker` -- dialog/drawer to add widgets to dashboard
- `DashboardManager` -- tab management (create, rename, duplicate, delete, import/export)
- `StatusWidget`, `TableWidget`, `LogWidget`, `ChartWidget`, `JsonWidget`, `TimelineWidget` -- 6 distinct widget type renderers
- `DashboardPersistence` -- localStorage serialization/deserialization of layouts
- `ToolbarMiniDashboard` -- compact widget rendering for embedded toolbar
- `SmartTable` -- table widget variant with constrained height

**Migration Strategy**: Most disruptive. The entire page structure changes from route-based pages to widget-based dashboards. Current collector pages become widgets. Current routing (page-per-collector) is replaced by dashboard tabs with widget compositions. The module system needs rethinking -- modules currently provide `routes`, but in this variant they should provide `widgets`. Existing collector components can be wrapped as widgets, but they need to handle constrained sizes they were not designed for. Inspector pages (20+) would each need a widget adapter.

**Risk Areas**: react-grid-layout has known performance issues with many widgets (drag causes full re-renders). Widget resize triggers re-layout of all siblings. Each widget is an independent scroll container, which is confusing for keyboard navigation. Serializing/deserializing dashboard layouts to localStorage is fragile across version upgrades. The widget picker UX is complex (choosing from 20+ collector types). The toolbar's "mini-dashboard" doubles the widget rendering code. Accessibility is poor -- drag-and-drop grid layouts are notoriously hard to make accessible (WCAG drag-and-drop is Level AAA, not AA). Testing is complex because widget positions are dynamic.

---

## Top 3 Ranking (by feasibility + quality)

1. **D: Minimal Zen** (Score: 83) -- Lowest risk, best migration path, no new dependencies, excellent MUI compatibility. The command-palette-first navigation is a proven pattern (Linear, Raycast, Arc). Every feature maps cleanly to existing MUI components. Can be shipped incrementally without breaking current functionality.

2. **A: Command Center** (Score: 78) -- Solid IDE-inspired layout that MUI handles well. The sidebar rail is a natural evolution of the existing MenuPanel. Context bar + tabs + status bar are all standard MUI patterns. SmartTable is the main new work. Requires some custom behavior (command palette, column resize) but nothing exotic.

3. **B: Split Focus** (Score: 71) -- Three-column master-detail is a strong UX pattern, but the always-visible entry list panel and resizable splits push beyond what MUI offers natively. Needs a third-party split-pane library and likely react-window for list virtualization. More restructuring of the current Layout than A or D.

## Recommendation

**Variant D (Minimal Zen)** is the strongest choice from an implementation perspective. It has the lowest risk, fastest time-to-ship, smallest bundle impact, and the most natural migration path from the current codebase. The command palette pattern eliminates the sidebar entirely, which avoids the complex resizable panel work needed by B and E. Every component maps to a standard MUI primitive. The progressive disclosure model (cards -> expanded detail -> full page) reuses existing collector page components with minimal modification.

**Variant A (Command Center)** is the recommended runner-up if stakeholders prefer a more traditional IDE-style layout. It scores nearly as well and the sidebar rail pattern already exists in the codebase (MenuPanel).

**Avoid C (Timeline-Driven) and E (Dashboard Grid)** unless there is a strong product reason. Both require significant new dependencies, custom rendering engines, and would take 3-5x longer to implement than D or A. The timeline visualization (C) has a backend data dependency that may not be satisfiable with current collector architecture. The dashboard grid (E) has inherent accessibility and performance problems that are difficult to solve.

A hybrid approach is also viable: start with D's shell (thin top bar, command palette, full-width content) and adopt A's SmartTable and context bar concepts for the debug detail views. This gives the clean aesthetic of Zen with the information density of Command Center where it matters most.
