---
title: Переводчик
---

# Переводчик

ADP автоматически перехватывает вызовы переводчика в вашем приложении и записывает каждый запрос — включая отсутствующие переводы. Изменения кода не требуются.

## TranslatorCollector

`TranslatorCollector` реализует `SummaryCollectorInterface` и фиксирует каждый запрос перевода во время выполнения запроса.

### TranslationRecord

Каждый вызов перевода создаёт DTO `TranslationRecord`:

| Поле | Тип | Описание |
|------|-----|----------|
| `category` | `string` | Домен/группа перевода (например, `messages`, `app`, `validation`) |
| `locale` | `string` | Целевая локаль (например, `en`, `de`, `fr`) |
| `message` | `string` | Исходный идентификатор сообщения / ключ перевода |
| `translation` | `?string` | Переведённая строка или `null`, если перевод отсутствует |
| `missing` | `bool` | `true`, если перевод не найден |
| `fallbackLocale` | `?string` | Использованная резервная локаль (если применимо) |

### Собранные данные

`getCollected()` возвращает:

```php
[
    'translations' => [
        ['category' => 'messages', 'locale' => 'de', 'message' => 'welcome', 'translation' => 'Willkommen!', 'missing' => false, 'fallbackLocale' => null],
        ['category' => 'messages', 'locale' => 'de', 'message' => 'goodbye', 'translation' => null, 'missing' => true, 'fallbackLocale' => null],
    ],
    'missingCount' => 1,
    'totalCount' => 2,
    'locales' => ['de'],
    'categories' => ['messages'],
]
```

### Сводка

`getSummary()` возвращает:

```php
[
    'translator' => [
        'total' => 2,
        'missing' => 1,
    ],
]
```

## Определение отсутствующих переводов

Каждый proxy фреймворка определяет отсутствие перевода по-разному, но логика единообразна: если переводчик возвращает исходный идентификатор сообщения без изменений, перевод считается отсутствующим.

| Фреймворк | Метод определения |
|-----------|-------------------|
| Symfony | `trans()` возвращает `$id` без изменений |
| Laravel | `get()` возвращает `$key` без изменений |
| Yii 3 | `translate()` возвращает `$id` без изменений |
| Yii 2 | `MessageSource::translate()` возвращает `false` |

## Proxy фреймворков

Каждый адаптер предоставляет proxy переводчика, который оборачивает нативный переводчик фреймворка и передаёт данные в `TranslatorCollector`. Proxy регистрируются автоматически — ручная настройка не требуется.

### Symfony — `SymfonyTranslatorProxy`

Декорирует `Symfony\Contracts\Translation\TranslatorInterface`. Перехватывает вызовы `trans()`.

**Подключение:** Регистрируется через `CollectorProxyCompilerPass` с помощью паттерна `setDecoratedService()` Symfony.

```php
// Все вызовы trans() перехватываются автоматически
$translator->trans('welcome', [], 'messages', 'de');
```

- Домен по умолчанию: `messages` (когда `$domain` равен `null`)
- Использует трейт `ProxyDecoratedCalls` для проброса методов

### Laravel — `LaravelTranslatorProxy`

Декорирует `Illuminate\Contracts\Translation\Translator`. Перехватывает вызовы `get()` и `choice()`.

**Подключение:** Регистрируется через `$app->extend('translator', ...)` в сервис-провайдере.

```php
// Все хелперы перевода перехватываются
__('messages.welcome');
trans('messages.welcome');
Lang::get('messages.welcome');
```

- Разбирает точечную нотацию ключей Laravel: `group.key` → категория `group`, сообщение `key`
- JSON-переводы (без точки): категория по умолчанию `messages`
- Использует трейт `ProxyDecoratedCalls` для проброса методов

### Yii 3 — `TranslatorInterfaceProxy`

Декорирует `Yiisoft\Translator\TranslatorInterface`. Перехватывает вызовы `translate()`.

**Подключение:** Регистрируется как `trackedService` в `params.php` — система proxy-сервисов адаптера автоматически выполняет декорирование.

```php
// Все вызовы translate() перехватываются
$translator->translate('welcome', [], 'app', 'de');
```

- Категория по умолчанию: `app` (когда `$category` равен `null` и `withDefaultCategory()` не вызывался)
- Поддерживает иммутабельные `withDefaultCategory()` и `withLocale()` через `clone`

### Yii 2 — `I18NProxy`

Расширяет `yii\i18n\I18N` и переопределяет `translate()`. Заменяет компонент приложения `i18n`.

**Подключение:** Модуль заменяет `Yii::$app->i18n` экземпляром proxy, копируя существующую конфигурацию переводов.

```php
// Все вызовы Yii::t() перехватываются
Yii::t('app', 'welcome', [], 'de');
```

- Вызывает `$messageSource->translate()` напрямую — возвращает `false` при отсутствии (надёжнее сравнения строк)
- Безопасен без коллектора (null-safe `$this->collector?->logTranslation()`)

## Конфигурация

Перехват переводов включён по умолчанию, когда активен `TranslatorCollector`. Дополнительная настройка не требуется.

:::tabs key:framework
== Symfony
```yaml
# config/packages/app_dev_panel.yaml
app_dev_panel:
    collectors:
        translator: true    # включён по умолчанию
```
== Yii 2
```php
// конфигурация приложения
'modules' => [
    'debug-panel' => [
        'collectors' => [
            'translator' => true,   // включён по умолчанию
        ],
    ],
],
```
== Yii 3
```php
// config/params.php
'app-dev-panel/yiisoft' => [
    'collectors' => [
        TranslatorCollector::class => true,  // включён по умолчанию
    ],
    'trackedServices' => [
        TranslatorInterface::class => [TranslatorInterfaceProxy::class, TranslatorCollector::class],
    ],
],
```
== Laravel
```php
// config/app-dev-panel.php
'collectors' => [
    'translator' => true,   // включён по умолчанию
],
```
:::

## Панель во фронтенде

TranslatorPanel в интерфейсе отладки отображает:

- **Значок сводки** — общее количество переводов и количество отсутствующих
- **Таблица переводов** — все записанные переводы с категорией, локалью, сообщением, переведённым значением и статусом
- **Фильтры** — фильтрация по локали, категории или статусу отсутствия
- **Подсветка отсутствующих** — отсутствующие переводы визуально выделены для быстрого обнаружения
