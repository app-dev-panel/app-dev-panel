# Yii3 ADP install — beginner walkthrough (post-rebase)

Re-ran the install on master (commit `d3f6a0d0` — frontend-assets bundle, AssetsController, middleware docs).
This is the third iteration of the `docs/install-report*/` series. **All previous issues that needed
code changes are fixed in master**; the remaining ones are release/CI orchestration problems.

## Summary

| # | Problem | Severity | Status |
|---|---------|----------|--------|
| 1 | Stable Packagist release is stale (`v0.2` from 22 Apr) | **Blocker for newcomers** | Open — needs a tag |
| 2 | `frontend-assets` Packagist tags are missing — only `dev-master` is available | Blocker | Open — needs CI tag |
| 3 | Mixed-stability install leaves `api/cli/kernel/mcp-server` at `v0.2` while pulling `adapter-yii3:dev-master` | Blocker | Open — needs `:dev-master` on every package or a stable release |
| 4 | Split repo `frontend-assets` lags behind the monorepo — `FrontendAssets::resolve()` is missing in the published `dev-master` even though it exists on master | Blocker (HTTP 500) | Open — needs an atomic split push |
| 5 | `yiisoft/app` template doesn't auto-configure middleware | UX | Documented now |
| 6 | `PHP_CLI_SERVER_WORKERS=3` is mandatory for SSE | UX | Documented |

## Reproduction

Steps a newcomer would take, following the official guide:

```bash
composer create-project --stability=dev yiisoft/app yii3
cd yii3
composer require app-dev-panel/adapter-yii3
PHP_CLI_SERVER_WORKERS=3 php -S 127.0.0.1:8204 -t public
open http://127.0.0.1:8204/debug
```

### What actually happens

#### Issue 1 — stale stable release

Packagist HEAD for `app-dev-panel/adapter-yii3`: **`v0.2`** dated `2026-04-22`.

Everything after that — `frontend-assets` package (PR #248), `AssetsController` (this branch),
the `adapter-* source panel/toolbar bundles from FrontendAssets first` chain (`27bec2c3`),
the Symfony `AdpAssetsController` runtime streaming (`6f1a102a`), the `FrontendAssets::resolve()`
helper (`5830244b`), and updated middleware docs — **is not on Packagist** because no tag was
cut. A user installing today gets `v0.2` and runs into all problems from
`docs/install-report/` and `docs/install-report-v2/`.

**Fix**: cut a tag from current master. CI workflow `.github/workflows/split.yml` already
fan-outs to all split repos.

#### Issue 2 — frontend-assets has no Packagist version

```
$ curl https://repo.packagist.org/p2/app-dev-panel/frontend-assets.json
{"packages": {"app-dev-panel/frontend-assets": []}}
```

The split repo exists, the package is registered on Packagist, but the only published ref is
`dev-master`. `adapter-yii3:dev-master` requires `app-dev-panel/frontend-assets: "*"`, so
Composer with default stability rejects it:

```
Your requirements could not be resolved to an installable set of packages.
- app-dev-panel/adapter-yii3 dev-master requires app-dev-panel/frontend-assets *
  -> found app-dev-panel/frontend-assets[dev-master] but it does not match your minimum-stability.
```

A newcomer who follows the docs (`composer require app-dev-panel/adapter-yii3`) doesn't see
this until they try `:dev-master` to get the recent fixes — and then they have to figure out
`minimum-stability: dev` + `prefer-stable: true` themselves.

**Fix**: tag a stable `frontend-assets` release alongside the main monorepo tag.

#### Issue 3 — mixed-stability install

After `composer config minimum-stability dev && composer require adapter-yii3:dev-master -W`,
the lockfile shows:

```
app-dev-panel/adapter-yii3   dev-master
app-dev-panel/frontend-assets dev-master
app-dev-panel/api            v0.2          <-- stale
app-dev-panel/cli            v0.2          <-- stale
app-dev-panel/kernel         v0.2          <-- stale
app-dev-panel/mcp-server     v0.2          <-- stale
```

`prefer-stable: true` keeps API/CLI/kernel/mcp-server on `v0.2`. The dev-master adapter
references new APIs in `api`/`kernel` that don't exist in `v0.2`. To get a consistent set
the user has to pin every package: `composer require ".../adapter-yii3:dev-master" ".../api:dev-master" ".../cli:dev-master" ".../kernel:dev-master" ".../mcp-server:dev-master" ".../frontend-assets:dev-master"`.

**Fix**: same release-cutting fix solves this — once everything is tagged, `composer require adapter-yii3` pulls a coherent stable set.

#### Issue 4 — split repo lags behind monorepo (HTTP 500)

After getting all packages on `dev-master`, hitting `/` fires:

```
[error][application] Caught unhandled error
"Call to undefined method AppDevPanel\FrontendAssets\FrontendAssets::resolve()"
while building "AppDevPanel\Api\Panel\PanelConfig".
```

Cause:

- Monorepo master has `FrontendAssets::resolve()` (added in `5830244b`).
- `adapter-yii3` config (`vendor/.../adapter-yii3/config/di-api.php:235`) calls `FrontendAssets::resolve()`.
- The published `app-dev-panel/frontend-assets:dev-master` on Packagist **does not have it** — only `path()` and `exists()`.

The split workflow that pushes `libs/FrontendAssets/` to the standalone `app-dev-panel/frontend-assets` repo lagged behind the workflow that pushed `libs/Adapter/Yii3/`. End result: an adapter dev-master that depends on an unreleased API in its dev-master sibling. The app crashes on first request.

**Fix**: ensure the split workflow either runs every package on every push or is sequenced so dependents can never publish before their dependencies. Today's workflow uses a matrix with no ordering between packages.

#### Issue 5 — middleware needs manual wiring (now documented)

The `yiisoft/app` template owns `config/web/di/application.php`. The Yii 3 adapter's config
plugin can register DI/events/collectors but cannot insert middleware into the user's
dispatcher — `ToolbarMiddleware` and `YiiApiMiddleware` have to be added by hand.

`website/guide/adapters/yii3.md` (this branch) now spells this out with a copy-paste-ready
`application.php` block. Before this branch, the doc said "no manual wiring needed" and
left users guessing. **No code change needed; the doc already covers it.**

#### Issue 6 — PHP built-in server workers

`php -S` defaults to a single worker. ADP issues SSE + parallel data fetches; with one
worker the page hangs. Users must export `PHP_CLI_SERVER_WORKERS=3` (or higher).
This is mentioned once in `getting-started.md` but easy to miss. Suggest highlighting it
inside the Yii 3 adapter page too.

## What works once the install hangs together

After manually pinning every package to `:dev-master` AND fixing `FrontendAssets::resolve()`
(by mirroring the master file into `vendor/.../frontend-assets/src/`) the install behaves
correctly: panel renders, toolbar widget injects, no CDN 404s, no missing chunks. This
matches the v3 end-to-end run on `claude/setup-ui3-driver-OtakO` before rebase. The only
thing standing between today's master and a working `composer require` is the release.
