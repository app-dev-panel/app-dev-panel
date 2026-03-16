# Toolbar Package

The toolbar (`app-dev-toolbar`) is a lightweight, embeddable widget that displays
debug information directly on the page of the target application.

## Purpose

While the main SPA is a standalone application, the toolbar is designed to be
injected into the target application's HTML pages. It provides quick access to
debug info without leaving the application.

## Structure

```
packages/app-dev-toolbar/
├── src/
│   ├── index.tsx               # Entry point
│   ├── App.tsx                 # Root component (Redux Provider, Router)
│   ├── store.ts                # Redux store (shared with SDK)
│   ├── router.tsx              # Single wildcard route
│   ├── modules.ts              # Module registry
│   ├── wdyr.ts                 # Why-Did-You-Render dev tool
│   └── Module/
│       └── Toolbar/
│           ├── index.ts        # ToolbarModule (ModuleInterface, standalone: true)
│           ├── router.tsx      # Wildcard route → Toolbar page
│           ├── Pages/
│           │   └── Toolbar.tsx # Main toolbar page
│           └── Component/
│               ├── DebugEntriesListModal.tsx  # Expandable debug entries modal
│               └── Toolbar/
│                   ├── DebugToolbar.tsx        # Main toolbar bar component
│                   ├── DateItem.tsx            # Request timestamp
│                   ├── MemoryItem.tsx          # Memory usage display
│                   ├── RequestTimeItem.tsx     # Response time display
│                   ├── EventsItem.tsx          # Event count
│                   ├── LogsItem.tsx            # Log count
│                   ├── ValidatorItem.tsx       # Validation stats
│                   ├── Console/
│                   │   └── CommandItem.tsx     # CLI command info
│                   └── Web/
│                       ├── RequestItem.tsx     # HTTP request info
│                       └── RouterItem.tsx      # Route match info
├── vite.config.ts              # Vite build config (library mode)
└── package.json
```

## Toolbar Items

The toolbar displays compact metric items along a horizontal bar:

| Item | Metric | Source |
|------|--------|--------|
| DateItem | Request timestamp | `web.request.startTime` |
| RequestTimeItem | Processing time (ms) | `web.request.processingTime` |
| MemoryItem | Peak memory usage | `web.memory.peakUsage` |
| RequestItem | HTTP method + URL + status | `request.*`, `response.*` |
| RouterItem | Matched route pattern | `router.*` |
| EventsItem | Event listener count | `event.total` |
| LogsItem | Log entry count | `logger.total` |
| ValidatorItem | Valid/invalid count | `validator.*` |
| CommandItem | CLI command name + exit code | `command.*` (console mode) |

## How It Works

1. The toolbar renders as a `standaloneModule` (no Layout wrapper)
2. Uses a wildcard route (`*`) to match any URL path
3. Shares the same Redux store structure as the main app (via SDK)
4. Uses **Redux State Sync** to communicate with the main SPA across tabs/windows
5. Fetches debug data for the current request via `debugApi`
6. Displays metric items in a collapsible horizontal bar

## Cross-Window Communication

The toolbar and main SPA communicate via:
- **Redux State Sync**: Shared state updates propagated through `BroadcastChannel`
- **Window postMessage**: Events like `panel.loaded` and `router.navigate`
- **dispatchWindowEvent**: Helper from SDK that posts messages to `window.parent`

## Integration

The toolbar is built as a standalone bundle that can be injected via:

1. A `<script>` tag in the application's layout
2. Framework middleware that appends the toolbar HTML before `</body>`
3. The main SPA's toolbar wrapper (sidebar mode)

## Build

```bash
cd packages/app-dev-toolbar
npm run build    # Produces bundle.js + bundle.css via Vite library mode
```

The toolbar is also bundled into the main SPA's dist via the `copy-toolbar` script:
```bash
# From root
npm run copy-toolbar    # Copies toolbar dist into main app's dist/toolbar/
```

## Dependencies

Uses the SDK for shared components and API clients:
- `@app-dev-panel/sdk` — API clients, Redux slices, helpers
- `react-resizable-layout` — Resizable panel layout
- `redux-state-sync` — Cross-window state synchronization
