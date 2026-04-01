# Адаптер Yii 3

Адаптер Yii 3 — эталонный адаптер ADP. Он связывает ADP Kernel и API с Yii 3 через систему конфигурационных плагинов.

## Установка

```bash
composer require app-dev-panel/adapter-yii3
```

Пакет автоматически регистрируется через систему config-плагинов Yii 3 — ручная настройка не требуется.

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

Добавьте следующие middleware в стек вашего веб-приложения (порядок важен):

```
DebugHeaders → ErrorCatcher → YiiApiMiddleware → ... → Router
```

- **DebugHeaders** — должен быть внешним, чтобы добавлять `X-Debug-Id` даже при ошибках
- **YiiApiMiddleware** — перехватывает запросы `/debug/api/*` до роутера

## Коллекторы

Включает специфичные для Yii коллекторы: запросы к БД, почта, очереди, роутер, валидатор, переводчик и представления — в дополнение ко всем коллекторам Kernel (логи, события, исключения, HTTP-клиент и др.).

## Интеграция с переводчиком

При установленном пакете `yiisoft/translator` адаптер регистрирует <class>AppDevPanel\Adapter\Yii3\Collector\Translator\TranslatorInterfaceProxy</class> в `trackedServices`. Все вызовы `translate()` на `Yiisoft\Translator\TranslatorInterface` перехватываются автоматически. Подробности на странице [Переводчик](/ru/guide/translator).

## Инспектор базы данных

Инспекция схемы базы данных осуществляется через `Yiisoft\Db` с помощью <class>AppDevPanel\Adapter\Yii3\Inspector\DbSchemaProvider</class>.
