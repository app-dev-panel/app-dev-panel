# Frontend Modules

## Module Architecture

The frontend is organized into self-contained feature modules. Each module defines its own:
- **Routes** — React Router route definitions
- **Reducers** — Redux Toolkit reducers for the store
- **Middlewares** — Redux middlewares (typically RTK Query API middleware)
- **Pages** — React components for each route
- **API slices** — RTK Query endpoint definitions

Modules implement the `ModuleInterface`:
```typescript
interface ModuleInterface {
    routes: RouteObject[];
    reducers: Record<string, Reducer>;
    middlewares: Middleware[];
    standaloneModule: boolean;
}
```

### Module Registration Flow

```
modules.ts          → Lists all ModuleInterface instances
    ↓
store.ts            → Collects reducers + middlewares from each module's api.ts
    ↓
router.tsx          → Collects routes, separates standalone vs Layout-wrapped
    ↓
App.tsx             → Creates store, creates router, renders Provider + RouterProvider
```

Each module exports from its `index.ts`:
```typescript
// Module/Debug/index.ts
export const DebugModule: ModuleInterface = {
    routes,
    reducers,
    middlewares,
    standaloneModule: false,
};
```

Each module also exports from its `api.ts`:
```typescript
// Module/Inspector/api.ts
export const reducers = {
    [inspectorApi.reducerPath]: inspectorApi.reducer,
    [gitApi.reducerPath]: gitApi.reducer,
};
export const middlewares = [inspectorApi.middleware, gitApi.middleware];
```

## Module List

| Module | Path | Standalone | Description |
|--------|------|-----------|-------------|
| Application | `/`, `/shared` | No | App shell, home page, shared layout |
| Debug | `/debug/*` | No | Debug entry viewer with collector tabs |
| Inspector | `/inspector/*` | No | Live application introspection (20+ pages) |
| Gii | `/gii/*` | No | Code generation wizard |
| OpenAPI | `/open-api/*` | No | Swagger UI integration |
| Frames | `/frames/*` | No | iFrame manager for remote panels |

## Debug Module

**Path prefix**: `/debug`

The primary module for inspecting collected debug data. Uses `debugApi` from the SDK.

### Pages

| Page | Route | Description |
|------|-------|-------------|
| Layout | `/debug` | Entry list sidebar + collector tabs + SSE updates |
| IndexPage | `/debug` (index) | Single entry detail view with collector data |
| ListPage | `/debug/list` | All debug entries with summary info |
| ObjectPage | `/debug/object` | Deep inspection of a single serialized object |

### Data Flow

```
Backend collects data → SSE notifies frontend ("debug-updated")
    → Redux action dispatched → debugApi.getDebug refetched
    → Entry list re-rendered → User clicks entry
    → debugApi.getCollectorInfo fetches collector detail
    → Tab content rendered with collector-specific components
```

### API Endpoints (debugApi)

| Endpoint | Type | Path | Description |
|----------|------|------|-------------|
| getDebug | Query | `/` | Fetch all debug entries (summary) |
| getCollectorInfo | Query | `/view/{id}?collector={name}` | Fetch collector detail for an entry |
| getObject | Query | `/object/{entryId}/{objectId}` | Fetch serialized object |

### State

- `debugSlice` (name: `store.debug`): current entry, current page request IDs
- `useDebugEntry()`: hook to access the currently selected debug entry
- `useCurrentPageRequestIds()`: hook to access request IDs on the current page

### Collector Panel Components

Located in `src/Module/Debug/Component/Panel/`. Each panel renders data from a specific backend collector. All panels follow the zen-minimal design pattern:

- **Styled components** with design tokens from `@yiisoft/yii-dev-panel-sdk/Component/Theme/tokens`
- **Expandable rows** using MUI `Collapse` with expand/collapse icons
- **Consistent layout**: `SectionTitle` headers, mono-font for code/time, color-coded badges
- **Filter inputs** where applicable (LogPanel, EventPanel, DatabasePanel, ServicesPanel)
- **Empty state** via `Alert` with descriptive message

| Component | File | Data Source | Features |
|-----------|------|-------------|----------|
| `LogPanel` | `LogPanel.tsx` | `LogCollector` | Level badges (color-coded), filter, expandable context/file links |
| `ExceptionPanel` | `ExceptionPanel.tsx` | `ExceptionCollector` | Red index badges, inline code preview, collapsible stack trace, file links |
| `EventPanel` | `EventPanel.tsx` | `EventCollector` | EVENT badges, filter, object examine links, file links |
| `RequestPanel` | `RequestPanel.tsx` | `RequestCollector` | Method/status color chips, sectioned request/response, collapsible raw views |
| `DatabasePanel` | `DatabasePanel.tsx` | `DatabaseCollector` | SQL type badges, duration color-coding, row counts, filter, tabs (queries/transactions) |
| `TimelinePanel` | `TimelinePanel.tsx` | `TimelineCollector` | Waterfall bars, legend, time axis ticks, expandable details |
| `MiddlewarePanel` | `MiddlewarePanel.tsx` | `MiddlewareCollector` | Phase badges (BEFORE/HANDLER/AFTER), memory display, object links |
| `ServicesPanel` | `ServicesPanel.tsx` | `ServiceCollector` | Summary/All tabs, expandable rows, error badges, time metrics |
| `FilesystemPanel` | `FilesystemPanel.tsx` | `FilesystemCollector` | Operation tabs with counts, file links, collapsible args |
| `MailerPanel` | `MailerPanel.tsx` | `MailerCollector` | Mail list, field details (from/to/cc/bcc), HTML/raw preview dialog |
| `VarDumperPanel` | `VarDumperPanel.tsx` | `VarDumperCollector` | Type-aware preview, expandable JSON, inline file links |

#### Panel Design Pattern

```tsx
// Common structure for all panels:
export const XxxPanel = ({data}: XxxPanelProps) => {
    const [expandedIndex, setExpandedIndex] = useState<number | null>(null);

    if (!data || data.length === 0) {
        return <Alert severity="info"><AlertTitle>No items found</AlertTitle></Alert>;
    }

    return (
        <Box>
            <SectionTitle>{`${data.length} items`}</SectionTitle>
            {data.map((item, index) => (
                <Box key={index}>
                    <StyledRow expanded={expandedIndex === index} onClick={...}>
                        {/* Badge | Content | Metadata | ExpandIcon */}
                    </StyledRow>
                    <Collapse in={expandedIndex === index}>
                        <DetailBox>{/* Expanded content */}</DetailBox>
                    </Collapse>
                </Box>
            ))}
        </Box>
    );
};
```

Shared styled components across panels: expandable `Row`, `DetailBox`, `TimeCell`, `NameCell`, badges via `Chip`.

## Inspector Module

**Path prefix**: `/inspector`

Introspects the live application state. The most feature-rich module with 20+ pages.

### Pages

| Page | Route | Description |
|------|-------|-------------|
| RoutesPage | `/inspector/routes` | All routes with methods, path, middleware, action |
| EventsPage | `/inspector/events` | Event listeners grouped by event class |
| DatabasePage | `/inspector/database` | Tables list with column info |
| TablePage | `/inspector/database/:table` | Table schema + records grid |
| CommandsPage | `/inspector/commands` | Available CLI commands |
| TestsPage | `/inspector/tests` | Test execution results (PHPUnit/Codeception) |
| AnalysePage | `/inspector/analyse` | Static analysis results (Psalm) |
| FileExplorerPage | `/inspector/files` | Source code file browser |
| ContainerEntryPage | `/inspector/container/view` | DI container entry inspection |
| ComposerPage | `/inspector/composer` | Composer packages and autoload info |
| TranslationsPage | `/inspector/translations` | Translation catalogs |
| CachePage | `/inspector/cache` | Cache viewer/clearer |
| OpcachePage | `/inspector/opcache` | OPcache status and config |
| PhpInfoPage | `/inspector/phpinfo` | PHP configuration viewer |
| ConfigurationPage | `/inspector/config(/:page)` | Application configuration viewer |
| GitPage | `/inspector/git` | Git repository summary |
| GitLogPage | `/inspector/git/log` | Git commit log |

### API Endpoints

Two RTK Query APIs:

**inspectorApi** (reducerPath: `api.inspector`):
- getParameters, getConfiguration, getActions, getRoutes, getTable, getTableData
- getTranslations, getComposerInstalled, getContainer, doRequest, runCommand
- getCache, getCacheValue, deleteCacheValue, clearCache, getOpcacheStatus
- getFileContents, getPhpInfo, getAnalyse, getEvents

**gitApi** (reducerPath: `api.inspector.git`):
- getSummary, getLog, checkout, command

All inspector endpoints use `keepUnusedDataFor: 0` (no caching — always fresh data).

## Gii Module

**Path prefix**: `/gii`

Code generation wizard. Uses a local React Context with `useReducer` for wizard step state.

### Pages

| Page | Route | Description |
|------|-------|-------------|
| GiiPage | `/gii` | Generator selection + multi-step form |

### API Endpoints (giiApi)

| Endpoint | Type | Description |
|----------|------|-------------|
| getGenerators | Query | List available code generators |
| postPreview | Mutation | Preview generated files |
| postGenerate | Mutation | Execute code generation |
| postDiff | Mutation | Show diff for a generated file |

### Wizard Flow

```
Select generator → Fill attributes form (React Hook Form + Yup)
    → Preview files → Review diffs → Generate → Show results
```

## OpenAPI Module

**Path prefix**: `/open-api`

Embeds Swagger UI React for interactive API documentation. Stores API spec entries (name → URL) in Redux state (`openApiSlice`), persisted to localStorage.

## Frames Module

**Path prefix**: `/frames`

Manages iFrame entries for rendering remote panels. Stores frame entries (name → URL) in Redux state (`framesSlice`), persisted to localStorage. Supports Module Federation for loading external collector UIs dynamically.

## Adding a New Module

1. Create directory: `src/Module/MyModule/`
2. Create `router.tsx` with route definitions
3. Create page components in `Pages/`
4. Create `api.ts` exporting `reducers` and `middlewares` (if using RTK Query)
5. Create `index.ts` exporting the `ModuleInterface`
6. Register the module in `modules.ts`
7. Import reducers/middlewares in `store.ts`

```typescript
// src/Module/MyModule/API/MyApi.ts
export const myApi = createApi({
    reducerPath: 'api.myModule',
    baseQuery: createBaseQuery('/my-module/api'),
    endpoints: (builder) => ({
        getData: builder.query({ query: () => '/' }),
    }),
});

// src/Module/MyModule/api.ts
export const reducers = { [myApi.reducerPath]: myApi.reducer };
export const middlewares = [myApi.middleware];

// src/Module/MyModule/index.ts
export const MyModule: ModuleInterface = {
    routes,
    reducers,
    middlewares,
    standaloneModule: false,
};
```
