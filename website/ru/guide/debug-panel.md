---
title: Панель отладки
description: "Панель отладки ADP -- React SPA для просмотра логов, запросов, событий и других данных из PHP-приложения."
---

# Панель отладки

Панель отладки ADP — это React SPA, предоставляющий веб-интерфейс для просмотра отладочных данных, собранных из вашего приложения. При установке адаптера панель автоматически доступна по адресу `/debug`.

::: tip Live Demo
Попробуйте панель без установки: [Live Demo](https://app-dev-panel.github.io/app-dev-panel/demo/). Введите URL вашего приложения в поле backend для подключения.
:::

## Как это работает

Каждый адаптер регистрирует маршрут `/debug`, который отдаёт минимальную HTML-страницу. Эта страница:

1. Загружает `bundle.js` и `bundle.css` из источника статических ресурсов (по умолчанию — GitHub Pages)
2. Внедряет конфигурацию — URL бэкенда (определяется автоматически из текущего запроса), базовый путь роутера и т.д.
3. Монтирует React SPA, который взаимодействует с эндпоинтами `/debug/api/*` и `/inspect/api/*`

```
Браузер → GET /debug              → Адаптер отдаёт HTML
        → GET /debug/static/*     → AssetsController отдаёт bundle.js/css из
                                    app-dev-panel/frontend-assets/dist/ (локально)
        → React SPA монтируется
        → Получает данные из /debug/api/*
```

Отдельный фронтенд-сервер не нужен. Панель работает сразу после установки адаптера — `app-dev-panel/frontend-assets` подтягивается транзитивно и раздаётся локально.

## Доступ к панели

После установки адаптера откройте приложение в браузере и перейдите по адресу:

```
http://ваше-приложение/debug
```

Панель поддерживает клиентскую маршрутизацию, поэтому все подпути (например, `/debug/logs`, `/debug/inspector/routes`) обрабатываются SPA.

::: tip Встроенный сервер PHP
При использовании встроенного сервера PHP установите `PHP_CLI_SERVER_WORKERS=3` или больше. ADP выполняет параллельные запросы (SSE + получение данных); однопоточный режим вызывает таймауты.

```bash
PHP_CLI_SERVER_WORKERS=3 php -S 127.0.0.1:8080 -t public
```
:::

## Источник статических ресурсов

По умолчанию панель загружает ресурсы из локальной Composer-сборки по адресу `/debug/static/`:

```
http://ваше-приложение/debug/static/bundle.js
http://ваше-приложение/debug/static/bundle.css
http://ваше-приложение/debug/static/toolbar/bundle.js
```

`AssetsController` читает файлы из `vendor/app-dev-panel/frontend-assets/dist/` и отдаёт их с immutable cache-заголовками. Без походов по сети.

URL статики можно переопределить и загружать ресурсы из другого источника:

### Вариант 1: Локальная сборка Composer (по умолчанию)

Настройка не требуется. `app-dev-panel/frontend-assets` тянется транзитивно вместе с адаптером; `PanelConfig::$staticUrl` по умолчанию равен `/debug/static`.

### Вариант 1b: GitHub Pages CDN

Установите `staticUrl` в `PanelConfig::CDN_STATIC_URL` (или в литерал `https://app-dev-panel.github.io/app-dev-panel`), чтобы загружать последний релиз с GitHub Pages. Удобно для демо; ломается, когда CDN-версия расходится с установленным пакетом.

### Вариант 2: Локальные ресурсы из релиза

Скачайте `panel-dist.tar.gz` из [релиза на GitHub](https://github.com/app-dev-panel/app-dev-panel/releases), распакуйте в публичную директорию и настройте URL статики:

```bash
# Скачать и распаковать
curl -L https://github.com/app-dev-panel/app-dev-panel/releases/latest/download/panel-dist.tar.gz | tar xz -C public/adp-panel
```

Эта же сборка публикуется как `frontend-dist.zip` для встроенного обновлятора (см. [Обновление фронтенда](#обновление-фронтенда) ниже).

Затем настройте адаптер:

:::tabs key:framework
== Symfony
```yaml
# config/packages/app_dev_panel.yaml
app_dev_panel:
    panel:
        static_url: '/adp-panel'
```
== Laravel
```php
// config/app-dev-panel.php
'panel' => [
    'static_url' => '/adp-panel',
],
```
== Yii 3
```php
// config/params.php
'app-dev-panel/yii3' => [
    'panel' => [
        'staticUrl' => '/adp-panel',
    ],
],
```
== Yii 2
```php
// config/web.php
'modules' => [
    'app-dev-panel' => [
        'class' => \AppDevPanel\Adapter\Yii2\Module::class,
        'panelStaticUrl' => '/adp-panel',
    ],
],
```
:::

### Вариант 3: Сборка из исходников

Если вы разрабатываете сам ADP или хотите использовать собственную сборку, можно собрать фронтенд из исходников и скопировать ассеты в пакеты адаптеров:

```bash
make build-panel
```

Эта команда:
1. Собирает пакеты panel и toolbar через Vite
2. Копирует `bundle.js`, `bundle.css` и ассеты в директорию каждого адаптера:
   - `libs/Adapter/Symfony/Resources/public/`
   - `libs/Adapter/Laravel/resources/dist/`
   - `libs/Adapter/Yii3/resources/dist/`
   - `libs/Adapter/Yii2/resources/dist/`

Для публикации ассетов в playground-приложения:

```bash
make build-install-panel    # Сборка + публикация одной командой
```

::: tip Автоопределение
Если `static_url` оставлен пустым (по умолчанию), каждый адаптер автоматически проверяет наличие собранных ассетов в своей директории. Если `bundle.js` найден локально, адаптер раздаёт ассеты из локального пути вместо GitHub Pages — **настройка не требуется**.

| Адаптер | Путь к локальным ассетам | Раздаётся как |
|---------|--------------------------|---------------|
| Symfony | `Resources/public/bundle.js` | `/bundles/appdevpanel` |
| Laravel | `resources/dist/bundle.js` → публикуется в `public/vendor/app-dev-panel/` | `/vendor/app-dev-panel` |
| Yii 3 | `resources/dist/bundle.js` → симлинк в `@public/app-dev-panel/` | `/app-dev-panel` |
| Yii 2 | `resources/dist/bundle.js` → симлинк в `@webroot/app-dev-panel/` | `/app-dev-panel` |

Для возврата к GitHub Pages удалите собранные ассеты из директории адаптера.
:::

### Вариант 4: Vite dev-сервер

При разработке фронтенда можно направить панель на локальный Vite dev-сервер:

```bash
cd libs/frontend
npm start    # Запускает Vite на http://localhost:3000
```

Затем настройте:

:::tabs key:framework
== Symfony
```yaml
app_dev_panel:
    panel:
        static_url: 'http://localhost:3000'
```
== Laravel
```php
'panel' => [
    'static_url' => 'http://localhost:3000',
],
```
== Yii 3
```php
'app-dev-panel/yii3' => [
    'panel' => [
        'staticUrl' => 'http://localhost:3000',
    ],
],
```
== Yii 2
```php
'modules' => [
    'app-dev-panel' => [
        'class' => \AppDevPanel\Adapter\Yii2\Module::class,
        'panelStaticUrl' => 'http://localhost:3000',
    ],
],
```
:::

## Модули панели

SPA панели включает следующие модули:

| Модуль | Путь | Описание |
|--------|------|----------|
| Debug | `/debug` | Просмотр собранных записей — логи, SQL-запросы, события, исключения, timeline, HTTP-запросы, кэш, почта и т.д. |
| Inspector | `/debug/inspector/*` | Состояние приложения в реальном времени — маршруты, конфигурация, схема БД, git, кэш, файлы, переводы, Composer-пакеты |
| LLM | `/debug/llm` | AI-чат и анализ отладочных данных. Поддержка OpenRouter, Anthropic, OpenAI и ACP (локальные агенты, например Claude Code) |
| MCP | `/debug/mcp` | Настройка MCP (Model Context Protocol) сервера |
| OpenAPI | `/debug/openapi` | Swagger UI для REST API ADP |

## Справочник по конфигурации

| Параметр | По умолчанию | Описание |
|----------|-------------|----------|
| `static_url` | `/debug/static` | Базовый URL для статических ресурсов панели (bundle.js, bundle.css, тулбар). Раздаётся локально `AssetsController` из `app-dev-panel/frontend-assets`. Установите в `https://app-dev-panel.github.io/app-dev-panel`, чтобы использовать CDN GitHub Pages. |
| `viewer_base_path` | `/debug` | Префикс маршрута, на котором смонтирована панель |

## Архитектура

Рендеринг панели выполняется на уровне API (<class>AppDevPanel\Api\Panel\PanelController</class>), который не зависит от фреймворка. Каждый адаптер просто маршрутизирует `/debug` и `/debug/*` на тот же `ApiApplication`, который обрабатывает API-запросы.

```
GET /debug/logs/detail
    → Адаптер перехватывает /debug/* (но не /debug/api/*)
    → ApiApplication направляет в PanelController
    → PanelController рендерит HTML с:
        - <link> на bundle.css
        - <script> с конфигом window['AppDevPanelWidget']
        - <script> загружающий bundle.js
    → Браузер загружает React SPA
    → SPA использует клиентскую маршрутизацию для /debug/logs/detail
```

Маршруты панели пропускают обёртку JSON-ответов и аутентификацию по токену — через них проходят только CORS и фильтр по IP.

## Фронтенд как Composer-пакет

Каждый адаптер требует `app-dev-panel/frontend-assets` — Composer-пакет, содержащий предсобранную SPA панели. При установке адаптера Composer автоматически подтягивает `vendor/app-dev-panel/frontend-assets/dist/` — никакой отдельной загрузки не требуется.

| Что содержит пакет | Путь после установки |
|---------------------|----------------------|
| Предсобранный `dist/` (`index.html`, JS, CSS, ассеты) | `vendor/app-dev-panel/frontend-assets/dist/` |
| Хелпер `FrontendAssets::path()` | `AppDevPanel\FrontendAssets\FrontendAssets` |

### Автономный сервер — `adp serve`

Команда `adp serve` запускает встроенный PHP-сервер с API на `/debug/api/*` и `/inspect/api/*`, а на остальных путях отдаёт SPA панели. Если `--frontend-path` не указан, команда вызывает `FrontendAssets::path()` и использует сборку, установленную через Composer — полная панель доступна на `http://127.0.0.1:8888/` без дополнительных флагов:

```bash
php vendor/bin/adp serve --host=127.0.0.1 --port=8888 --storage-path=./var/adp
```

Чтобы указать другую сборку (например, свою кастомную или dev-копию):

```bash
php vendor/bin/adp serve --frontend-path=/path/to/my/dist
```

### Обновление фронтенда

Поддерживаются два канала:

1. **Composer (рекомендуется)** — поднимаем тег из split-репозитория [`frontend-assets`](https://github.com/app-dev-panel/frontend-assets):

   ```bash
   composer update app-dev-panel/frontend-assets
   ```

2. **Прямая загрузка (для PHAR-установок)** — CLI `frontend:update` скачивает `frontend-dist.zip` из [последнего GitHub Release](https://github.com/app-dev-panel/app-dev-panel/releases) и распаковывает его на месте:

   ```bash
   php vendor/bin/adp frontend:update check
   php vendor/bin/adp frontend:update download --path=/path/to/dist
   ```

   Команда записывает файл `.adp-version` рядом с `index.html`, чтобы последующие `check` понимали, доступно ли обновление.

### Как собирается пакет

Монорепа **не** хранит `libs/FrontendAssets/dist/` — он генерируется на каждом push воркфлоу `.github/workflows/split.yml`:

1. `npm ci && npm run build -w packages/sdk && npm run build -w packages/panel` (внутри `libs/frontend/`).
2. Вывод Vite копируется в `libs/FrontendAssets/dist/`.
3. Локальный одноразовый коммит добавляет файлы `dist/`, затем `splitsh-lite` извлекает `libs/FrontendAssets/` (исходники + dist) как subtree.
4. Subtree force-пушится в [`app-dev-panel/frontend-assets`](https://github.com/app-dev-panel/frontend-assets) и тегается версией релиза, если триггером был тег `v*`.

Потребители видят только split-репозиторий — `composer require` никогда не заходит в монорепу.
