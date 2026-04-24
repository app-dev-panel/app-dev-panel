---
description: "Установка и настройка ADP для Yii 3. Конфигурационные плагины, подключение коллекторов и middleware."
---

# Адаптер Yii 3

Адаптер Yii 3 — эталонный адаптер ADP. Он связывает ADP Kernel и API с Yii 3 через систему конфигурационных плагинов.

## Установка

```bash
composer require app-dev-panel/adapter-yii3
```

Config-плагины Yii 3 автоматически подключают DI, слушатели событий и коллекторы. Единственное, что
плагин настроить не может, — это стек middleware: `config/web/di/application.php` принадлежит приложению,
поэтому три ADP middleware нужно добавить туда вручную (см. раздел [Middleware](#middleware) ниже).

## Конфигурация

Все настройки управляются в `config/params.php`:

```php
'app-dev-panel/yii3' => [
    'enabled' => true,
    'collectors' => [...],
    'trackedServices' => [...],
    'ignoredRequests' => [],
    'ignoredCommands' => [],
    'dumper' => [
        'excludedClasses' => [],
    ],
    'logLevel' => [
        'AppDevPanel\\' => 0,
    ],
    'storage' => [
        'path' => '@runtime/debug',
        'historySize' => 50,
        'exclude' => [],
    ],
],
```

## Middleware

В `config/web/di/application.php` нужно добавить два ADP middleware. **Порядок важен** — адаптер
рассчитывает именно на такой стек:

```
ToolbarMiddleware → ErrorCatcher → YiiApiMiddleware → SessionMiddleware → CsrfTokenMiddleware → RequestCatcherMiddleware → Router
```

| Middleware | Назначение |
|-----------|-----------|
| <class>AppDevPanel\Adapter\Yii3\Api\ToolbarMiddleware</class> | Внедряет тулбар ADP в HTML-ответы перед `</body>`. Должен быть внешним, чтобы переписывать тело, произведённое любыми нижележащими middleware (включая страницы ошибок). |
| <class>AppDevPanel\Adapter\Yii3\Api\YiiApiMiddleware</class> | Перехватывает `/debug/*` (SPA панели + `/debug/static/*` ассеты + `/debug/api/*`) и `/inspect/api/*`, делегируя их ADP API. Должен стоять перед `Router`, чтобы маршруты приложения не затеняли ADP, и после `ErrorCatcher`, чтобы исключения корректно ловились. |

Готовый `config/web/di/application.php` для стокового шаблона `yiisoft/app`:

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

::: warning Порядок middleware
`YiiApiMiddleware` **должен** стоять до `Router`, но после `ErrorCatcher`. Если он после роутера —
ADP маршруты не перехватываются. Если до error-handler'а — ошибки в самом ADP не попадают в Yii
error pipeline. `ToolbarMiddleware` **должен** быть внешним — он переписывает HTML, произведённый
всем, что стоит ниже.
:::

## Коллекторы

Включает специфичные для Yii коллекторы: запросы к БД, почта, очереди, роутер, валидатор, переводчик и представления — в дополнение ко всем коллекторам Kernel (логи, события, исключения, HTTP-клиент и др.).

Дополнительно:

- **Asset bundles** — <class>AppDevPanel\Adapter\Yii3\Collector\Asset\AssetLoaderInterfaceProxy</class> оборачивает `AssetLoaderInterface` для сбора загруженных бандлов с CSS/JS файлами, зависимостями и опциями.
- **Code coverage** — <class>AppDevPanel\Kernel\Collector\CodeCoverageCollector</class> зарегистрирован и собирает покрытие кода построчно за каждый запрос (требуется pcov или xdebug).

## Интеграция с переводчиком

При установленном пакете `yiisoft/translator` адаптер регистрирует <class>AppDevPanel\Adapter\Yii3\Collector\Translator\TranslatorInterfaceProxy</class> в `trackedServices`. Все вызовы `translate()` на `Yiisoft\Translator\TranslatorInterface` перехватываются автоматически. Подробности на странице [Переводчик](/ru/guide/translator).

## Инспектор базы данных

Инспекция схемы базы данных осуществляется через `Yiisoft\Db` с помощью <class>AppDevPanel\Adapter\Yii3\Inspector\DbSchemaProvider</class>.
