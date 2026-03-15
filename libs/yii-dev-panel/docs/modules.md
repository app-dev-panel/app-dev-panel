# Frontend Modules

## Module Architecture

The frontend is organized into self-contained feature modules. Each module defines its own:
- Routes (React Router)
- Pages (React components)
- Components (module-specific UI elements)
- API slices (RTK Query endpoints)

Modules implement the `ModuleInterface`:
```typescript
interface ModuleInterface {
    routes: RouteObject[];
    standaloneModule?: boolean;  // If true, renders outside the main Layout
}
```

## Debug Module

**Path prefix**: `/debug`

The primary module for inspecting collected debug data.

### Pages

| Page | Route | Description |
|------|-------|-------------|
| ListPage | `/debug` | List of all debug entries with summary info |
| IndexPage | `/debug/{id}` | Single entry detail view with collector tabs |
| DumpPage | `/debug/{id}/dump` | Object dumps for the entry |
| ObjectPage | `/debug/{id}/object/{objectId}` | Deep inspection of a single object |

### Layout

The Debug Layout provides:
- Entry list sidebar
- Collector tab navigation
- Real-time updates via SSE (new entries appear automatically)

### Data Flow

```
SSE notifies new entry → Redux action dispatched → List refetched → UI updated
User clicks entry → RTK Query fetches detail → Collector data rendered in tabs
```

## Inspector Module

**Path prefix**: `/inspector`

Introspects the live application state.

### Pages

| Page | Route | Description |
|------|-------|-------------|
| RoutesPage | `/inspector/routes` | All routes with methods, path, middleware, action |
| EventsPage | `/inspector/events` | Event listeners grouped by event class |
| DatabasePage | `/inspector/database` | Tables list with column info |
| TablePage | `/inspector/database/{name}` | Table schema + records grid |
| CommandsPage | `/inspector/commands` | Available commands for execution |
| TestsPage | `/inspector/tests` | Test execution results |
| FileExplorerPage | `/inspector/files` | Source code file browser |
| ContainerEntryPage | `/inspector/container` | DI container entries |
| ComposerPage | `/inspector/composer` | Composer packages |
| TranslationsPage | `/inspector/translations` | Translation catalogs |
| CachePage | `/inspector/cache` | Cache viewer/clearer |
| OpcachePage | `/inspector/opcache` | OPcache status |
| PhpInfoPage | `/inspector/phpinfo` | PHP configuration |
| AnalysePage | `/inspector/analyse` | Static analysis results |

## Gii Module

**Path prefix**: `/gii`

Code generation wizard. Currently supports controller generation.
Multi-step form interface using React Hook Form.

## OpenAPI Module

**Path prefix**: `/open-api`

Embeds Swagger UI React for interactive API documentation viewing.

## Frames Module

**Path prefix**: `/frames`

Supports rendering remote panels in iframes. Used with Module Federation
for loading external collector UIs dynamically.

## Adding a New Module

1. Create directory: `src/Module/MyModule/`
2. Add `router.tsx` with route definitions
3. Create page components in `Pages/`
4. Register the module in `App.tsx`

```typescript
// src/Module/MyModule/router.tsx
import { RouteObject } from 'react-router-dom';
import { MyPage } from './Pages/MyPage';

export const routes: RouteObject[] = [
    { path: 'my-module', element: <MyPage /> },
];

// Module registration
const myModule: ModuleInterface = {
    routes,
    standaloneModule: false,
};
```
