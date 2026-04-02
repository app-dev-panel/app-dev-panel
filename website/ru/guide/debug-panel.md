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
Браузер → GET /debug → Адаптер отдаёт HTML → Загружает bundle.js с CDN
                                             → React SPA монтируется
                                             → Получает данные из /debug/api/*
```

Отдельный фронтенд-сервер не нужен. Панель работает сразу после установки адаптера.

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

По умолчанию панель загружает ресурсы с GitHub Pages:

```
https://app-dev-panel.github.io/app-dev-panel/bundle.js
https://app-dev-panel.github.io/app-dev-panel/bundle.css
```

Вы можете изменить URL статики для загрузки ресурсов из другого источника:

### Вариант 1: GitHub Pages (по умолчанию)

Настройка не требуется. Ресурсы автоматически загружаются из последнего релиза на GitHub Pages.

### Вариант 2: Локальные ресурсы из релиза

Скачайте `panel-dist.tar.gz` из [релиза на GitHub](https://github.com/app-dev-panel/app-dev-panel/releases), распакуйте в публичную директорию и настройте URL статики:

```bash
# Скачать и распаковать
curl -L https://github.com/app-dev-panel/app-dev-panel/releases/latest/download/panel-dist.tar.gz | tar xz -C public/adp-panel
```

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

### Вариант 3: Vite dev-сервер

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
| LLM | `/debug/llm` | AI-чат и анализ отладочных данных |
| MCP | `/debug/mcp` | Настройка MCP (Model Context Protocol) сервера |
| OpenAPI | `/debug/openapi` | Swagger UI для REST API ADP |

## Справочник по конфигурации

| Параметр | По умолчанию | Описание |
|----------|-------------|----------|
| `static_url` | `https://app-dev-panel.github.io/app-dev-panel` | Базовый URL для статических ресурсов панели (bundle.js, bundle.css) |
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
