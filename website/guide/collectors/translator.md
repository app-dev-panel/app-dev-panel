---
title: Translator Collector
---

# Translator Collector

Captures translation lookups during request execution — resolved translations, missing keys, locale usage, and categories.

![Translator Collector panel](/images/collectors/translator.png)

## What It Captures

| Field | Description |
|-------|-------------|
| `category` | Translation category/domain |
| `locale` | Target locale |
| `message` | Translation key |
| `translation` | Resolved translation (or `null` if missing) |
| `missing` | Whether the translation was not found |
| `fallbackLocale` | Fallback locale used (if any) |

## Data Schema

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

**Summary** (shown in debug entry list):

```json
{
    "translator": {
        "total": 2,
        "missing": 1
    }
}
```

## Contract

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
`TranslatorCollector` implements `SummaryCollectorInterface`. It has no dependencies on other collectors.
:::

## How It Works

The collector is fed by the `TranslatorProxy` which intercepts calls to the translator service. Each `trans()` / `translate()` call is recorded with its result.

See the dedicated [Translator](/guide/translator) page for detailed integration examples per framework.

## Debug Panel

- **Translation list** — all lookups with key, resolved value, and locale
- **Missing detection** — missing translations highlighted in red
- **Locale breakdown** — translations grouped by locale
- **Category filtering** — filter by translation domain/category
