---
title: Frontend Packages
---

# Frontend Packages

ADP provides three npm packages under the `@app-dev-panel` scope, published to [GitHub Packages](https://github.com/orgs/app-dev-panel/packages).

| Package | Description |
|---------|-------------|
| `@app-dev-panel/sdk` | Shared library: React components, API clients (RTK Query), theme system, helpers |
| `@app-dev-panel/panel` | Main SPA — the debug panel application |
| `@app-dev-panel/toolbar` | Embeddable toolbar widget for injecting into your application |

## Installation

### 1. Configure GitHub Packages registry

ADP frontend packages are hosted on GitHub Packages. Add the registry scope to your project:

```bash
echo "@app-dev-panel:registry=https://npm.pkg.github.com" >> .npmrc
```

::: tip Authentication
GitHub Packages requires authentication even for public packages. Create a [personal access token](https://github.com/settings/tokens) with `read:packages` scope and add it to your `~/.npmrc`:

```bash
//npm.pkg.github.com/:_authToken=YOUR_GITHUB_TOKEN
```
:::

### 2. Install packages

```bash
# SDK only (components, API clients, helpers)
npm install @app-dev-panel/sdk

# Full panel application
npm install @app-dev-panel/panel

# Toolbar widget
npm install @app-dev-panel/toolbar
```

## Packages

### SDK (`@app-dev-panel/sdk`)

The foundation library used by both panel and toolbar. Contains:

- **API clients** — RTK Query endpoints for debug data, inspector, git, and LLM APIs
- **React components** — `JsonRenderer`, `CodeHighlight`, `DataGrid`, `SearchFilter`, `EmptyState`, `CommandPalette`
- **Layout components** — `TopBar`, `UnifiedSidebar`, `EntrySelector`, `ContentPanel`
- **Theme system** — MUI 5 theme with light/dark mode, design tokens, brand colors
- **Helpers** — fuzzy matching, keyboard layout transliteration (QWERTY/ЙЦУКЕН), date formatting
- **SSE** — `useServerSentEvents` hook for real-time debug entry updates
- **State management** — Redux Toolkit slices for application state, debug entries, notifications

```typescript
import { createAppTheme } from '@app-dev-panel/sdk/Component/Theme/DefaultTheme';
import { JsonRenderer } from '@app-dev-panel/sdk/Component/JsonRenderer';
import { useServerSentEvents } from '@app-dev-panel/sdk/Component/useServerSentEvents';
```

### Panel (`@app-dev-panel/panel`)

The main debug panel SPA. Modules:

- **Debug** — collector panels (logs, database, events, exceptions, timeline, etc.)
- **Inspector** — live application state (routes, config, database, git, cache, files, translations, etc.)
- **LLM** — AI-powered chat and analysis integration
- **MCP** — MCP server configuration page
- **OpenAPI** — Swagger UI integration

### Toolbar (`@app-dev-panel/toolbar`)

Embeddable widget that shows a compact debug bar at the bottom of your application. Displays key metrics (request time, memory, query count) and links to the full panel.

## Pre-built Assets

Each [GitHub Release](https://github.com/app-dev-panel/app-dev-panel/releases) includes pre-built static assets:

| Asset | Contents |
|-------|----------|
| `panel-dist.tar.gz` | Production build of the panel SPA |
| `toolbar-dist.tar.gz` | Production build of the toolbar widget |

These can be served directly by a web server or embedded into PHP adapter packages.

## Development

### Prerequisites

- Node.js 21+
- npm 10+

### Setup

```bash
git clone https://github.com/app-dev-panel/app-dev-panel.git
cd app-dev-panel/libs/frontend
npm install
```

### Commands

```bash
npm start              # Start all Vite dev servers (panel + toolbar + sdk)
npm run build          # Production build all packages
npm run check          # Run Prettier + ESLint checks
npm test               # Run Vitest unit tests
npm run test:e2e       # Run browser E2E tests (requires Chrome)
```

### Project Structure

```
libs/frontend/
├── packages/
│   ├── sdk/           # Shared library (components, API, theme, helpers)
│   ├── panel/         # Main SPA
│   └── toolbar/       # Toolbar widget
├── lerna.json         # Independent versioning
└── package.json       # npm workspaces root
```

### Tech Stack

- React 18, TypeScript 5.5+
- Vite 5 (build tool)
- MUI 5 (Material UI components)
- Redux Toolkit + RTK Query (state management + API)
- React Router 6 (navigation)
- Vitest (testing)
- Prettier 3.8+ and ESLint 9 (code quality)
