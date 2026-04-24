---
title: Frontend Packages
description: "ADP frontend npm packages: @app-dev-panel/panel, @app-dev-panel/toolbar, and @app-dev-panel/sdk on GitHub Packages."
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
| `frontend-dist.zip` | Same panel build, zip-packaged for the `frontend:update` CLI |

These can be served directly by a web server or embedded into PHP adapter packages.

## Composer Distribution — `app-dev-panel/frontend-assets`

The panel SPA is also shipped as a Composer package so PHP applications get the built frontend automatically when they install an adapter. This is the default channel — every framework adapter (`adapter-yii3`, `adapter-symfony`, `adapter-laravel`, `adapter-yii2`) requires it.

| Package | Namespace | Ships |
|---------|-----------|-------|
| `app-dev-panel/frontend-assets` | `AppDevPanel\FrontendAssets\` | Prebuilt `dist/` directory + `FrontendAssets::path()` helper |

### How it's built and released

The source of the bundle lives in `libs/frontend/packages/panel`. The `dist/` directory is **not** committed to the monorepo — it is produced at release time by the `Monorepo Split` workflow (`.github/workflows/split.yml`):

1. On each `push` to `master` / `*.x` / `v*`, the workflow runs `npm ci && npm run build -w packages/sdk && npm run build -w packages/panel` inside `libs/frontend/`.
2. The Vite output is copied into `libs/FrontendAssets/dist/` and committed to a disposable local commit.
3. `splitsh-lite` extracts `libs/FrontendAssets/` (including `dist/`) into a subtree SHA.
4. The subtree is force-pushed to [`app-dev-panel/frontend-assets`](https://github.com/app-dev-panel/frontend-assets) — and tagged with the release version when triggered by a `v*` tag.

The split repository is what Packagist and `composer require` see. The monorepo `libs/FrontendAssets/` itself only tracks `composer.json`, `src/FrontendAssets.php`, and the `.gitkeep` placeholder — everything in `dist/` is git-ignored locally.

### How it's consumed

Installing any adapter pulls `app-dev-panel/frontend-assets` transitively. The `FrontendAssets::path()` helper returns the absolute path to the bundled `dist/`:

```php
use AppDevPanel\FrontendAssets\FrontendAssets;

FrontendAssets::path();    // /vendor/app-dev-panel/frontend-assets/dist
FrontendAssets::exists();  // true if dist/index.html is present
```

The `serve` CLI command uses this helper as the default for `--frontend-path`, so `php vendor/bin/adp serve` works out of the box without any extra flags.

### Updating the frontend

Two channels are supported:

1. **Composer (default)** — `composer update app-dev-panel/frontend-assets` pulls the latest split-repository tag.
2. **Direct download** — for PHAR-based installs or when Composer is unavailable, use the `frontend:update` CLI command (see the [CLI guide](./cli.md#frontend-update)) to fetch `frontend-dist.zip` from the GitHub Release and extract it in place.

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

- React 19, TypeScript 5.5+
- Vite 5 (build tool)
- MUI 5 (Material UI components)
- Redux Toolkit + RTK Query (state management + API)
- React Router 6 (navigation)
- Vitest (testing)
- Prettier 3.8+ and ESLint 9 (code quality)
