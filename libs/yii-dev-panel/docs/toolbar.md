# Toolbar Package

The toolbar (`yii-dev-toolbar`) is a lightweight, embeddable widget that displays
debug information directly on the page of the target application.

## Purpose

While the main SPA is a standalone application, the toolbar is designed to be
injected into the target application's HTML pages. It provides quick access to
debug info without leaving the application.

## Structure

```
packages/yii-dev-toolbar/
├── src/
│   ├── App.tsx                  # Toolbar root component
│   └── Module/
│       └── Toolbar/             # Toolbar-specific components
├── vite.config.ts               # Build configuration
└── package.json
```

## Features

- Compact, collapsible bar at the bottom of the page
- Shows key metrics: response time, memory usage, status code
- Quick links to the full debug panel for the current request
- Non-intrusive: minimal impact on page layout and performance

## Integration

The toolbar is built as a standalone bundle that can be injected via:

1. A `<script>` tag in the application's layout
2. Framework middleware that appends the toolbar HTML before `</body>`

## Build

```bash
cd packages/yii-dev-toolbar
npm run build    # Produces a self-contained JS bundle
```

The built bundle includes all dependencies (React, MUI) and mounts itself
into a shadow DOM to avoid CSS conflicts with the host page.
