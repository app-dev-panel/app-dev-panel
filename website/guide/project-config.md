---
title: Project Config
description: "Persist Frames and OpenAPI specs to a VCS-tracked file (config/adp/project.json) so every developer on your team starts with the same panel setup after `git pull`."
---

# Project Config

Frames (embedded iframes) and OpenAPI specs you add through the panel UI are **persisted to a JSON file alongside your application source**. Commit that file and every developer on your team gets the same setup after `git pull` — no more "works on my machine" debug-panel state.

Behind the scenes, the panel keeps a `localStorage` cache for offline UX, but the on-disk file is the source of truth: on every page load the panel pulls the latest version, and your edits are debounced (500 ms) into a single `PUT` to the backend.

## File Layout

The panel writes two files into a framework-specific config directory:

```
<your-app>/
└── config/
    └── adp/
        ├── project.json   ← commit this — shared with the team
        └── .gitignore     ← auto-created, ignores secrets.json
```

| File | Commit? | Contents |
|------|---------|----------|
| `project.json` | **Yes** | `{version, frames, openapi}` — display name → URL maps |
| `.gitignore` | **Yes** | Auto-generated with `secrets.json` pre-listed |
| `secrets.json` | **No** (gitignored) | Reserved for future API-keys / per-machine overrides |

Example `project.json`:

```json
{
    "version": 1,
    "frames": {
        "Grafana": "https://grafana.example.com/",
        "Logs": "https://kibana.example.com/"
    },
    "openapi": {
        "Main API": "/api/openapi.json",
        "Webhooks": "https://webhooks.example.com/openapi.json"
    }
}
```

The shape is intentionally simple: each entry is a `displayName → url` mapping. Hand-editing the file works as long as you stay JSON-valid.

## Per-Framework Config Path

Each adapter resolves the config directory to a framework-idiomatic location and exposes an override knob.

:::tabs key:framework
== Yii 3

**Default path:** `<app-root>/config/adp` — resolved from the `@root` Yiisoft alias.

**Override** in `config/params.php`:

```php
'app-dev-panel/yii3' => [
    // ...
    'projectConfigPath' => '@root/config/adp', // default
    // 'projectConfigPath' => '@root/.adp',    // example: hide under a dotted folder
],
```

Anything Yiisoft `Aliases` understands is valid (`@root`, `@runtime`, etc., or an absolute path).

== Symfony

**Default path:** `%kernel.project_dir%/config/adp`.

**Override** in `config/packages/app_dev_panel.yaml`:

```yaml
app_dev_panel:
    project_config_path: '%kernel.project_dir%/config/adp'  # default
    # project_config_path: '%kernel.project_dir%/.adp'      # example
```

The container parameter is `app_dev_panel.project_config_path`. Symfony's `%kernel.project_dir%` is resolved at compile time.

== Laravel

**Default path:** `base_path('config/adp')`.

**Override** in `config/app-dev-panel.php`:

```php
return [
    // ...
    'project_config_path' => base_path('config/adp'),  // default
    // 'project_config_path' => base_path('.adp'),     // example
];
```

You can also drive it from `.env`:

```php
'project_config_path' => env('APP_DEV_PANEL_PROJECT_CONFIG', base_path('config/adp')),
```

== Yii 2

**Default path:** `@app/config/adp`.

**Override** on the module declaration in your application config:

```php
'modules' => [
    'app-dev-panel' => [
        'class' => \AppDevPanel\Adapter\Yii2\Module::class,
        // ...
        'projectConfigPath' => '@app/config/adp',  // default
        // 'projectConfigPath' => '@app/.adp',     // example
    ],
],
```

The path is resolved through `Yii::getAlias()`, so any registered alias (or an absolute path) works.

== Spiral

**Default path:** `<root>/app/config/adp` — the resolver consults `APP_DEV_PANEL_ROOT_PATH` (set by the application entry point alongside the `PathResolver`) before falling back to `getcwd()`. This keeps the file out of `public/` even when `php -S` flips the working directory to docroot.

**Override** in `app/config/app-dev-panel.php`:

```php
return [
    // ...
    'project_config_path' => directory('app') . 'config/adp',  // default
    // 'project_config_path' => directory('root') . '.adp',    // example
];
```

You can also drive it from the environment without writing a config file:

```dotenv
APP_DEV_PANEL_PROJECT_CONFIG_PATH=/srv/app/app/config/adp
```

`AdpConfig::projectConfigPath()` checks them in priority order: explicit config → `APP_DEV_PANEL_PROJECT_CONFIG_PATH` → `APP_DEV_PANEL_ROOT_PATH/app/config/adp` → `getcwd()/app/config/adp`.
:::

## API Endpoint

The frontend talks to the backend through two endpoints under `/debug/api/project`:

| Method | Path | Purpose |
|--------|------|---------|
| `GET` | `/debug/api/project/config` | Returns `{config: {version, frames, openapi}, configDir}` — `configDir` is the absolute path the user can `git add` |
| `PUT` | `/debug/api/project/config` | Accepts a bare `{frames, openapi}` document or the GET wrapper. Malformed entries (non-string keys/values, empty strings) are dropped silently |

You can curl it to verify your installation:

```bash
curl http://127.0.0.1:8101/debug/api/project/config | jq
# {
#   "data": {
#     "config": {"version": 1, "frames": {}, "openapi": {}},
#     "configDir": "/home/you/app/config/adp"
#   }
# }
```

```bash
curl -X PUT \
  -H 'Content-Type: application/json' \
  -d '{"frames":{"Grafana":"https://grafana.example/"},"openapi":{}}' \
  http://127.0.0.1:8101/debug/api/project/config
```

After the first `PUT` the `config/adp/` directory and the two files appear on disk.

## How the Frontend Syncs

The panel UI follows a dual-store model so it stays usable when the backend is unreachable:

1. **On boot** the panel dispatches `getProjectConfig`. The server document overwrites the local Frames/OpenAPI Redux slices.
2. **On user edit** (add/delete/rename a Frame or OpenAPI spec) the change is applied locally first (instant UI), then debounced 500 ms into a single `PUT`.
3. **First-run migration:** when the backend returns an empty config but `localStorage` has pre-existing entries (typical when upgrading from an older ADP version), the panel pushes those entries to the server **once** so users don't lose their setup.
4. **Backend offline:** the `Settings` dialog shows an explicit warning. Edits remain in `localStorage`; the next successful boot syncs them up.

The settings dialog also surfaces the `configDir` path so you know exactly which file to commit:

```
┌────────────────────────────────────────────┐
│  Frames                                    │
│  …                                         │
│                                            │
│  ⓘ Synced to /your-app/config/adp/        │
│    project.json. Commit it to share with   │
│    your team.                              │
└────────────────────────────────────────────┘
```

## Verifying Across Playgrounds

Each playground writes to a framework-appropriate location. Run the playgrounds (`make serve`) and curl them:

```bash
for p in 8101 8102 8103 8104; do
  curl -s "http://127.0.0.1:$p/debug/api/project/config" | jq -r '.data.configDir'
done
```

| Playground | Port | `configDir` |
|------------|-----:|-------------|
| Yii 3 | 8101 | `playground/yii3-app/config/adp` |
| Symfony | 8102 | `playground/symfony-app/config/adp` |
| Yii 2 | 8103 | `playground/yii2-basic-app/src/config/adp` |
| Laravel | 8104 | `playground/laravel-app/config/adp` |
| Spiral | 8105 | `playground/spiral-app/app/config/adp` |

(The Yii 2 path lands under `src/` because the playground sets `@app` to that directory — your real app may resolve it elsewhere.)

## What's Coming Next

`secrets.json` will hold per-machine values that **must not** be committed: the Anthropic / OpenRouter API keys, OAuth tokens, and any future ACP environment overrides. The `.gitignore` rule is already in place; the storage class and API endpoint will land in a follow-up release.

## Technical Details

- **Backend storage**: <class>AppDevPanel\Kernel\Project\FileProjectConfigStorage</class> — atomic save (temp + rename), `0644` mode, auto-creates the directory and `.gitignore`.
- **Backend interface**: <class>AppDevPanel\Kernel\Project\ProjectConfigStorageInterface</class>.
- **Backend value object**: <class>AppDevPanel\Kernel\Project\ProjectConfig</class> — immutable, drops malformed entries on `fromArray()`.
- **HTTP controller**: <class>AppDevPanel\Api\Project\Controller\ProjectController</class>.
- **Frontend module**: `libs/frontend/packages/panel/src/Module/Project/`.
- **Sync middleware**: `Module/Project/projectSyncMiddleware.ts` — handles bootstrap, debounce, migration, suppression of feedback loops.
- **RTK Query API**: `libs/frontend/packages/sdk/src/API/Project/Project.ts`.
