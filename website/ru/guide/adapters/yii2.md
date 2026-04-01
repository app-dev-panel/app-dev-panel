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
            'redis' => true,
            'elasticsearch' => true,
            'view' => true,
            'template' => true,
            'code_coverage' => false, // opt-in, требует pcov или xdebug
            // ... все коллекторы включены по умолчанию
        ],
        'ignoredRequests' => ['/debug/api/**', '/inspect/api/**'],
        'ignoredCommands' => ['help', 'list', 'cache/*', 'asset/*'],
        'allowedIps' => ['127.0.0.1', '::1'],
    ],
],
```

## Коллекторы

Поддерживает все коллекторы Kernel, а также специфичный для Yii 2 сбор данных:

| Коллектор | Механизм | Данные |
|-----------|----------|--------|
| Database | `DbProfilingTarget` (логгер Yii) | SQL-запросы, время, кол-во строк |
| Log | `DebugLogTarget` (real-time Yii log target) | Сообщения логов с маппингом PSR-3 уровней |
| Mailer | `BaseMailer::EVENT_AFTER_SEND` | From, to, cc, bcc, subject, body |
| Asset Bundles | `View::EVENT_END_PAGE` | Бандлы: класс, пути, CSS/JS, зависимости |
| Translator | `I18NProxy` заменяет `Yii::$app->i18n` | Поиск переводов, пропущенные переводы |
| View | `View::EVENT_AFTER_RENDER` | Файл рендеринга, вывод, параметры |
| Templates | `View::EVENT_BEFORE_RENDER` + `EVENT_AFTER_RENDER` | Время рендеринга шаблонов (поддержка вложенности) |
| Redis | Прямые вызовы коллектора | Redis-команды, время, ошибки |
| Elasticsearch | Прямые вызовы коллектора | ES-запросы, время, количество совпадений |
| Code Coverage | Расширение `pcov` / `xdebug` | Покрытие строк по файлам (opt-in) |
| Authorization | `User::EVENT_AFTER_LOGIN/LOGOUT` | События аутентификации, идентификация пользователя |
| Router | `UrlRuleProxy` оборачивает все URL-правила | Данные маршрутизации, время |

### Коллекторы View и Template

**ViewCollector** подключается к `yii\base\View::EVENT_AFTER_RENDER` для захвата каждого рендеринга view с путём к файлу, выводом и параметрами. Автоматически обнаруживает дублирующиеся рендеры.

**TemplateCollector** подключается к `EVENT_BEFORE_RENDER` и `EVENT_AFTER_RENDER` для измерения времени рендеринга. Корректно обрабатывает вложенный рендеринг (например, layout → partial → widget) с помощью стека таймеров по файлам.

### Code Coverage

Code coverage — **opt-in** (`'code_coverage' => false` по умолчанию). Требует расширение `pcov` или `xdebug`. Без них коллектор возвращает `driver: null`. Подробности на странице [Коллекторы — Code Coverage](/ru/guide/collectors#code-coverage-collector).

## Интеграция с переводчиком

Адаптер заменяет компонент приложения `i18n` на `I18NProxy` — расширенный `yii\i18n\I18N`, который переопределяет метод `translate()`. Все вызовы `Yii::t()` перехватываются и записываются в `TranslatorCollector` автоматически. Подробности на странице [Переводчик](/ru/guide/translator).

## Инспектор базы данных

`Yii2DbSchemaProvider` предоставляет инспекцию схемы БД через `yii\db\Schema`. Без настроенного компонента базы данных используется `NullSchemaProvider`.
