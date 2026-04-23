# Frontend Module

React/TypeScript frontend for ADP. Provides a web UI to inspect debug data and application state.

## Tech Stack

- React 19+, TypeScript 6+
- Vite 8+ (build tool)
- Material-UI (MUI) 7+ with DataGrid and TreeView
- Redux Toolkit 2+ with RTK Query (state management and API calls)
- React Router 7 (navigation)
- React Hook Form + Yup (forms and validation)
- Workbox 7 (PWA / Service Worker)
- Lerna 8 (monorepo management)
- Prettier 3.8+ (code formatting)
- ESLint 9 + @typescript-eslint (linting)

## Monorepo Structure

```
packages/
├── panel/                  # Main SPA application
│   ├── src/
│   │   ├── index.tsx       # Entry point
│   │   ├── App.tsx         # Root component (Redux Provider, Router, SSE)
│   │   ├── store.ts        # Redux store factory (reducers, middlewares, persist)
│   │   ├── router.tsx      # Router factory (Browser/Hash, Layout wrapping)
│   │   ├── modules.ts      # Module registry (all ModuleInterface imports)
│   │   ├── Application/    # App shell (Layout, NotFoundPage, Settings)
│   │   └── Module/         # Feature modules
│   │       ├── Debug/      # Debug data viewer (collector panels, timeline, exceptions)
│   │       ├── Inspector/  # Application inspector (28 pages: routes, DB, git, cache, etc.)
│   │       ├── Llm/        # LLM chat and AI-powered analysis (connect, chat, analyze, history)
│   │       ├── Mcp/        # MCP server setup and configuration page
│   │       ├── GenCode/    # Code generation wizard (stepper: generate, preview, result)
│   │       ├── OpenApi/    # Swagger UI integration
│   │       └── Frames/     # iFrame support for remote panels
│   └── vite.config.ts
│
├── toolbar/                # Embeddable toolbar widget
│   ├── src/
│   │   ├── App.tsx
│   │   └── Module/Toolbar/ # Toolbar components (DebugToolbar, metric items)
│   └── vite.config.ts
│
└── sdk/                    # Shared SDK library
    ├── src/
    │   ├── Config.ts       # Build configuration (VITE_BUILD_ID, VITE_ENV)
    │   ├── API/            # API clients (RTK Query)
    │   │   ├── createBaseQuery.ts       # Dynamic base URL factory
    │   │   ├── errorNotificationMiddleware.ts
    │   │   ├── Debug/      # Debug API (debugApi, debugSlice)
    │   │   ├── Application/# Application state (ApplicationSlice)
    │   │   └── Llm/
    │   │       ├── Llm.ts              # LLM API (llmApi) + llmBaseQuery with X-Acp-Session header
    │   │       └── acpSession.ts       # ACP session ID management (sessionStorage, per-tab UUID)
    │   ├── Component/      # Reusable components
    │   │   ├── Theme/
    │   │   │   ├── tokens.ts              # Design tokens (primitives, semantic, dark)
    │   │   │   └── DefaultTheme.tsx       # MUI theme factory (createAppTheme)
    │   │   ├── Layout/
    │   │   │   ├── TopBar.tsx               # Global top bar with entry pill
    │   │   │   ├── UnifiedSidebar.tsx       # Collapsible sidebar with sections
    │   │   │   ├── EntrySelector.tsx        # Debug entry picker + filter config
    │   │   │   ├── EntryFilterConfig.tsx    # Filter builder for debug entries
    │   │   │   ├── CommandPalette.tsx        # Ctrl+K command palette (syncs sidebar)
    │   │   │   ├── RequestPill.tsx           # Compact request summary pill
    │   │   │   ├── SearchTrigger.tsx         # Search icon button (opens palette)
    │   │   │   ├── NavItem.tsx              # Sidebar navigation item
    │   │   │   ├── NavBadge.tsx             # Badge for nav items
    │   │   │   └── ContentPanel.tsx         # Content area wrapper
    │   │   ├── SearchFilter.tsx             # Reusable search filter (hook + component)
    │   │   ├── EmptyState.tsx              # Generic empty state (icon + title + desc)
    │   │   ├── SectionTitle.tsx            # Section heading component
    │   │   ├── SqlHighlight.tsx            # SQL syntax highlighting (Prism, inline/formatted modes)
    │   │   ├── FilterChip.tsx              # Unified colored filter/tag badge (fixes gray-on-hover bug)
    │   │   ├── FilterInput.tsx             # Reusable filter text input with debounce
    │   │   ├── BodyPreview.tsx             # HTTP body preview (JSON, HTML, text)
    │   │   ├── ExplainPlanVisualizer.tsx   # SQL EXPLAIN plan tree visualizer
    │   │   ├── FileLink.tsx               # Clickable file *path* link (File Explorer + Open in Editor). For class names use `panel/Application/Component/ClassName` instead.
    │   │   ├── StackTrace.tsx             # Exception stack trace renderer
    │   │   ├── KeyValueTable.tsx          # Key-value pair table display
    │   │   ├── StatusCard.tsx             # Status indicator card
    │   │   ├── InfoBox.tsx                # Info message box
    │   │   ├── DebugChip.tsx              # Debug status chip
    │   │   ├── ErrorFallback.tsx          # Error boundary fallback UI
    │   │   ├── ServiceSelector.tsx        # Multi-app service selector
    │   │   ├── ServerSentEventsObserver.ts  # SSE connection manager
    │   │   ├── useServerSentEvents.ts       # SSE React hook
    │   │   ├── JsonRenderer.tsx             # JSON display; renders `ClosureDescriptor` markers and `fn(...)=>…` strings as PHP code
    │   │   ├── CodeHighlight.tsx            # Syntax highlighting
    │   │   ├── Form/
    │   │   │   └── FilterInput.tsx          # Form-integrated filter input
    │   │   └── Grid.tsx                     # Data grid wrapper
    │   ├── Adapter/        # Framework adapters
    │   │   ├── mui/        # MUI type extensions
    │   │   ├── yii/        # Yii-specific input matchers
    │   │   └── yup/        # Yup validation adapters
    │   ├── Helper/         # Utility functions (25 helpers)
    │   │   ├── fuzzyMatch.ts              # Fuzzy matching algorithm (score + indices)
    │   │   ├── layoutTranslit.ts          # QWERTY ↔ ЙЦУКЕН transliteration
    │   └── Types/          # TypeScript type definitions
    └── package.json
```

## Module System

Each module implements `ModuleInterface`:
```typescript
interface ModuleInterface {
    routes: RouteObject[];      // React Router route definitions
    reducers: Record<string, Reducer>;  // Redux reducers for the store
    middlewares: Middleware[];   // Redux middlewares (RTK Query, etc.)
    standaloneModule: boolean;  // If true, renders outside main Layout
}
```

Modules are registered in `modules.ts` and composed in `store.ts` (reducers/middlewares) and `router.tsx` (routes). Non-standalone modules are wrapped in the main `Layout` component; standalone modules render independently. Currently all modules are non-standalone (Debug was migrated to the unified layout).

## Layout Architecture

All pages share a single unified layout (`Application/Component/Layout.tsx`):

```
┌─────────────────────────────────────────────┐
│ TopBar (entry pill, nav arrows, search,     │
│         theme toggle, more menu)            │
├────────┬────────────────────────────────────┤
│Sidebar │ Content Area                       │
│ Home   │                                    │
│ Debug  │  <Outlet /> renders page content   │
│  ├ Ov  │                                    │
│  ├ Log │  Debug Layout wraps debug routes   │
│  ├ DB  │  and handles collector data loading │
│  └ All │                                    │
│ Insp.  │                                    │
│  ├ Cfg │                                    │
│  └ ... │                                    │
│ LLM    │                                    │
│  └ MCP │                                    │
│ OpenAPI│                                    │
│ Frames │                                    │
└────────┴────────────────────────────────────┘
```

**Main Layout** manages: debug entry state (SSE, fetching, navigation), entry selector, theme toggle, more menu (repeat request, copy cURL, auto-refresh), command palette (Ctrl+K), and the `UnifiedSidebar` with collapsible sections.

**Debug Layout** (`Module/Debug/Pages/Layout.tsx`) is a thin wrapper that handles collector data loading — reads `collector` from URL params, fetches collector info, and renders the appropriate panel component. It has no shell (TopBar, sidebar) of its own.

**UnifiedSidebar** sections:
- Home, Debug (expandable: collectors + All Entries), Inspector (expandable: 14 sub-pages), LLM (expandable: MCP), GenCode, Open API, Frames
- Debug sub-items are dynamic (built from current entry's collectors)
- Inspector sub-items are static (config, events, routes, etc.)

## Search & Filtering

All search/filter functionality is layout-aware: queries typed on the wrong keyboard layout (QWERTY ↔ ЙЦУКЕН) are auto-transliterated so results still match.

### Core Building Blocks

| File | Purpose |
|------|---------|
| `Helper/fuzzyMatch.ts` | Fuzzy matching algorithm. Returns `{score, indices}` — lower score = better match. Penalizes gaps and late starts, bonuses exact substrings. |
| `Helper/layoutTranslit.ts` | `translit(str)` converts between keyboard layouts. `searchVariants(query)` returns `[original, transliterated]` for dual matching. |
| `Component/SearchFilter.tsx` | Reusable `useSearchFilter<T>` hook + `SearchFilter<T>` component. |

### useSearchFilter Hook

Filters an array of items using layout-aware search. Supports two modes:

- **`'includes'`** (default) — case-insensitive substring match. Returns all items whose search text contains the query.
- **`'fuzzy'`** — fuzzy character matching with scoring. Results sorted by score (best match first). Returns match `indices` for highlighting.

`getSearchText` accepts `string | string[]` for multi-field search.

```tsx
const results = useSearchFilter({
    items: logs,
    query: filter,
    getSearchText: (log) => [log.message, log.level],
    mode: 'fuzzy',
});
// results: SearchMatch<T>[] = [{item, score, indices}, ...]
```

### SearchFilter Component

Self-contained filter input. Manages own query state with `useDeferredValue` for smooth typing. Calls `onChange(results, query)` on every change.

```tsx
<SearchFilter
    items={logs}
    getSearchText={(log) => log.message}
    mode="fuzzy"
    placeholder="Filter logs..."
    onChange={(results, query) => setFiltered(results)}
/>
```

### Where Search is Used

| Location | Component | Mode | Search Fields |
|----------|-----------|------|---------------|
| Command Palette | `CommandPalette.tsx` | includes | label, shortcut |
| Entry Selector | `EntrySelector.tsx` | fuzzy | method + path, command input |
| Log Panel | `LogPanel.tsx` | includes | message, level |
| Entry Filter Config | `EntryFilterConfig.tsx` | condition-based | url, status, type |

## Theming

Theme is built in `sdk/src/Component/Theme/` using a three-layer token architecture:

1. **Primitive tokens** (`tokens.ts: primitives`) — Raw hex values, font families, radii. Never used directly in components.
2. **Semantic tokens** (`tokens.ts: semanticTokens`) — Light-mode palette mapped from primitives. Used in `createTheme()`.
3. **Dark semantic tokens** (`tokens.ts: darkSemanticTokens`) — Dark-mode overrides. Merged when `themeMode === 'dark'`.
4. **Component tokens** (`tokens.ts: componentTokens`) — MUI `styleOverrides` and `defaultProps`.

`DefaultTheme.tsx` exports `createAppTheme(mode)` which composes these layers into a full MUI theme.

**Convention**: Components must use `theme.palette.*`, `theme.spacing()`, and `sx` shorthand strings (`'primary.main'`, `'common.white'`) — never `primitives.*` or hardcoded hex values. This ensures dark mode works correctly.

**Dark mode palette** (key values):
- `background.default: '#0F172A'`, `background.paper: '#1E293B'`
- `text.primary: '#F1F5F9'`, `text.secondary: '#94A3B8'`, `text.disabled: '#64748B'`
- `error.main: '#F87171'`, `error.light: '#7F1D1D'`
- `divider: '#334155'`

## State Management

Redux store is created via `createStore()` in `store.ts`:

```
Reducers:
├── application          # baseUrl, pageSize, toolbar, favorites, autoLatest, themeMode
├── notifications        # toast alerts
├── store.debug          # current debug entry, request IDs
├── store.openApi        # API spec entries (name → URL)
├── store.frames2        # iFrame entries (name → URL)
├── api.debug            # RTK Query cache (debug endpoints)
├── api.inspector        # RTK Query cache (inspector endpoints)
├── api.inspector.git    # RTK Query cache (git endpoints)
├── api.llm              # RTK Query cache (LLM endpoints)
└── api.genCode          # RTK Query cache (code generation endpoints)
```

Key features:
- **Redux Persist**: Application state persisted to localStorage
- **Redux State Sync**: Cross-window/tab state synchronization (toolbar ↔ main app)
- **RTK Query**: Automatic caching, tag-based invalidation, polling

## API Communication

**Dynamic base URL**: `createBaseQuery(prefix)` reads `application.baseUrl` from Redux state at request time, enabling connection to any backend instance.

**RTK Query APIs**:
| API | Prefix | Endpoints |
|-----|--------|-----------|
| `debugApi` | `/debug/api/` | getDebug, getCollectorInfo, getObject |
| `inspectorApi` | `/debug/api/inspector/` | getParameters, getConfiguration, getTable, runCommand, doRequest (20+) |
| `gitApi` | `/debug/api/inspector/git/` | getSummary, getLog, checkout, command |
| `llmApi` | `/inspect/api/llm/` | getStatus, getModels, chat, connect, disconnect, getHistory, addHistory |

**Server-Sent Events (SSE)**:
- Endpoint: `/debug/api/event-stream`
- Hook: `useServerSentEvents(baseUrl, onMessage, subscribe)`
- Used for real-time debug entry notifications

## Rendering Rules

### Class names (PHP FQCN) — always use `ClassName`

Any place that renders a PHP class name (fully-qualified or short) **must** use
`packages/panel/src/Application/Component/ClassName.tsx`. It provides the two
required affordances: a link to the internal File Explorer
(`/inspector/files?class=…`) **and** an "Open in Editor" button that resolves
the source path via the inspector API and opens the user's configured IDE.

```tsx
import {ClassName} from '@app-dev-panel/panel/Application/Component/ClassName';

// Full FQCN, default rendering
<ClassName value={message.messageClass} />

// Short label in a list row, full FQCN drives the links
<ClassName value={fqcn}>{shortClassName(fqcn)}</ClassName>

// Callable — method name is included in both explorer and editor URLs
<ClassName value={action.className} methodName={action.methodName} />
```

Do **not** render class names as plain `Typography`, `styled(Typography)`, or
`<FileLink className=…>`. `FileLink` is for file *paths* only — it has no
`className` prop anymore.

Non-FQCN values (short names without a `\`) render as plain inline text
without buttons, so the component is safe to use for both short and
fully-qualified identifiers.

## Code Quality

```bash
npm run format         # Format with Prettier
npm run format:check   # Check formatting (CI)
npm run lint           # ESLint check
npm run lint:fix       # ESLint auto-fix
npm run check          # Run all checks
```

**Prettier** (v3.8+): Single quotes, trailing commas, 120 char width, 4-space indent, `objectWrap: "collapse"`, `prettier-plugin-organize-imports`.

**ESLint**: @typescript-eslint with `consistent-type-definitions: "type"`, integrated with Prettier via `eslint-config-prettier`.

## Build & Development

```bash
npm install              # Install all workspace dependencies
npm start                # Start all Vite dev servers (via Lerna)
npm run build            # Production build all packages (via Lerna)
npm run build:dev        # Development build with toolbar bundled
```

## Screenshots

Take screenshots of the running frontend using Playwright (not Selenium — version mismatch issues with ChromeDriver).

**Quick CLI**:
```bash
npx playwright screenshot --browser chromium --wait-for-timeout 5000 --full-page \
  --viewport-size "1920,1080" http://localhost:5173/ /tmp/screenshot.png
```

**Node.js script** (for React SPA — waits for render):
```bash
NODE_PATH=/opt/node22/lib/node_modules node -e "
const { chromium } = require('playwright');
(async () => {
  const browser = await chromium.launch({ headless: true });
  const page = await browser.newPage({ viewport: { width: 1920, height: 1080 } });
  await page.goto('http://localhost:5173/', { waitUntil: 'networkidle' });
  await page.waitForTimeout(3000);
  await page.screenshot({ path: '/tmp/screenshot.png', fullPage: true });
  await browser.close();
})();
"
```

View with `Read /tmp/screenshot.png`. See `/screenshot` skill for full documentation.

## Responsive Design

Uses MUI breakpoints (`theme.breakpoints.down('sm')`, `theme.breakpoints.down('md')`) for mobile/tablet adaptation.

| Breakpoint | Width | Layout |
|------------|-------|--------|
| `xs` | 0-599px | Mobile: sidebar as drawer, single-column grids, hidden secondary info |
| `sm` | 600-899px | Small tablet: reduced gaps/padding, narrower labels |
| `md` | 900-1199px | Tablet: sidebar visible, 2-column grids |
| `lg+` | 1200px+ | Desktop: full layout, 4-column grids |

**Layout** (`Application/Component/Layout.tsx`): Below `md`, sidebar collapses into a `Drawer` with hamburger menu. Content area gets full width.

**Responsive patterns used in components**:

| Pattern | Where | Behavior on mobile (`< sm`) |
|---------|-------|-----------------------------|
| Hide secondary cells | `DebugEntryList`, `EventPanel`, `TimelinePanel` | `StatCell`, `MetaLabel`, `FileCell`, `Duration` hidden |
| Ellipsis truncation | `EventPanel.NameCell`, `RoutesPage.PatternCell` | Text truncated with `...` instead of wrapping |
| Flex-wrap | `RequestPanel.MetricBox` | URL + chips wrap to next line |
| Column stacking | `DashboardPage`, Config pages | `flex-direction: column` on mobile |
| Reduced spacing | `EventRow`, `EntryRow`, `RouteRow` | Smaller gap and padding |
| Auto-width labels | `TimelinePanel.Label`, `EventPanel.TimeCell` | Fixed widths removed or shrunk |
| Responsive grid | `DashboardPage.HealthGrid` | `repeat(4, 1fr)` -> `repeat(2, 1fr)` -> `1fr` |

## PWA Support

Service Worker via Workbox provides offline caching and background sync.
Build version tracked via `VITE_BUILD_ID` env variable.
