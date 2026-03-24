# ADP Use Cases & UX Requirements

## Current Page Inventory

### Debug Module (runtime request/command inspection)
| Route | Page | Purpose |
|-------|------|---------|
| `/debug` | Layout + IndexPage | Select debug entry, pick collector, view data |
| `/debug/list` | ListPage | All debug entries in a table |
| `/debug/object` | ObjectPage | Inspect a single serialized object |

### Inspector Module (live application introspection)
| Route | Page | Purpose |
|-------|------|---------|
| `/inspector/config` | ConfigurationPage | Application configuration tree |
| `/inspector/config/:page` | ConfigurationPage | Configuration sub-page (parameters, definitions, container) |
| `/inspector/events` | EventsPage | Registered event listeners |
| `/inspector/routes` | RoutesPage | Application routes table |
| `/inspector/tests` | TestsPage | Run and view test results |
| `/inspector/analyse` | AnalysePage | Static analysis results |
| `/inspector/files` | FileExplorerPage | Browse application files |
| `/inspector/translations` | TranslationsPage | Translation strings |
| `/inspector/commands` | CommandsPage | CLI commands list, run commands |
| `/inspector/database` | DatabasePage | Database schema browser |
| `/inspector/database/:table` | TablePage | View/query a specific table |
| `/inspector/phpinfo` | PhpInfoPage | PHP info output |
| `/inspector/composer` | ComposerPage | Installed packages |
| `/inspector/opcache` | OpcachePage | OPcache status |
| `/inspector/container/view` | ContainerEntryPage | DI container entry details |
| `/inspector/git` | GitPage | Git repository summary |
| `/inspector/git/log` | GitLogPage | Git commit log |
| `/inspector/cache` | CachePage | Cache contents |

### GenCode Module (code generation)
| Route | Page | Purpose |
|-------|------|---------|
| `/gen-code` | Layout | Select generator, fill form, preview, generate |

### OpenAPI Module
| Route | Page | Purpose |
|-------|------|---------|
| `/open-api` | Layout | Swagger UI for API spec |

### Frames Module
| Route | Page | Purpose |
|-------|------|---------|
| `/frames` | Layout | Manage and view iFrame-embedded remote panels |

### Toolbar (separate package, embedded in target app)
- Fixed bottom bar with metrics: status code, method, time, memory, route, logs, events, validator, date
- SpeedDial FAB for quick actions
- Resizable iframe with full debug panel

---

## Identified UX Problems

### P1: Navigation friction
- Sidebar is a Drawer that fully opens/closes — wastes space or hides navigation
- 6 top-level groups, 20+ pages total — flat list without grouping priority
- No keyboard shortcuts for navigation
- No way to pin frequently used pages
- Breadcrumbs are manual (context-based), not URL-derived — inconsistent

### P2: Debug entry context loss
- Switching between Debug and Inspector loses debug entry context
- No persistent header showing current request/command info
- URL state (`?debugEntry=`) only preserved in Debug module
- No way to compare two debug entries side-by-side

### P3: Tables are basic
- MUI DataGrid with minimal customization
- No column resize persistence
- No saved filter presets
- No inline search/filter per column
- No row grouping or nested rows
- Pagination via URL params but sort/filter not persisted in URL

### P4: Action feedback is inconsistent
- Some buttons show Check/Error icons after action, some don't
- No unified status bar for last action result
- Loading states vary: CircularProgress, LinearProgress, FullScreenCircularProgress
- No action history or undo

### P5: Information density
- Large spacing wastes screen real estate
- JSON viewer takes too much vertical space for simple values
- No compact/dense mode toggle
- Collector panels don't highlight anomalies (errors, slow queries, etc.)

### P6: Missing features for debugging workflow
- No diff/compare between two debug entries
- No search across all collector data
- No bookmarks/annotations on entries
- No export/share debug entry
- No timeline visualization across collectors

---

## UX Requirements

### R1: Fast Access
- Persistent mini sidebar (icon-only, always visible, expandable)
- Keyboard shortcuts: Ctrl+K command palette, Ctrl+1..9 for modules
- Recent pages list
- Pin favorite pages

### R2: Context Persistence
- Current debug entry shown in persistent header bar
- URL encodes full state: page, entry, collector, filters, sort, expanded panels
- Inspector pages remember last state when returning
- Cross-module debug entry context (Debug collector selected → Inspector shows same request context)

### R3: Smart Tables
- Column resize with persistence (localStorage)
- Per-column filter dropdowns
- Sortable columns with URL persistence
- Density toggle (compact/comfortable/spacious)
- Row count in header
- Exportable (CSV/JSON)
- Keyboard navigation (arrow keys, Enter to expand)

### R4: Action Feedback
- Unified status bar at bottom showing last action result (method, status, duration)
- Toast notifications for async actions
- Loading skeleton instead of spinners for data areas
- Action buttons show state: idle → loading → success/error (auto-reset after 3s)

### R5: Information Density
- Highlight anomalies: red badges for errors, yellow for warnings, green for success
- Collapsible sections with summary counts
- Compact JSON viewer for simple values (inline), expanded for complex
- Sparkline charts for numeric collectors (memory, time, query count)

### R6: Compare & Analyze
- Side-by-side debug entry comparison
- Diff view for configuration changes
- Timeline visualization across all collectors
- Search across all collector data in a debug entry

### R7: Responsive & Accessible
- Works on 1280px+ screens (developer laptops)
- Dark/light mode (already exists, preserve)
- Focus indicators for keyboard navigation
- Minimum touch target 44px for toolbar
