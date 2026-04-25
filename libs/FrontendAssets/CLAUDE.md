# FrontendAssets Module

Ships the prebuilt ADP panel SPA as a Composer package so every framework adapter gets the frontend installed automatically. No runtime download, no CDN dependency.

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
2. `npm ci && npm run build -w packages/sdk && npm run build -w packages/panel` inside `libs/frontend/`
3. Copy `libs/frontend/packages/panel/dist/*` into `libs/FrontendAssets/dist/`
4. Make a throwaway local commit that `git add -f libs/FrontendAssets/dist`
5. Run `splitsh-lite --prefix=libs/FrontendAssets` to extract the subtree
6. Force-push the resulting SHA to [`app-dev-panel/frontend-assets`](https://github.com/app-dev-panel/frontend-assets) (and tag it with `v*` when triggered by a tag)

The local build commit is never pushed back to the monorepo — only the extracted split remote sees it.

## Local Development

To test `adp serve` locally against the real panel:

```bash
make install-frontend
cd libs/frontend && npm run build -w packages/sdk && npm run build -w packages/panel
cp -R libs/frontend/packages/panel/dist/. libs/FrontendAssets/dist/
```

The `dist/*` files are gitignored, so nothing leaks into the monorepo.

## Why Not Commit `dist/` to the Monorepo?

- Keeps the monorepo small and diff-friendly — the panel build is hundreds of files.
- Prevents `dist/` from drifting out of sync with the source in `libs/frontend/`.
- Release flow is reproducible: the bundle always matches the exact source at that commit.

The split-repository workflow is modelled on [buggregator's `frontend-dist`](https://github.com/buggregator/frontend-dist) pattern.
