# Адаптер Yii 2

Адаптер Yii 2 связывает ADP Kernel и API с Yii 2.0.50+ через механизм bootstrap.

## Установка

```bash
composer require app-dev-panel/adapter-yii2
```

Пакет автоматически регистрируется через `extra.bootstrap` в composer.json. Класс `Bootstrap` регистрирует модуль `debug-panel` автоматически при включенном `YII_DEBUG`.

## Конфигурация

Настройте модуль в конфигурации приложения:

```php
'modules' => [
    'debug-panel' => [
        'class' => \AppDevPanel\Adapter\Yii2\Module::class,
        'storagePath' => '@runtime/debug',
        'historySize' => 50,
        'collectors' => [
            'request' => true,
            'exception' => true,
            'log' => true,
            'event' => true,
            'db' => true,
            'mailer' => true,
            'assets' => true,
            // ... все коллекторы
        ],
        'ignoredRequests' => ['/debug/api/**', '/inspect/api/**'],
        'ignoredCommands' => ['help', 'list', 'cache/*', 'asset/*'],
        'allowedIps' => ['127.0.0.1', '::1'],
    ],
],
```

## Коллекторы

Поддерживает все коллекторы Kernel, а также специфичный для Yii 2 сбор данных: запросы к БД через `DbProfilingTarget` (логгер Yii), захват логов в реальном времени через `DebugLogTarget`, события почты, профилирование ассет-бандлов и перехват переводов.

## Интеграция с переводчиком

Адаптер заменяет компонент приложения `i18n` на `I18NProxy` — расширенный `yii\i18n\I18N`, который переопределяет метод `translate()`. Все вызовы `Yii::t()` перехватываются и записываются в `TranslatorCollector` автоматически.

## Инспектор базы данных

`Yii2DbSchemaProvider` предоставляет инспекцию схемы БД через `yii\db\Schema`. Без настроенного компонента базы данных используется `NullSchemaProvider`.
