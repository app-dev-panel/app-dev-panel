# SDK Package

The SDK (`app-dev-panel-sdk`) is a shared library used by both the main SPA and the toolbar.
It provides API clients, reusable components, state management, helpers, and type definitions.

## Package Exports

The SDK is published as an ESM package with path-based exports:
```typescript
import {createBaseQuery} from '@app-dev-panel/sdk/API/createBaseQuery';
import {JsonRenderer} from '@app-dev-panel/sdk/Component/JsonRenderer';
import {formatDate} from '@app-dev-panel/sdk/Helper/formatDate';
```

## API Layer

### createBaseQuery

Factory for RTK Query base queries. Reads `application.baseUrl` from Redux state at request time, enabling dynamic backend connection:

```typescript
export const createBaseQuery = (baseUrlAdditional: string) => {
    return async (args, WebApi, extraOptions) => {
        const baseUrl = (WebApi.getState() as any).application?.baseUrl || '';
        const rawBaseQuery = fetchBaseQuery({
            baseUrl: baseUrl.replace(/\/$/, '') + baseUrlAdditional,
            referrerPolicy: 'no-referrer',
            headers: {Accept: 'application/json', 'Content-Type': 'application/json'},
        });
        return rawBaseQuery(args, WebApi, extraOptions);
    };
};
```

### Debug API (`API/Debug/`)

| File | Purpose |
|------|---------|
| `Debug.ts` | `debugApi` RTK Query API with endpoints: getDebug, getCollectorInfo, getObject |
| `Context.ts` | `debugSlice` Redux slice (current entry, request IDs) + hooks |
| `api.ts` | Exports `reducers` and `middlewares` for store composition |

**DebugEntry type**: Comprehensive type covering all collector summary data (web, console, request, response, router, middleware, exception, db, logger, event, service, queue, etc.)

### Application API (`API/Application/`)

| File | Purpose |
|------|---------|
| `ApplicationContext.tsx` | `ApplicationSlice` Redux slice with application settings |
| `api.ts` | Exports `reducers` and `middlewares` for store composition |

**ApplicationSlice state**:
- `baseUrl`: Backend URL (user-configurable)
- `preferredPageSize`: Default page size for grids
- `toolbarOpen`: Toolbar visibility
- `favoriteUrls`: User's bookmarked URLs
- `autoLatest`: Auto-select latest debug entry
- `iframeHeight`: Configurable iframe height

### Middleware

| File | Purpose |
|------|---------|
| `errorNotificationMiddleware.ts` | Dispatches notification on any rejected RTK Query action |
| `consoleLogActionsMiddleware.ts` | Logs all Redux actions to browser console (dev only) |

## Components

### SSE (Server-Sent Events)

| Component | Purpose |
|-----------|---------|
| `ServerSentEventsObserver.ts` | Observer pattern class managing EventSource lifecycle (connect, subscribe, unsubscribe, close) |
| `useServerSentEvents.ts` | React hook wrapping the observer with auto-cleanup |

Usage:
```typescript
useServerSentEvents(backendUrl, (event: MessageEvent<EventTypes>) => {
    // event.type === 'debug-updated'
}, true);
```

### UI Components

| Component | Purpose |
|-----------|---------|
| `JsonRenderer.tsx` | JSON display with `@textea/json-viewer` |
| `CodeHighlight.tsx` | Syntax highlighting via `react-syntax-highlighter` |
| `MenuPanel.tsx` | Sidebar navigation panel |
| `Grid.tsx` | MUI DataGrid wrapper with common configuration |
| `Notifications.tsx` | Toast notification reducer and display |

### Search & Filter

| Component | Purpose |
|-----------|---------|
| `SearchFilter.tsx` | `useSearchFilter<T>` hook + `SearchFilter<T>` component — reusable layout-aware search |

**`useSearchFilter<T>`** hook — filters items with layout-aware search (QWERTY ↔ ЙЦУКЕН auto-transliteration):
- `mode: 'includes'` (default): case-insensitive substring match
- `mode: 'fuzzy'`: fuzzy character matching with score-based sorting, returns match indices for highlighting
- `getSearchText`: accepts `string | string[]` for multi-field search

```typescript
const results = useSearchFilter({
    items: logs,
    query: filter,
    getSearchText: (log) => [log.message, log.level],
    mode: 'fuzzy',
});
// SearchMatch<T>[] = [{item, score, indices}, ...]
```

**`SearchFilter<T>`** component — self-contained filter input with `useDeferredValue`:
```tsx
<SearchFilter
    items={logs}
    getSearchText={(log) => log.message}
    placeholder="Filter logs..."
    onChange={(results, query) => setFiltered(results)}
/>
```

## Helpers

30+ utility functions in `Helper/`:

| Helper | Purpose |
|--------|---------|
| `formatDate.ts` | Date/time formatting with `date-fns` |
| `formatBytes.ts` | Byte size formatting (KB, MB, GB) |
| `objectString.ts` | PHP object string representation |
| `classMatcher.ts` | PHP class name matching and highlighting |
| `classMethodConcater.ts` | Format `Class::method` strings |
| `filePathParser.ts` | Parse and link file paths |
| `callableSerializer.ts` | Serialize PHP callables for display |
| `tagMatcher.ts` | Match and highlight DI tags |
| `buttonColor.ts` | HTTP method → MUI color mapping |
| `collectors.ts` | Collector name utilities |
| `collectorsTotal.ts` | Calculate collector totals from summary |
| `debugEntry.ts` | Debug entry type detection (web vs console) |
| `fuzzyMatch.ts` | Fuzzy matching algorithm — returns `{score, indices}`, lower score = better match |
| `layoutTranslit.ts` | QWERTY ↔ ЙЦУКЕН transliteration, `searchVariants()` for layout-aware search |
| `regexpQuote.ts` | Escape regex special characters |
| `scrollToAnchor.ts` | Smooth scroll to page anchors |
| `queue.ts` | Simple FIFO queue |
| `dispatchWindowEvent.ts` | Cross-window event dispatching (postMessage) |
| `IFrameWrapper.ts` | iFrame communication utilities |

## Adapters

### MUI Adapter (`Adapter/mui/`)
Type extensions for Material-UI components (column definitions, grid props).

### Yii Adapter (`Adapter/yii/`)
Input type matcher — maps Yii attribute types to HTML input types for form generation.

### Yup Adapter (`Adapter/yup/`)
Yup validation schema generators — converts Yii validator rules to Yup schemas for React Hook Form.

## Types

| File | Purpose |
|------|---------|
| `Module.types.ts` | `ModuleInterface` — contract for feature modules |
| `Gii/index.ts` | `GiiGeneratorAttribute` — Gii generator attribute types |

## Configuration

```typescript
// Config.ts
export const Config = {
    buildVersion: import.meta.env.VITE_BUILD_ID || 'development',
    appEnv: import.meta.env.VITE_ENV || 'development',
};
```

Environment variables:
- `VITE_BUILD_ID`: Git short hash or CI build ID
- `VITE_ENV`: `dev`, `github`, or custom environment
