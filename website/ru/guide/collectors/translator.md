---
title: Коллектор переводов
---

# Коллектор переводов

Собирает обращения к переводам во время выполнения запроса — разрешённые переводы, отсутствующие ключи, используемые локали и категории.

![Панель коллектора переводов](/images/collectors/translator.png)

## Что собирает

| Поле | Описание |
|------|----------|
| `category` | Категория/домен перевода |
| `locale` | Целевая локаль |
| `message` | Ключ перевода |
| `translation` | Разрешённый перевод (или `null`, если отсутствует) |
| `missing` | Был ли перевод не найден |
| `fallbackLocale` | Использованная резервная локаль (если есть) |

## Схема данных

```json
{
    "translations": [
        {
            "category": "messages",
            "locale": "en",
            "message": "welcome.title",
            "translation": "Welcome to App",
            "missing": false,
            "fallbackLocale": null
        },
        {
            "category": "messages",
            "locale": "fr",
            "message": "missing.key",
            "translation": null,
            "missing": true,
            "fallbackLocale": "en"
        }
    ],
    "missingCount": 1,
    "totalCount": 2,
    "locales": ["en", "fr"],
    "categories": ["messages"]
}
```

**Сводка** (отображается в списке отладочных записей):

```json
{
    "translator": {
        "total": 2,
        "missing": 1
    }
}
```

## Контракт

```php
use AppDevPanel\Kernel\Collector\TranslatorCollector;
use AppDevPanel\Kernel\Collector\TranslationRecord;

$collector->logTranslation(new TranslationRecord(
    category: 'messages',
    locale: 'en',
    message: 'welcome.title',
    translation: 'Welcome to App',
    missing: false,
));
```

::: info
<class>\AppDevPanel\Kernel\Collector\TranslatorCollector</class> реализует <class>\AppDevPanel\Kernel\Collector\SummaryCollectorInterface</class>. Не зависит от других коллекторов.
:::

## Как это работает

Коллектор получает данные от `TranslatorProxy`, который перехватывает вызовы сервиса переводов. Каждый вызов `trans()` / `translate()` записывается вместе с результатом.

Подробные примеры интеграции для каждого фреймворка см. на странице [Переводчик](/ru/guide/translator).

## Панель отладки

- **Список переводов** — все обращения с ключом, разрешённым значением и локалью
- **Обнаружение отсутствующих** — отсутствующие переводы подсвечены красным
- **Разбивка по локалям** — переводы сгруппированы по локали
- **Фильтрация по категориям** — фильтр по домену/категории перевода
