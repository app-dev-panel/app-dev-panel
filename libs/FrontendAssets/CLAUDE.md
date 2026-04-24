# FrontendAssets Module

Ships the prebuilt ADP panel SPA **and** toolbar widget as a Composer package so every framework adapter gets the frontend installed automatically. No runtime download, no CDN dependency.

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
└── FrontendAssets.php   # Locator helper for the prebuilt bundle
dist/
└── .gitkeep             # Placeholder — real files are populated by CI at release time
.gitignore               # /dist/* (except .gitkeep)
```

## Helper

```php
AppDevPanel\FrontendAssets\FrontendAssets::path();    // /vendor/app-dev-panel/frontend-assets/dist
AppDevPanel\FrontendAssets\FrontendAssets::exists();  // true when dist/index.html is present
```

`ServeCommand` (in `app-dev-panel/cli`) calls `FrontendAssets::path()` as the default for `--frontend-path`, so `adp serve` serves the panel with no extra flags whenever this package is installed.

### Layout under `dist/`

```
dist/
├── index.html               # Panel SPA entry
├── bundle.js, bundle.css    # Panel bundle (referenced by PanelController)
├── assets/                  # Vite-chunked panel sub-bundles
└── toolbar/
    ├── bundle.js            # Toolbar widget bundle (referenced by ToolbarInjector)
    └── bundle.css
```

The toolbar lives at `dist/toolbar/*` because `AppDevPanel\Api\Toolbar\ToolbarInjector` always injects `<script src="{panel.staticUrl}/toolbar/bundle.js">` — keeping the relative layout stable means each adapter's static URL points at this `dist/` root and both panel and toolbar resolve correctly.

## Who Requires This Module

Every framework adapter, plus the CLI module itself, with all four adapters wiring `FrontendAssets::path()` into their panel auto-publish path:

| Consumer | Hook |
|----------|------|
| `app-dev-panel/cli` — `ServeCommand` | `FrontendAssets::path()` is the default `--frontend-path` |
| `app-dev-panel/adapter-yii2` — `Module::publishBundledAssets()` | Symlinks `FrontendAssets::path()` into `@webroot/app-dev-panel` |
| `app-dev-panel/adapter-yii3` — `config/di-api.php` (`PanelConfig` factory) | Symlinks `FrontendAssets::path()` into `@public/app-dev-panel` |
| `app-dev-panel/adapter-laravel` — `AppDevPanelServiceProvider::resolveAssetSource()` | Publishes `FrontendAssets::path()` to `public/vendor/app-dev-panel` via `vendor:publish` |
| `app-dev-panel/adapter-spiral` | Pulls `FrontendAssets` transitively via the CLI module |
| `app-dev-panel/adapter-symfony` — `Controller\AdpAssetsController` + `Command\AssetsInstallCommand` | Runtime streams `FrontendAssets::path()/*` at `/_adp-assets`; opt-in `bin/console app-dev-panel:assets:install` copies/symlinks the same dir into `public/bundles/appdevpanel/` for nginx-served setups |

Each adapter falls back to its own `resources/dist/` (legacy `make build-panel` target) when `FrontendAssets::exists()` is `false` — useful for monorepo development before a release build.

## Build Flow (Release Time)

`dist/` is **not** tracked in the monorepo. It is produced on every push by `.github/workflows/split.yml`:

1. Checkout the monorepo
2. `npm ci && npm run build -w packages/sdk && npm run build -w packages/panel && npm run build -w packages/toolbar` inside `libs/frontend/`
3. Copy `libs/frontend/packages/panel/dist/*` into `libs/FrontendAssets/dist/`
4. Copy `libs/frontend/packages/toolbar/dist/*` into `libs/FrontendAssets/dist/toolbar/`
5. Make a throwaway local commit that `git add -f libs/FrontendAssets/dist`
6. Run `splitsh-lite --prefix=libs/FrontendAssets` to extract the subtree
7. Force-push the resulting SHA to [`app-dev-panel/frontend-assets`](https://github.com/app-dev-panel/frontend-assets) (and tag it with `v*` when triggered by a tag)

The local build commit is never pushed back to the monorepo — only the extracted split remote sees it.

## Local Development

To test the adapters' auto-publish locally against the real panel + toolbar:

```bash
make install-frontend
cd libs/frontend && npm run build -w packages/sdk && npm run build -w packages/panel && npm run build -w packages/toolbar
mkdir -p libs/FrontendAssets/dist/toolbar
cp -R libs/frontend/packages/panel/dist/. libs/FrontendAssets/dist/
cp -R libs/frontend/packages/toolbar/dist/. libs/FrontendAssets/dist/toolbar/
```

The `dist/*` files are gitignored, so nothing leaks into the monorepo.

## Why Not Commit `dist/` to the Monorepo?

- Keeps the monorepo small and diff-friendly — the panel build is hundreds of files.
- Prevents `dist/` from drifting out of sync with the source in `libs/frontend/`.
- Release flow is reproducible: the bundle always matches the exact source at that commit.

The split-repository workflow is modelled on [buggregator's `frontend-dist`](https://github.com/buggregator/frontend-dist) pattern.
