# Laravel ADP Install Report (v0.2 from Packagist)

End-to-end walk-through of installing `app-dev-panel/adapter-laravel` v0.2 from
Packagist on a fresh Laravel 13.6.0 application, following
`website/guide/adapters/laravel.md` and `website/guide/getting-started.md`.

This run is performed **after** PR #248 (frontend-assets package) and PR #264
(refactor: drop AdpAssetsController, web server serves statics) landed in
master. Master has the right architecture but the Packagist v0.2 release
predates both PRs, so the user-visible flow on a fresh install is broken.

## Environment

| Item | Value |
| --- | --- |
| Host OS | Linux 4.4.0 |
| PHP | 8.4.19 |
| Composer | 2.8.12 |
| Laravel | 13.6.0 (fresh `laravel/laravel` skeleton) |
| ADP adapter | `app-dev-panel/adapter-laravel` **v0.2** (Packagist) |
| Kernel/API/Cli/McpServer | v0.2 (transitive) |
| `app-dev-panel/frontend-assets` | **NOT installed** — v0.2 of adapter-laravel does not require it |

## TL;DR — recommended install on Packagist v0.2

```bash
# 1. Fresh Laravel
composer create-project laravel/laravel my-app
cd my-app

# 2. ADP adapter (auto-discovered via package discovery, no manual provider)
composer require app-dev-panel/adapter-laravel

# 3. Publish config
php artisan vendor:publish --tag=app-dev-panel-config

# 4. WORKAROUND (will not be needed once v0.3 is tagged):
#    download panel + toolbar from the GitHub release directly
mkdir -p public/vendor/app-dev-panel/toolbar
curl -sSL https://github.com/app-dev-panel/app-dev-panel/releases/download/v0.2/panel-dist.tar.gz \
  | tar -xz -C public/vendor/app-dev-panel --strip-components=1
curl -sSL https://github.com/app-dev-panel/app-dev-panel/releases/download/v0.2/toolbar-dist.tar.gz \
  | tar -xz -C public/vendor/app-dev-panel/toolbar --strip-components=1

# 5. Boot
PHP_CLI_SERVER_WORKERS=4 php -S 127.0.0.1:8000 -t public
# → http://127.0.0.1:8000          : your app, with the ADP toolbar pinned bottom-right
# → http://127.0.0.1:8000/debug    : the panel SPA, fully populated
```

The `vendor/app-dev-panel/bundle.js` heuristic in `AppDevPanelServiceProvider`
flips `panel.static_url` to `/vendor/app-dev-panel` once that file exists, so
the manual extraction in step 4 is enough — no further config changes.

## What works out of the box

- `composer require` succeeds, package auto-discovered via
  `extra.laravel.providers`. No manual provider registration.
- `vendor:publish --tag=app-dev-panel-config` creates
  `config/app-dev-panel.php`.
- `APP_DEBUG=true` (Laravel default) is enough — `enabled` falls back to it.
- All 25+ collectors register: Timeline, Environment, Filesystem/Http stream,
  Validator, Translator, Authorization, Exception, Log, Event, Service,
  HttpClient, VarDumper, Deprecation, Database, Cache, Mailer, Queue,
  OpenTelemetry, AssetBundle, Template, Redis, Elasticsearch, Request,
  WebAppInfo. Visible at `GET /debug/api`.
- Per-request `summary.json` written under `storage/debug/<date>/<id>/`.
- `GET /debug/api` returns the JSON index (24+ entries after a few hits).

## Issues encountered

### 1. `app-dev-panel/frontend-assets` not pulled in by Packagist v0.2 — **CRITICAL**

`composer require app-dev-panel/adapter-laravel` brings:

```
app-dev-panel/adapter-laravel v0.2
app-dev-panel/api             v0.2
app-dev-panel/cli             v0.2
app-dev-panel/kernel          v0.2
app-dev-panel/mcp-server      v0.2
```

— but **not** `app-dev-panel/frontend-assets`. The v0.2 tag was cut before
PR #248 added that package to every adapter's `require`. Confirmed by reading
`vendor/app-dev-panel/adapter-laravel/composer.json`:

```json
"require": {
    "php": "^8.4",
    "app-dev-panel/api": "*",
    "app-dev-panel/kernel": "*",
    "app-dev-panel/cli": "*",
    ...
}
```

Consequence: the new `AppDevPanelServiceProvider::resolveAssetSource()` chain
falls all the way through to `PanelConfig::DEFAULT_STATIC_URL` (the GitHub
Pages CDN). Both panel and toolbar URLs point at
`https://app-dev-panel.github.io/app-dev-panel/...`. In any environment
without outbound HTTPS, with a stale CA bundle, or with mixed-content
restrictions, the panel renders blank and the toolbar never paints.

In this run the headless browser hit
`net::ERR_CERT_AUTHORITY_INVALID` for every CDN asset and both `/` and
`/debug` came back blank — see `01-home-cdn-fail.png` /
`02-debug-cdn-fail.png`.

**Fix:** cut a v0.3 (or whatever the next tag is) so Packagist serves the
master `composer.json` that already lists `app-dev-panel/frontend-assets`.
Until then the workaround in the TL;DR is the only reliable path.

### 2. `frontend:update download` is unusable on this release

The CLI's recovery path is `php artisan frontend:update download --path=...`,
which is supposed to fetch the latest release from GitHub and unpack it. Two
problems:

a. **GitHub API rate limit on first call.** `FrontendUpdateCommand` always
   hits `GET https://api.github.com/repos/.../releases/latest`. Anonymous
   requests share the runner's outbound IP, which is rate-limited:

   ```
   [ERROR] Failed to fetch release info:
   GET https://api.github.com/repos/app-dev-panel/app-dev-panel/releases/latest
   resulted in a 403 Forbidden response:
   {"message":"API rate limit exceeded for <ip>. ..."}
   ```

   Reproduced two consecutive runs from two different IPs. No retry, no
   `Authorization: Bearer $GITHUB_TOKEN` env support — fail closed.

b. **The asset name does not exist.** Even if the API call succeeds, the
   command looks for an asset literally named `frontend-dist.zip`:

   ```
   private const ASSET_NAME = 'frontend-dist.zip';
   ```

   The v0.2 release publishes only `panel-dist.tar.gz` and
   `toolbar-dist.tar.gz` (verified by hitting both URLs):

   ```
   panel-dist.tar.gz   -> HTTP 200
   toolbar-dist.tar.gz -> HTTP 200
   frontend-dist.zip   -> HTTP 404
   ```

   The matching CI step (`npm-publish.yml: Package panel + toolbar as
   frontend-dist.zip`) was added in master but, again, only takes effect on
   the next tag.

**Fix:** add a token-aware retry path in `FrontendUpdateCommand` and either
(a) pin the asset list to the actual release (`panel-dist.tar.gz` +
`toolbar-dist.tar.gz`) or (b) document `frontend-dist.zip` as a hard
requirement and tag a release that produces it.

### 3. Adapter docs still link to the old `--provider=` publish form

`website/guide/adapters/laravel.md:25-27` (master HEAD) still shows:

```
php artisan vendor:publish --provider="AppDevPanel\Adapter\Laravel\AppDevPanelServiceProvider"
```

while `getting-started.md` shows the canonical `--tag=app-dev-panel-config`.
Both work, but presenting two recipes side-by-side is confusing for first-time
installs. Fold the long form into a single canonical `--tag=app-dev-panel-config`.

### 4. Adapter docs do not mention the frontend resolution chain at all

The Laravel page (`website/guide/adapters/laravel.md`) ends at "Database
Inspector". A reader has no way to know:

- the bundle is supposed to come from `app-dev-panel/frontend-assets`
  (transitive on a future release),
- the SPA at `/debug` will fall back to the GitHub Pages CDN if the bundle is
  missing,
- `panel.static_url` / `toolbar.static_url` overrides exist in the published
  config.

Add a "Frontend Assets" section spelling out the three-tier resolution
(`public/vendor/app-dev-panel` → `vendor/app-dev-panel/frontend-assets/dist`
→ CDN), the override knobs, and the manual install fallback.

### 5. `GET /debug/api/debug` returns 404 — easy stumbling block

The debug-API root is at `/debug/api`, not `/debug/api/debug`. New users
trying to "look at the debug data" via curl land on a `Not found.` JSON.

```
$ curl http://127.0.0.1:8000/debug/api/debug
{"error":"Not found.","success":false}

$ curl http://127.0.0.1:8000/debug/api
{"id":null,"data":[{"id":"...","collectors":[...]}]}    # ← actual index
```

Worth a one-liner in the adapter doc with the canonical curl examples
(`/debug/api`, `/debug/api/summary/{id}`, `/debug/api/view/{id}`,
`/debug/api/event-stream`).

## Verification (after step 4 workaround)

```
$ curl -sS http://127.0.0.1:8000/debug | grep bundle.
    <link rel="stylesheet" href="/vendor/app-dev-panel/bundle.css" />
    <script type="module" crossorigin src="/vendor/app-dev-panel/bundle.js"></script>

$ curl -sS http://127.0.0.1:8000/ | grep -E "toolbar/bundle"
<link rel="stylesheet" href="/vendor/app-dev-panel/toolbar/bundle.css" />
<script type="module" crossorigin src="/vendor/app-dev-panel/toolbar/bundle.js"></script>

$ curl -sS -o /dev/null -w "%{http_code}\n" http://127.0.0.1:8000/vendor/app-dev-panel/bundle.js
200

$ curl -sS -o /dev/null -w "%{http_code}\n" http://127.0.0.1:8000/vendor/app-dev-panel/toolbar/bundle.js
200
```

Screenshots (not committed — `screenshots/` is gitignored project-wide):

| Path | What |
| --- | --- |
| `/tmp/laravel-demo-shots/01-home-cdn-fail.png` | Default install: Laravel welcome page renders, ADP toolbar markup is injected but JS never loads (CDN cert error) |
| `/tmp/laravel-demo-shots/02-debug-cdn-fail.png` | Default install: `/debug` route returns the SPA shell, but `bundle.js` never loads → blank page |
| `/tmp/laravel-demo-shots/03-home-with-toolbar.png` | After workaround: Laravel home + ADP toolbar pinned bottom-right (🐤 200 64ms) |
| `/tmp/laravel-demo-shots/03b-toolbar-zoom.png` | Toolbar widget closeup |
| `/tmp/laravel-demo-shots/04-debug-panel.png` | After workaround: `/debug` SPA — Home, Debug, Inspector, LLM, Open API, Frames sidebar; Debug + Inspector both "Connected"; 24 entries listed |

## Action items for the project (priority order)

1. **Cut a release** that includes the master `composer.json` with the
   `frontend-assets` dependency. This single change makes the happy path work
   for everyone. (Closes #1, partially closes #2.)
2. **Fix `FrontendUpdateCommand`** to (a) accept `GITHUB_TOKEN` env for the
   API call, (b) match the assets the release pipeline actually publishes.
   Otherwise this CLI is dead code. (Closes #2.)
3. **Update `website/guide/adapters/laravel.md`** with a "Frontend Assets"
   section + curl examples + canonical `vendor:publish` form.
   (Closes #3, #4, #5.)

## Test project location

`/home/user/test-installation/laravel-adp-demo/` (not committed, lives outside the repo).
