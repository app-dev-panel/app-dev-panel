# FrontendAssets Module

Ships the prebuilt ADP panel SPA **and toolbar widget** as a Composer package so every framework adapter gets the frontend installed automatically. No runtime download, no CDN dependency.

## Package

- Composer: `app-dev-panel/frontend-assets`
- Namespace: `AppDevPanel\FrontendAssets\`
- PHP: 8.4+
- Dependencies: none (leaf module, `requires: []` in `modulite.php`)

## Directory Structure

```
composer.json
CLAUDE.md
src/
└── FrontendAssets.php   # Locator helper for the prebuilt bundles
dist/
├── index.html           # Panel SPA entry
├── bundle.js / bundle.css
├── assets/              # Panel chunked assets
└── toolbar/             # Toolbar widget build
    ├── bundle.js
    └── bundle.css
.gitignore               # /dist/* (except .gitkeep)
```

## Helper

```php
AppDevPanel\FrontendAssets\FrontendAssets::path();           // /vendor/app-dev-panel/frontend-assets/dist
AppDevPanel\FrontendAssets\FrontendAssets::exists();         // true when dist/index.html is present
AppDevPanel\FrontendAssets\FrontendAssets::toolbarPath();    // /vendor/app-dev-panel/frontend-assets/dist/toolbar
AppDevPanel\FrontendAssets\FrontendAssets::toolbarExists();  // true when dist/toolbar/bundle.js is present
```

`ServeCommand` (in `app-dev-panel/cli`) calls `FrontendAssets::path()` as the default for `--frontend-path`. Framework adapters (Laravel, Symfony, Yii 2, Yii 3) call `FrontendAssets::path()` / `toolbarPath()` to serve the bundles locally under `/vendor/app-dev-panel/*`, falling back to the CDN only when the dist is missing.

## Who Requires This Module

Every framework adapter, plus the CLI module itself:

- `app-dev-panel/cli` — `ServeCommand` uses the helper to auto-resolve the bundle
- `app-dev-panel/adapter-yii3`
- `app-dev-panel/adapter-symfony`
- `app-dev-panel/adapter-laravel`
- `app-dev-panel/adapter-yii2`

## Build Flow (Release Time)

`dist/` is **not** tracked in the monorepo. It is produced on every push by `.github/workflows/split.yml`:

1. Checkout the monorepo
2. `npm ci && npm run build -w packages/sdk && npm run build -w packages/panel && npm run build -w packages/toolbar` inside `libs/frontend/`
3. Copy `libs/frontend/packages/panel/dist/*` into `libs/FrontendAssets/dist/` and `libs/frontend/packages/toolbar/dist/*` into `libs/FrontendAssets/dist/toolbar/`
4. Make a throwaway local commit that `git add -f libs/FrontendAssets/dist`
5. Run `splitsh-lite --prefix=libs/FrontendAssets` to extract the subtree
6. Force-push the resulting SHA to [`app-dev-panel/frontend-assets`](https://github.com/app-dev-panel/frontend-assets) (and tag it with `v*` when triggered by a tag)

The local build commit is never pushed back to the monorepo — only the extracted split remote sees it.

## Local Development

To test adapters or `adp serve` locally against the real panel + toolbar:

```bash
make install-frontend
cd libs/frontend && npm run build -w packages/sdk && npm run build -w packages/panel && npm run build -w packages/toolbar
cd ..
mkdir -p libs/FrontendAssets/dist/toolbar
cp -R libs/frontend/packages/panel/dist/.   libs/FrontendAssets/dist/
cp -R libs/frontend/packages/toolbar/dist/. libs/FrontendAssets/dist/toolbar/
```

The `dist/*` files are gitignored, so nothing leaks into the monorepo.

## Why Not Commit `dist/` to the Monorepo?

- Keeps the monorepo small and diff-friendly — the panel build is hundreds of files.
- Prevents `dist/` from drifting out of sync with the source in `libs/frontend/`.
- Release flow is reproducible: the bundle always matches the exact source at that commit.

The split-repository workflow is modelled on [buggregator's `frontend-dist`](https://github.com/buggregator/frontend-dist) pattern.
