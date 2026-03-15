# SDK Package

The SDK (`yii-dev-panel-sdk`) is a shared library used by both the main SPA and the toolbar.
It provides API clients, reusable components, helpers, and type definitions.

## API Clients

### createBaseQuery

Factory for RTK Query base queries. Reads the API base URL from Redux state dynamically:

```typescript
import { createBaseQuery } from '@yiisoft/yii-dev-panel-sdk/API/createBaseQuery';

const debugApi = createApi({
    baseQuery: createBaseQuery('/debug/api'),
    endpoints: (builder) => ({
        getEntries: builder.query({ query: () => '/' }),
    }),
});
```

### Debug API (`API/Debug/`)

- `Debug.ts` — Debug entry types and interfaces
- `Context.ts` — Debug context types
- `api.ts` — RTK Query API slice for debug endpoints

### Application API (`API/Application/`)

- `api.ts` — RTK Query API slice for inspector endpoints

### Middleware

- `errorNotificationMiddleware.ts` — Shows error notifications on API failures
- `consoleLogActionsMiddleware.ts` — Logs Redux actions to console (dev mode)

## Components

### ServerSentEventsObserver

Class that manages SSE connection lifecycle:

```typescript
import { ServerSentEventsObserver } from '@yiisoft/yii-dev-panel-sdk/Component/ServerSentEventsObserver';

const observer = new ServerSentEventsObserver('/debug/api/event-stream');
observer.subscribe((event) => {
    // Handle new debug entries
});
```

### useServerSentEvents

React hook wrapper around `ServerSentEventsObserver`:

```typescript
const { connected, lastEvent } = useServerSentEvents('/debug/api/event-stream');
```

## Helpers

| Helper | Purpose |
|--------|---------|
| `formatDate.ts` | Date/time formatting |
| `formatBytes.ts` | Byte size formatting (KB, MB, etc.) |
| `objectString.ts` | PHP object string representation |
| `classMatcher.ts` | PHP class name matching and highlighting |
| `classMethodConcater.ts` | Format `Class::method` strings |
| `filePathParser.ts` | Parse and link file paths |
| `callableSerializer.ts` | Serialize PHP callables for display |
| `tagMatcher.ts` | Match and highlight DI tags |
| `buttonColor.ts` | HTTP method → color mapping |
| `collectors.ts` | Collector name utilities |
| `collectorsTotal.ts` | Calculate collector totals |
| `debugEntry.ts` | Debug entry type utilities |
| `regexpQuote.ts` | Escape regex special characters |
| `scrollToAnchor.ts` | Smooth scroll to page anchors |
| `queue.ts` | Simple queue implementation |
| `dispatchWindowEvent.ts` | Cross-window event dispatching |
| `IFrameWrapper.ts` | iFrame communication utilities |

## Adapters

### MUI Adapter (`Adapter/mui/`)
Type extensions for Material-UI components.

### Yii Adapter (`Adapter/yii/`)
Input type matcher for Yii-specific form field types.

### Yup Adapter (`Adapter/yup/`)
Yup validation schema generators for Yii validator rules.

## Types

### Module Types (`Types/Module.types.ts`)
```typescript
interface ModuleInterface {
    routes: RouteObject[];
    standaloneModule?: boolean;
}
```

### Gii Types (`Types/Gii/`)
Type definitions for code generation interfaces.

## Configuration

```typescript
// Config.ts
export const Config = {
    buildVersion: import.meta.env.VITE_BUILD_ID || 'development',
    appEnv: import.meta.env.VITE_ENV || 'development',
};
```

## Store Slices

Shared Redux slices used across modules. Includes application state
(base URL, settings) and debug entry state.
