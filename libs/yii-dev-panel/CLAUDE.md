# Frontend Module

React/TypeScript frontend for ADP. Provides a web UI to inspect debug data and application state.

## Tech Stack

- React 18.3+, TypeScript 5.5+
- Vite 5.4+ (build tool)
- Material-UI (MUI) 5+ with DataGrid and TreeView
- Redux Toolkit 1.9+ with RTK Query (state management and API calls)
- React Router 6 (navigation)
- React Hook Form + Yup (forms and validation)
- Workbox 7 (PWA / Service Worker)
- Lerna 8 (monorepo management)
- Prettier 3.8+ (code formatting)
- ESLint 9 + @typescript-eslint (linting)

## Monorepo Structure

```
packages/
├── yii-dev-panel/          # Main SPA application
│   ├── src/
│   │   ├── index.tsx       # Entry point
│   │   ├── App.tsx         # Root component (Redux Provider, Router, SSE)
│   │   ├── store.ts        # Redux store factory (reducers, middlewares, persist)
│   │   ├── router.tsx      # Router factory (Browser/Hash, Layout wrapping)
│   │   ├── modules.ts      # Module registry (all ModuleInterface imports)
│   │   ├── Application/    # App shell (Layout, NotFoundPage, Settings)
│   │   └── Module/         # Feature modules
│   │       ├── Debug/      # Debug data viewer
│   │       ├── Inspector/  # Application inspector (20+ pages)
│   │       ├── Gii/        # Code generator UI
│   │       ├── OpenApi/    # Swagger UI integration
│   │       └── Frames/     # iFrame support for remote panels
│   └── vite.config.ts
│
├── yii-dev-toolbar/        # Embeddable toolbar widget
│   ├── src/
│   │   ├── App.tsx
│   │   └── Module/Toolbar/ # Toolbar components (DebugToolbar, metric items)
│   └── vite.config.ts
│
└── yii-dev-panel-sdk/      # Shared SDK library
    ├── src/
    │   ├── Config.ts       # Build configuration (VITE_BUILD_ID, VITE_ENV)
    │   ├── API/            # API clients (RTK Query)
    │   │   ├── createBaseQuery.ts       # Dynamic base URL factory
    │   │   ├── errorNotificationMiddleware.ts
    │   │   ├── Debug/      # Debug API (debugApi, debugSlice)
    │   │   └── Application/# Application state (ApplicationSlice)
    │   ├── Component/      # Reusable components
    │   │   ├── ServerSentEventsObserver.ts  # SSE connection manager
    │   │   ├── useServerSentEvents.ts       # SSE React hook
    │   │   ├── JsonRenderer.tsx             # JSON display component
    │   │   ├── CodeHighlight.tsx            # Syntax highlighting
    │   │   ├── MenuPanel.tsx                # Sidebar menu
    │   │   └── Grid.tsx                     # Data grid wrapper
    │   ├── Adapter/        # Framework adapters
    │   │   ├── mui/        # MUI type extensions
    │   │   ├── yii/        # Yii-specific input matchers
    │   │   └── yup/        # Yup validation adapters
    │   ├── Helper/         # Utility functions (30+ helpers)
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

Modules are registered in `modules.ts` and composed in `store.ts` (reducers/middlewares) and `router.tsx` (routes). Non-standalone modules are wrapped in the main `Layout` component; standalone modules render independently.

## State Management

Redux store is created via `createStore()` in `store.ts`:

```
Reducers:
├── application          # baseUrl, pageSize, toolbar, favorites, autoLatest
├── notifications        # toast alerts
├── store.debug          # current debug entry, request IDs
├── store.openApi        # API spec entries (name → URL)
├── store.frames2        # iFrame entries (name → URL)
├── api.debug            # RTK Query cache (debug endpoints)
├── api.inspector        # RTK Query cache (inspector endpoints)
├── api.inspector.git    # RTK Query cache (git endpoints)
└── api.gii              # RTK Query cache (gii endpoints)
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
| `giiApi` | `/gii/api` | getGenerators, postPreview, postGenerate, postDiff |

**Server-Sent Events (SSE)**:
- Endpoint: `/debug/api/event-stream`
- Hook: `useServerSentEvents(baseUrl, onMessage, subscribe)`
- Used for real-time debug entry notifications

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

## PWA Support

Service Worker via Workbox provides offline caching and background sync.
Build version tracked via `VITE_BUILD_ID` env variable.
