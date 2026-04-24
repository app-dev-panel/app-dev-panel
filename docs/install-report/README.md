# Yii3 ADP install from packagist — report

Fresh `yiisoft/app` template + `composer require app-dev-panel/adapter-yii3` (v0.2).

## Findings

- `yiisoft/config` auto-discovery works — ADP entries appear in `config/.merge-plan.php`.
- `GET /debug/api/` returns JSON with 25 active collectors.
- Middleware wiring in `config/web/di/application.php` is **not** automatic for the `yiisoft/app`
  template — `YiiApiMiddleware` and `ToolbarMiddleware` have to be added by hand. The guide at
  `website/guide/adapters/yii3.md` only mentions `YiiApiMiddleware`.
- Frontend CDN (`https://app-dev-panel.github.io/app-dev-panel/...`) referenced by the v0.2
  package returns HTTP 404 for `toolbar/bundle.js`, `toolbar/bundle.css`, and for the panel's
  hashed chunks (`assets/preload-helper-*.js`, `assets/Config-*.js`). Both `/` (toolbar) and
  `/debug` (panel) render empty containers.
- `./yii frontend:update` fails with GitHub API rate-limit 403 without a `GITHUB_TOKEN`.

## Screenshots

| File | What it shows |
|------|---------------|
| `01-home-with-toolbar.png` | Home page before `ToolbarMiddleware` was added — no toolbar markup. |
| `02-debug-panel.png`      | `/debug` — blank, CDN chunks 404. |
| `03-home-long-wait.png`   | Home after both middlewares + SSL-ignore — toolbar markup is injected but invisible because CDN bundles are 404. |
| `04-debug-long-wait.png`  | `/debug` after SSL-ignore — still blank for the same reason. |
