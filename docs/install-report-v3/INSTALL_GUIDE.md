# Installing ADP into a fresh Yii 3 app — beginner walkthrough

Step-by-step guide to add ADP (Application Development Panel) to a freshly-scaffolded
`yiisoft/app` project. Targets PHP 8.4+ and Composer 2.x.

> **Heads-up (April 2026):** the latest Packagist release (`v0.2`) predates several
> important fixes (auto-bundled frontend, Composer-shipped panel SPA, AssetsController,
> documented middleware stack). Until a new tag is cut, follow the **dev-master** path
> in step 2.

## 1. Create the Yii 3 application

```bash
composer create-project --stability=dev yiisoft/app my-app
cd my-app
```

Verify the app starts:

```bash
PHP_CLI_SERVER_WORKERS=3 php -S 127.0.0.1:8080 -t public
# open http://127.0.0.1:8080  →  "Hello! Let's start something great with Yii3!"
```

> `PHP_CLI_SERVER_WORKERS=3` is **required**. ADP uses Server-Sent Events plus
> concurrent data fetches; with the PHP built-in server's default of one worker
> the panel hangs at startup. Always export this before `php -S`.

Stop the server (Ctrl-C).

## 2. Install ADP

### 2a. From a stable Packagist release (recommended once a new tag exists)

```bash
composer require app-dev-panel/adapter-yii3
```

That installs `adapter-yii3`, `api`, `cli`, `kernel`, `mcp-server`, and `frontend-assets`
(prebuilt panel + toolbar bundles) as transitive dependencies. Skip to step 3.

### 2b. From master while the release is pending

```bash
composer config minimum-stability dev
composer config prefer-stable true

composer require -W \
  "app-dev-panel/adapter-yii3:dev-master" \
  "app-dev-panel/api:dev-master" \
  "app-dev-panel/cli:dev-master" \
  "app-dev-panel/kernel:dev-master" \
  "app-dev-panel/mcp-server:dev-master" \
  "app-dev-panel/frontend-assets:dev-master"
```

Pinning every package to `dev-master` is **required** — `prefer-stable: true` would
otherwise leave `api/cli/kernel/mcp-server` on `v0.2` and the dev-master adapter would
crash calling APIs that don't exist in stable.

If `composer require adapter-yii3:dev-master` fails with
`requires app-dev-panel/frontend-assets * -> ... does not match your minimum-stability`,
re-run with `composer config minimum-stability dev` first (already in the snippet above).

> The split repository for `frontend-assets` may briefly lag the monorepo. If the
> first request to `/debug` returns HTTP 500 with
> `Call to undefined method AppDevPanel\FrontendAssets\FrontendAssets::resolve()`,
> wait a few minutes and re-run `composer update app-dev-panel/frontend-assets`.

## 3. Wire up middleware

The Yii 3 adapter's config plugin auto-registers DI bindings, event listeners, and
collectors — but it can't reach into your application's middleware stack. Edit
`config/web/di/application.php` to add **`ToolbarMiddleware`** (outermost) and
**`YiiApiMiddleware`** (after `ErrorCatcher`, before `Router`):

```php
<?php

declare(strict_types=1);

use App\Web\NotFound\NotFoundHandler;
use AppDevPanel\Adapter\Yii3\Api\ToolbarMiddleware;
use AppDevPanel\Adapter\Yii3\Api\YiiApiMiddleware;
use Yiisoft\Csrf\CsrfTokenMiddleware;
use Yiisoft\Definitions\DynamicReference;
use Yiisoft\Definitions\Reference;
use Yiisoft\ErrorHandler\Middleware\ErrorCatcher;
use Yiisoft\Middleware\Dispatcher\MiddlewareDispatcher;
use Yiisoft\RequestProvider\RequestCatcherMiddleware;
use Yiisoft\Router\Middleware\Router;
use Yiisoft\Session\SessionMiddleware;
use Yiisoft\Yii\Http\Application;

return [
    Application::class => [
        '__construct()' => [
            'dispatcher' => DynamicReference::to([
                'class' => MiddlewareDispatcher::class,
                'withMiddlewares()' => [
                    [
                        ToolbarMiddleware::class,
                        ErrorCatcher::class,
                        YiiApiMiddleware::class,
                        SessionMiddleware::class,
                        CsrfTokenMiddleware::class,
                        RequestCatcherMiddleware::class,
                        Router::class,
                    ],
                ],
            ]),
            'fallbackHandler' => Reference::to(NotFoundHandler::class),
        ],
    ],
];
```

Order matters:

- `ToolbarMiddleware` is outermost — it rewrites the HTML body before `</body>`,
  including pages produced by error handlers below.
- `ErrorCatcher` sits next so exceptions raised in ADP itself surface cleanly.
- `YiiApiMiddleware` intercepts `/debug/api/*`, `/inspect/api/*`, `/debug` (the panel
  HTML) and `/debug/static/*` (panel + toolbar bundles) before your application's
  router gets a chance to 404 them.

## 4. Run

```bash
PHP_CLI_SERVER_WORKERS=3 php -S 127.0.0.1:8080 -t public
```

Visit:

| URL | What you should see |
|-----|---------------------|
| `http://127.0.0.1:8080/` | Yii's "Hello!" home page **with the ADP toolbar** sitting bottom-right (yellow duck icon, response code, timing) |
| `http://127.0.0.1:8080/debug` | The full ADP panel SPA — Home / Debug / Inspector / LLM / Open API / Frames in the sidebar; recent requests listed under "Debug entries" |
| `http://127.0.0.1:8080/debug/api/` | JSON dump of debug entries |
| `http://127.0.0.1:8080/debug/static/bundle.js` | The panel JS bundle, served by `AssetsController` from `vendor/app-dev-panel/frontend-assets/dist/` |

## 5. Optional configuration

All ADP settings live in `config/params.php` under the `app-dev-panel/yii3` key:

```php
return [
    'app-dev-panel/yii3' => [
        'enabled' => true,
        'storage' => [
            'path' => '@runtime/debug',
            'historySize' => 50,
        ],
        'ignoredRequests' => ['/healthcheck', '/metrics'],
    ],
];
```

To override the panel asset URL (e.g. to point at a CDN or a local Vite dev server),
add a `panel.staticUrl` entry; otherwise leave it empty and the adapter will
auto-detect `FrontendAssets::path()` first, then `resources/dist/`, then the GitHub
Pages CDN as a last resort.

## Troubleshooting

| Symptom | Fix |
|---------|-----|
| `composer require adapter-yii3:dev-master` fails on `minimum-stability` | Run `composer config minimum-stability dev` and retry. |
| `/debug` is blank, browser console shows 404 on `bundle.js` | Adapter is on `dev-master` but `api`/`cli`/`kernel` are stuck on `v0.2`. Pin every package to `:dev-master` (see step 2b). |
| HTTP 500 with `FrontendAssets::resolve()` undefined | Split repository lag. Run `composer update app-dev-panel/frontend-assets` after a few minutes. |
| Toolbar invisible on the home page | `ToolbarMiddleware` not registered. Re-check `config/web/di/application.php` step 3. |
| `/debug` returns `404 Not Found` from Yii's router | `YiiApiMiddleware` not registered, or placed after `Router::class`. |
| Page hangs forever loading | Forgot `PHP_CLI_SERVER_WORKERS=3`. |
| `frontend:update` fails with HTTP 403 | Hit GitHub API rate-limit. Either authenticate (`export GITHUB_TOKEN=…`) or update via Composer (`composer update app-dev-panel/frontend-assets`). |

## What's next

- [Debug Panel guide](../../website/guide/debug-panel.md) — collectors and panel modules
- [Toolbar guide](../../website/guide/toolbar.md) — customising the bottom-of-page widget
- [Inspector guide](../../website/guide/) — live runtime introspection (DB schema, routes, cache, etc.)
