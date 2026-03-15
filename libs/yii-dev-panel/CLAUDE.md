# Frontend Module

React/TypeScript frontend for ADP. Provides a web UI to inspect debug data and application state.

## Tech Stack

- React 18.3+, TypeScript 5.5+
- Vite (build tool)
- Material-UI (MUI) 5+ with DataGrid and TreeView
- Redux Toolkit + RTK Query (state management and API calls)
- React Router 6 (navigation)
- React Hook Form (forms)
- Workbox (PWA / Service Worker)

## Monorepo Structure

```
packages/
├── yii-dev-panel/          # Main SPA application
│   ├── src/
│   │   ├── index.tsx       # Entry point
│   │   ├── App.tsx         # Root component
│   │   ├── store.ts        # Redux store configuration
│   │   ├── router.tsx      # Router factory
│   │   ├── Application/    # App shell (Layout, NotFoundPage, Settings)
│   │   └── Module/         # Feature modules
│   │       ├── Debug/      # Debug data viewer
│   │       ├── Inspector/  # Application inspector
│   │       ├── Gii/        # Code generator UI
│   │       ├── OpenApi/    # Swagger UI integration
│   │       └── Frames/     # iFrame support for remote panels
│   └── vite.config.ts
│
├── yii-dev-toolbar/        # Embeddable toolbar widget
│   ├── src/
│   │   ├── App.tsx
│   │   └── Module/Toolbar/ # Toolbar components
│   └── vite.config.ts
│
└── yii-dev-panel-sdk/      # Shared SDK library
    ├── src/
    │   ├── Config.ts       # Build configuration
    │   ├── API/            # API clients (RTK Query)
    │   │   ├── createBaseQuery.ts
    │   │   ├── Debug/      # Debug API client
    │   │   └── Application/# Application API client
    │   ├── Component/      # Reusable components
    │   │   ├── ServerSentEventsObserver.ts
    │   │   └── useServerSentEvents.ts
    │   ├── Adapter/        # Framework adapters
    │   │   ├── mui/        # MUI type extensions
    │   │   ├── yii/        # Yii-specific input matchers
    │   │   └── yup/        # Yup validation adapters
    │   ├── Helper/         # Utility functions
    │   ├── Store/          # Redux store slices
    │   └── Types/          # TypeScript type definitions
    └── package.json
```

## Feature Modules

### Debug Module (`/debug/*`)
- **ListPage**: Browse all debug entries with timestamps, URLs, status codes
- **IndexPage**: Detailed view of a single debug entry with collector tabs
- **DumpPage**: Object dump viewer with expandable tree
- **ObjectPage**: Single object deep inspection

### Inspector Module (`/inspector/*`)
- **RoutesPage**: Browse application routes with middleware info
- **EventsPage**: View registered event listeners
- **DatabasePage**: Browse tables, view schemas and records
- **TablePage**: Single table viewer with schema and data
- **CommandsPage**: Execute PHPUnit, Codeception, Psalm
- **TestsPage**: Test results viewer
- **FileExplorerPage**: Browse application source files
- **ContainerEntryPage**: Inspect DI container entries
- **ComposerPage**: View and manage Composer packages
- **TranslationsPage**: Browse and edit translations
- **CachePage**: View and clear cache entries
- **OpcachePage**: OPcache status and config
- **PhpInfoPage**: PHP info viewer
- **AnalysePage**: Static analysis results

### Gii Module (`/gii/*`)
- Code generation wizard UI
- Step-by-step generator interface

### OpenAPI Module (`/open-api/*`)
- Swagger UI integration for API documentation

## API Communication

Uses **RTK Query** (Redux Toolkit Query) for all API calls.

Base URL is dynamic, read from Redux state (`application.baseUrl`):
```typescript
const baseQuery = createBaseQuery('/debug/api');
```

SSE integration via `useServerSentEvents` hook for real-time updates.

## State Management

- **Redux Toolkit** with slices per module
- **Redux Persist** for local storage persistence
- **Redux State Sync** for cross-window/tab communication

## Build & Development

```bash
npm install              # Install dependencies
npm run dev              # Start Vite dev server
npm run build            # Production build
npm test                 # Run Vitest tests
```

## Module System

Modules are registered via `ModuleInterface`:
```typescript
interface ModuleInterface {
    routes: RouteObject[];
    standaloneModule?: boolean;
}
```

The router supports both `BrowserRouter` and `HashRouter` modes.

## PWA Support

Service Worker via Workbox provides offline caching and background sync.
Build version is tracked via `VITE_BUILD_ID` env variable.
