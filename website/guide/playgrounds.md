---
title: Playgrounds
---

# Playgrounds

Playgrounds are minimal, working applications demonstrating ADP integration with specific PHP frameworks. Each playground installs the corresponding ADP adapter, configures collectors, and exposes demo endpoints that generate debug data.

## Available Playgrounds

| Playground | Framework | Port | Adapter |
|------------|-----------|------|---------|
| `yii3-app` | Yii 3 | 8101 | <pkg>app-dev-panel/adapter-yii3</pkg> |
| `symfony-basic-app` | Symfony 7 | 8102 | <pkg>app-dev-panel/adapter-symfony</pkg> |
| `yii2-basic-app` | Yii 2 | 8103 | <pkg>app-dev-panel/adapter-yii2</pkg> |
| `laravel-app` | Laravel 12 | 8104 | <pkg>app-dev-panel/adapter-laravel</pkg> |

## Running Playgrounds

### Install Dependencies

```bash
make install-playgrounds
```

### Start Servers

Each playground runs on its own port. Start in separate terminals:

:::tabs key:framework
== Symfony
```bash
cd playground/symfony-basic-app && PHP_CLI_SERVER_WORKERS=3 php -S 127.0.0.1:8102 -t public
```
== Yii 2
```bash
cd playground/yii2-basic-app && PHP_CLI_SERVER_WORKERS=3 php -S 127.0.0.1:8103 -t public
```
== Yii 3
```bash
cd playground/yii3-app && ./yii serve --port=8101
```
== Laravel
```bash
cd playground/laravel-app && PHP_CLI_SERVER_WORKERS=3 php -S 127.0.0.1:8104 -t public
```
:::

::: tip
`PHP_CLI_SERVER_WORKERS=3` is required for SSE — one worker handles the SSE stream, others handle API requests.
:::

## Common URLs

All playgrounds expose the same URL structure:

| Path | Purpose |
|------|---------|
| `/` | Home / demo page |
| `/debug/api/` | Debug entry list (JSON) |
| `/debug/api/view/{id}` | Full debug entry data |
| `/debug/api/summary/{id}` | Entry summary |
| `/inspect/api/*` | Inspector endpoints |
| `/test/fixtures/*` | Test fixture endpoints |

## Integration Methods

Each framework uses a different adapter registration approach:

| Framework | Integration | Registration |
|-----------|------------|--------------|
| Yii 3 | Config plugin | Automatic via `yiisoft/config` |
| Symfony | Bundle | Manual in `config/bundles.php` (dev/test only) |
| Laravel | Package discovery | Automatic via `extra.laravel.providers` |
| Yii 2 | Module + Bootstrap | Auto-bootstrap via `extra.bootstrap` in composer |

### Storage Paths

| Framework | Path | Resolution |
|-----------|------|------------|
| Yii 3 | `runtime/debug/` | `@runtime` alias |
| Symfony | `var/debug/` | `%kernel.project_dir%` |
| Laravel | `storage/debug/` | `storage_path('debug')` |
| Yii2 | `runtime/debug/` | `@runtime` alias |

## Running Test Fixtures

Fixtures are automated test endpoints that exercise each collector:

```bash
make fixtures              # All playgrounds in parallel
make fixtures-yii3         # Yii 3 only
make fixtures-symfony      # Symfony only
make fixtures-yii2         # Yii2 only
make fixtures-laravel      # Laravel only
```

For PHPUnit E2E tests (requires running servers):

```bash
make test-fixtures         # All playgrounds
make test-fixtures-yii3    # Yii 3 only
```

## Adding a New Playground

To add a playground for a new framework:

1. Create `playground/<framework>-app/` with a minimal application using the framework's official skeleton
2. Install ADP packages using path repositories:

```json
{
    "repositories": [
        {"type": "path", "url": "../../libs/Kernel"},
        {"type": "path", "url": "../../libs/API"},
        {"type": "path", "url": "../../libs/Adapter/<Framework>"}
    ],
    "require": {
        "app-dev-panel/adapter-<framework>": "*"
    }
}
```

3. Configure collectors, storage, and API routes per the adapter's documentation
4. Implement `/test/fixtures/*` endpoints matching `FixtureRegistry` (see [Contributing](/guide/contributing))
5. Add Makefile targets for serve, fixtures, and Mago checks
6. Assign the next available port (8105+)

### Port Allocation

| Port | Assignment |
|------|-----------|
| 8100 | Frontend dev server |
| 8101 | Yii 3 |
| 8102 | Symfony |
| 8103 | Yii2 |
| 8104 | Laravel |
| 8105+ | Available |
