# Yii3 ADP install report — после мержа PR #248

Повторение сценария из `docs/install-report/` на мастере с мерженным `frontend-assets`.

## Сценарий и окружение

- `composer create-project yiisoft/app` — свежий шаблон.
- `composer require app-dev-panel/adapter-yii3` — установлен через `path` репозитории,
  указывающие на `libs/*` монорепо (потому что на Packagist `frontend-assets` ещё
  отсутствует, а `adapter-yii3` последний тег `v0.2.0` от 22.04 — до мержа PR #248).
- `libs/FrontendAssets/dist/` предварительно заполнен через `npm run build -w packages/panel`,
  т.е. воспроизведён CI-шаг из `.github/workflows/split.yml`.
- В `config/web/di/application.php` вручную добавлены `ToolbarMiddleware` и
  `YiiApiMiddleware` (Проблема 1 из первого отчёта остаётся — документация по-прежнему
  не упоминает `ToolbarMiddleware`).

## Два сценария и их состояние

### A. Интегрированный: `/` и `/debug` на Yii3-приложении пользователя

`PHP_CLI_SERVER_WORKERS=3 php -S 127.0.0.1:8202 -t public`.

| Путь | Состояние |
|------|-----------|
| `/` — главная с тулбаром | Тулбар инжектится, но **не отрисовывается**: `toolbar/bundle.js` и `toolbar/bundle.css` на CDN возвращают **404**. |
| `/debug` — HTML-оболочка панели | Возвращает 200, но React не запускается: `bundle.js` 200, однако `assets/Config-*.js` и `assets/preload-helper-*.js` — **404** (хешированные чанки на CDN не совпадают с теми, что референсятся в bundle.js). |

Скриншоты: `01-home-framework.png`, `02-debug-framework.png` — визуально идентичны старым
из `docs/install-report/` (тулбара нет, `/debug` — белый).

Причина: PR #248 ничего не меняет в `libs/API/src/Panel/PanelConfig.php` — константа
`DEFAULT_STATIC_URL = 'https://app-dev-panel.github.io/app-dev-panel'` остаётся прежней.
`PanelController` и `ToolbarInjector` инжектят ссылки на CDN, `vendor/app-dev-panel/frontend-assets/dist/`
лежит в vendor мёртвым грузом — ни один middleware/контроллер его не раздаёт.

### B. Standalone: `php vendor/bin/adp serve`

`./vendor/bin/adp serve --host=127.0.0.1 --port=8302 --storage-path=/tmp/adp-storage-v2`.

| Путь | Состояние |
|------|-----------|
| `/` | **Работает**. Отдаётся `index.html` из `FrontendAssets::path()` с относительными ссылками (`./bundle.js`), встроенный PHP-сервер раздаёт файлы из `vendor/app-dev-panel/frontend-assets/dist/` через `-t <path>`. |
| `/debug/api/` | 200 JSON. |

Скриншот: `03-adp-serve-panel.png` — панель отрисовывается полностью, весь UI работает.

## Ошибки, всплывшие в `adp serve`

Оба блокера появились из-за PR #248 и не относятся к установке из Packagist (проверял на
symlink-установке через path repositories — в реальном Composer-инсталле их эффект иной,
см. комментарии).

1. **`libs/Cli/bin/adp:25-28` — автолоадер ищется по неправильным путям.**
   ```php
   $autoloadPaths = [
       __DIR__ . '/../../../vendor/autoload.php',
       __DIR__ . '/../vendor/autoload.php',
   ];
   ```
   После `composer require` файл оказывается в `vendor/app-dev-panel/cli/bin/adp`.
   Отсюда `../../../vendor/autoload.php` = `vendor/vendor/autoload.php` — не существует.
   Правильно: `__DIR__ . '/../../../autoload.php'` = `vendor/autoload.php`.
   В таком виде `adp` работает только из монорепо (`libs/Cli/bin/adp` → `vendor/autoload.php`).
   Такой же баг в `libs/McpServer/bin/adp-mcp`.

2. **`libs/Cli/bin/adp:47-48` — `Application::add()` больше не существует в Symfony Console 8.**
   ```php
   $application->add(new AppDevPanel\Cli\Command\ServeCommand());
   $application->add(new AppDevPanel\Cli\Command\FrontendUpdateCommand());
   ```
   В Symfony Console 8 метод переименован в `addCommand()`. Шаблон `yiisoft/app` требует
   `symfony/console: ^7.4.7 || ^8.0.7` — при 8.x получается fatal error. Чинится простым
   `sed s/->add(/->addCommand(/`.

Оба бага не ловятся тестами и плайграундами, потому что в монорепо используется
`symfony/console ^7`, а `libs/Cli/tests/Unit/Command/ServeCommandTest.php` запускается
с автолоадером самого монорепо.

## Что именно закрыл PR #248

| Проблема из v1 | Закрыта? | Пояснение |
|---|---|---|
| 1. Yii3 docs без `ToolbarMiddleware` | ❌ | `website/guide/adapters/yii3.md` не изменён. |
| 2. CDN 404 при установке из Packagist | ❌ для интегрированного сценария, ✅ для `adp serve` | Composer-пакет `frontend-assets` создан и корректно доставляет `dist/`, но `PanelConfig::DEFAULT_STATIC_URL` всё ещё на CDN — интегрированная установка продолжает падать. Исправлен только standalone-флоу. |
| 3. `frontend:update` rate-limit | ⚠️ смягчена | Composer стал рекомендованным каналом обновления (это реально работает через `frontend-assets`), но сам `FrontendUpdateCommand` не трогали — PHAR-пользователи без `GITHUB_TOKEN` упрутся в 403 как раньше. |

## Что осталось доделать, чтобы интегрированный сценарий тоже работал

1. `libs/API/src/Panel/AssetsController.php` — читает файлы из `FrontendAssets::path()`
   с защитой от path traversal, ставит `Content-Type` и `Cache-Control: immutable`.
2. Роут `GET /debug/static/{path:.+}` — расширить перехват `YiiApiMiddleware`
   с `/debug/api/*` до `/debug/*` (static + api + панель-HTML) + аналогичные
   роуты в Symfony/Laravel/Yii2 адаптерах.
3. `PanelConfig::DEFAULT_STATIC_URL` → `/debug/static` (относительный). Для тулбара
   `ToolbarInjector::resolveStaticUrl()` подхватит его же. Оставить возможность
   переопределить через конфиг для тех, кто хочет свой CDN.
4. Починить два бага в `libs/Cli/bin/adp` (автолоадер + `addCommand`) и в
   `libs/McpServer/bin/adp-mcp`.
5. Обновить `website/guide/adapters/yii3.md` секцией про ручную регистрацию
   `ToolbarMiddleware` + `YiiApiMiddleware`.

## Скриншоты

| Файл | Сценарий | Видно |
|------|----------|-------|
| `01-home-framework.png` | `http://127.0.0.1:8202/` (Yii3 app) | Нет тулбара — 404 на CDN bundle. |
| `02-debug-framework.png` | `http://127.0.0.1:8202/debug` (Yii3 app) | Белая страница — 404 на чанки CDN. |
| `03-adp-serve-panel.png` | `http://127.0.0.1:8302/` (`adp serve`) | Панель отрисовывается, UI работает. |
