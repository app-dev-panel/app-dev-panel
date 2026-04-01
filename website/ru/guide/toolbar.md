---
title: Тулбар
---

# Тулбар

Тулбар — это встраиваемый виджет отладки, который внедряется непосредственно в HTML-страницы вашего приложения. Он отображается в виде компактной панели в нижней части экрана и показывает ключевые метрики текущего запроса — время ответа, использование памяти, количество логов, событий и многое другое. Нажмите кнопку FAB, чтобы развернуть панель, или откройте полную панель отладки в новом окне.

```
┌─────────────────────────────────────────────────────────────┐
│  Страница вашего приложения                                 │
│                                                             │
│  [контент страницы...]                                      │
│                                                             │
├─────────────────────────────────────────────────────────────┤
│ GET /api/users 200 │ 42ms │ 4MB │ api.users │ 5│ 12│ ⚙ FAB │  ← Тулбар
└─────────────────────────────────────────────────────────────┘
```

В отличие от [Панели отладки](/ru/guide/debug-panel) (отдельного SPA по адресу `/debug`), тулбар живёт **внутри страниц вашего приложения** — не нужно переключаться между окнами во время разработки.

## Принцип работы

### Механизм внедрения

Когда ваше приложение возвращает HTML-ответ, middleware адаптера перехватывает его и внедряет сниппет тулбара перед `</body>`:

```
1. Запрос пользователя → Middleware/listener фреймворка обрабатывает запрос
2. Приложение генерирует HTML-ответ
3. Middleware адаптера определяет Content-Type text/html
4. ToolbarInjector вставляет HTML тулбара перед </body>
5. Ответ отправляется со встроенным тулбаром
```

Внедряемый HTML содержит:
- Контейнер `<div id="app-dev-toolbar">`
- `<link>` на `toolbar/bundle.css`
- `<script>` с конфигурацией (`window['AppDevPanelToolbarWidget']`)
- `<script>`, загружающий `toolbar/bundle.js`

### Сбор данных

Тулбар **не собирает данные** самостоятельно. Он читает данные, уже собранные [коллекторами Kernel](/ru/guide/collectors) и доступные через [REST API](/ru/api/rest):

```
Виджет тулбара (React)
    │
    ├─ GET /debug/api/           → Список debug-записей
    ├─ GET /debug/api/view/{id}  → Полные данные записи для коллектора
    └─ GET /debug/api/event-stream → SSE для обновлений в реальном времени
```

Каждая debug-запись содержит сводные метрики (время запроса, память, количество логов и т.д.), которые тулбар отображает напрямую, не запрашивая данные отдельных коллекторов.

### Обновление в реальном времени

Тулбар обнаруживает новые debug-записи двумя способами:

1. **Service Worker** — Когда зарегистрирован, Service Worker перехватывает ответы и читает заголовок `X-Debug-Id`. Он отправляет сообщение тулбару, который инвалидирует кэш RTK Query и обновляет список записей.

2. **SSE (Server-Sent Events)** — Debug API транслирует уведомления о новых записях через `/debug/api/event-stream`. Тулбар подписывается на этот поток для обновлений в реальном времени.

## Отображаемые метрики

В развёрнутом состоянии тулбар показывает следующие метрики для выбранной debug-записи:

| Метрика | Web | Console | Источник |
|---------|:---:|:-------:|----------|
| HTTP метод + путь + статус | ✓ | — | `entry.request`, `entry.response` |
| Имя команды + код выхода | — | ✓ | `entry.command` |
| Время запроса | ✓ | ✓ | `entry.web.request.processingTime` |
| Пиковая память | ✓ | ✓ | `entry.web.memory.peakUsage` |
| Имя маршрута | ✓ | — | `entry.router.name` |
| Количество логов | ✓ | ✓ | `entry.logger.total` |
| Количество событий | ✓ | ✓ | `entry.event.total` |
| Ошибки валидации | ✓ | ✓ | `entry.validator.total` |
| Временная метка | ✓ | ✓ | `entry.web.request.startTime` |

## Компоненты UI

### SpeedDial FAB

Плавающая кнопка (в правом нижнем углу) предоставляет быстрые действия:

| Действие | Описание |
|----------|----------|
| **Переключение тулбара** | Нажмите FAB для развёртывания/сворачивания кнопок метрик |
| **Открыть панель отладки** | Открывает полную панель `/debug` в новом окне браузера |
| **Список записей** | Открывает модальное окно для просмотра и выбора debug-записей |
| **Переключение iframe** | Показывает/скрывает встроенный iframe с полной панелью отладки (с изменяемым размером) |

### Встроенный iframe

При включении iframe тулбар загружает полную панель отладки внутри iframe с изменяемым размером в нижней части страницы. Iframe взаимодействует с тулбаром через `postMessage`:

- **Выбор записи** — Выбор записи в тулбаре переводит iframe к этой записи
- **Навигация по коллекторам** — Нажатие на кнопку метрики (например, Логи) переводит iframe к соответствующему представлению коллектора
- **Изменение размера** — Перетащите разделитель для изменения высоты iframe

### Синхронизация состояния через Redux

Тулбар использует `redux-state-sync` для синхронизации состояния между тулбаром и основной панелью отладки (если обе открыты):

- `toolbarOpen` — Развёрнут ли тулбар
- `baseUrl` — URL бэкенд API

Это обеспечивает согласованное поведение при использовании тулбара вместе с панелью в отдельном окне.

## Конфигурация

Каждый адаптер поддерживает два параметра для тулбара:

| Параметр | По умолчанию | Описание |
|----------|--------------|----------|
| `enabled` | `true` | Внедрять тулбар в HTML-ответы |
| `static_url` | `''` (пусто) | Базовый URL для ассетов тулбара. Пусто = используется `static_url` панели |

:::tabs key:framework
== Symfony
```yaml
# config/packages/app_dev_panel.yaml
app_dev_panel:
    toolbar:
        enabled: true
        static_url: ''   # Использует panel.static_url по умолчанию
```
== Laravel
```php
// config/app-dev-panel.php
'toolbar' => [
    'enabled' => true,
    'static_url' => '',  // Использует panel.static_url по умолчанию
],
```
== Yii 3
```php
// config/params.php
'app-dev-panel/yii3' => [
    'toolbar' => [
        'enabled' => true,
        'staticUrl' => '',  // Использует panel.staticUrl по умолчанию
    ],
],
```
== Yii 2
```php
// config/web.php
'modules' => [
    'debug-panel' => [
        'class' => \AppDevPanel\Adapter\Yii2\Module::class,
        'toolbarEnabled' => true,
        'toolbarStaticUrl' => '',  // Использует panelStaticUrl по умолчанию
    ],
],
```
:::

### Отключение тулбара

Установите `enabled` в `false`, чтобы предотвратить внедрение тулбара, сохраняя остальную функциональность ADP:

:::tabs key:framework
== Symfony
```yaml
app_dev_panel:
    toolbar:
        enabled: false
```
== Laravel
```php
'toolbar' => [
    'enabled' => false,
],
```
== Yii 3
```php
'app-dev-panel/yii3' => [
    'toolbar' => [
        'enabled' => false,
    ],
],
```
== Yii 2
```php
'modules' => [
    'debug-panel' => [
        'class' => \AppDevPanel\Adapter\Yii2\Module::class,
        'toolbarEnabled' => false,
    ],
],
```
:::

## Интеграция с адаптерами

Механизм внедрения различается для каждого фреймворка, но все используют общий <class>AppDevPanel\Api\Toolbar\ToolbarInjector</class> из API-слоя.

### Symfony

Внедрение тулбара происходит в event subscriber `HttpSubscriber` на событии `kernel.response` (приоритет -1024). После добавления заголовка `X-Debug-Id` подписчик вызывает `ToolbarInjector::inject()` для HTML-ответов.

<class>AppDevPanel\Api\Toolbar\ToolbarInjector</class> и <class>AppDevPanel\Api\Toolbar\ToolbarConfig</class> регистрируются как сервисы в DI-контейнере через `AppDevPanelExtension`.

### Laravel

`DebugMiddleware` выполняет внедрение в методе `handle()` после сбора данных ответа и установки заголовка `X-Debug-Id`. Проверяет Content-Type и вызывает `ToolbarInjector::inject()` для HTML-ответов.

`ToolbarConfig` и `ToolbarInjector` регистрируются как singleton в `AppDevPanelServiceProvider`.

### Yii 3

Выделенный PSR-15 middleware — <class>AppDevPanel\Adapter\Yii3\Api\ToolbarMiddleware</class> — обрабатывает внедрение. Должен быть размещён в стеке middleware после `DebugHeaders`, чтобы debug ID был доступен.

Регистрируется в `config/di-api.php` вместе с `ToolbarConfig` и `ToolbarInjector`.

### Yii 2

Обработчик события `WebListener::onAfterRequest()` выполняет внедрение. После установки заголовка `X-Debug-Id` проверяет формат ответа HTML и вызывает `ToolbarInjector::inject()`.

Инжектор создаётся через `Module::createToolbarInjector()` с использованием свойств модуля `$toolbarEnabled` и `$toolbarStaticUrl`.

## Статические ассеты

Ассеты тулбара (bundle.js, bundle.css) обслуживаются из подкаталога `toolbar/` относительно static URL панели:

```
{static_url}/toolbar/bundle.js
{static_url}/toolbar/bundle.css
```

При использовании локальных ассетов (собранных через `make build-panel`) бандл тулбара автоматически копируется в директорию ассетов каждого адаптера в подкаталог `toolbar/`:

```
libs/Adapter/Symfony/Resources/public/toolbar/bundle.js
libs/Adapter/Laravel/resources/dist/toolbar/bundle.js
libs/Adapter/Yii3/resources/dist/toolbar/bundle.js
libs/Adapter/Yii2/resources/dist/toolbar/bundle.js
```

## Разработка

Пакет тулбара имеет собственный Vite dev-сервер на порту 3001:

```bash
cd libs/frontend/packages/toolbar
npm run start    # Запускает Vite на http://localhost:3001
```

Это запускает отдельную страницу с виджетом тулбара для разработки. Укажите вашему адаптеру на dev-сервер:

:::tabs key:framework
== Symfony
```yaml
app_dev_panel:
    toolbar:
        static_url: 'http://localhost:3001'
```
== Laravel
```php
'toolbar' => [
    'static_url' => 'http://localhost:3001',
],
```
== Yii 3
```php
'app-dev-panel/yii3' => [
    'toolbar' => [
        'staticUrl' => 'http://localhost:3001',
    ],
],
```
== Yii 2
```php
'modules' => [
    'debug-panel' => [
        'class' => \AppDevPanel\Adapter\Yii2\Module::class,
        'toolbarStaticUrl' => 'http://localhost:3001',
    ],
],
```
:::

::: tip
Страница разработки тулбара на порту 3001 требует работающего бэкенда (по умолчанию `http://127.0.0.1:8080`) для получения debug-записей. Без бэкенда кнопка FAB отображается, но метрики не показываются.
:::

## Архитектура

```
┌──────────────────────────────────────────────────┐
│  HTML-страница пользователя                      │
│                                                  │
│  ┌──────────────────────────────────────────────┐│
│  │  React-виджет тулбара (Portal)               ││
│  │  ├─ SpeedDial FAB                            ││
│  │  ├─ ButtonGroup с метриками                  ││
│  │  │   ├─ RequestItem / CommandItem            ││
│  │  │   ├─ RequestTimeItem, MemoryItem          ││
│  │  │   ├─ LogsItem, EventsItem, ValidatorItem  ││
│  │  │   └─ DateItem                             ││
│  │  └─ Встроенный iFrame (опционально, resizable)││
│  └──────────────────────────────────────────────┘│
└──────────────────────────────────────────────────┘
         │ HTTP                              │ postMessage
         ▼                                   ▼
┌─────────────────┐              ┌─────────────────┐
│  Debug REST API │              │  Панель отладки │
│  /debug/api/*   │              │  (в iframe)     │
└─────────────────┘              └─────────────────┘
```

**Исходный код:** `libs/frontend/packages/toolbar/` (React-виджет), `libs/API/src/Toolbar/` (PHP-инжектор)
