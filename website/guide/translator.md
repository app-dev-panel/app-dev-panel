---
title: Translator
description: "ADP automatically intercepts translation calls and records every lookup, including missing translations."
---

# Translator

ADP automatically intercepts translation calls in your application and records every lookup — including missing translations. No code changes required.

## TranslatorCollector

<class>AppDevPanel\Kernel\Collector\TranslatorCollector</class> implements <class>AppDevPanel\Kernel\Collector\SummaryCollectorInterface</class> and captures every translation lookup during request execution.

### TranslationRecord

Each translation call produces a <class>AppDevPanel\Kernel\Collector\TranslationRecord</class> DTO:

| Field | Type | Description |
|-------|------|-------------|
| `category` | `string` | Translation domain/group (e.g., `messages`, `app`, `validation`) |
| `locale` | `string` | Target locale (e.g., `en`, `de`, `fr`) |
| `message` | `string` | Original message ID / translation key |
| `translation` | `?string` | Translated string, or `null` if missing |
| `missing` | `bool` | `true` when no translation was found |
| `fallbackLocale` | `?string` | Fallback locale used (if applicable) |

### Collected Data

`getCollected()` returns:

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

### Summary

`getSummary()` returns:

```php
[
    'translator' => [
        'total' => 2,
        'missing' => 1,
    ],
]
```

## Missing Translation Detection

Each framework proxy detects missing translations differently, but the logic is consistent: if the translator returns the original message ID unchanged, the translation is considered missing.

| Framework | Detection Method |
|-----------|-----------------|
| Symfony | `trans()` returns `$id` unchanged |
| Laravel | `get()` returns `$key` unchanged |
| Yii 3 | `translate()` returns `$id` unchanged |
| Yii 2 | `MessageSource::translate()` returns `false` |

## Framework Proxies

Each adapter provides a translator proxy that wraps the framework's native translator and feeds data to <class>AppDevPanel\Kernel\Collector\TranslatorCollector</class>. Proxies are registered automatically — no manual setup needed.

### Symfony — <class>AppDevPanel\Adapter\Symfony\Proxy\SymfonyTranslatorProxy</class>

Decorates `Symfony\Contracts\Translation\TranslatorInterface`. Intercepts `trans()` calls.

**Wiring:** Registered via <class>AppDevPanel\Adapter\Symfony\DependencyInjection\CollectorProxyCompilerPass</class> using Symfony's `setDecoratedService()` pattern.

```php
// All trans() calls are intercepted automatically
$translator->trans('welcome', [], 'messages', 'de');
```

- Default domain: `messages` (when `$domain` is `null`)
- Uses `ProxyDecoratedCalls` trait for method forwarding

### Laravel — <class>AppDevPanel\Adapter\Laravel\Proxy\LaravelTranslatorProxy</class>

Decorates `Illuminate\Contracts\Translation\Translator`. Intercepts `get()` and `choice()` calls.

**Wiring:** Registered via `$app->extend('translator', ...)` in the service provider.

```php
// All translation helpers are intercepted
__('messages.welcome');
trans('messages.welcome');
Lang::get('messages.welcome');
```

- Parses Laravel's dot-notation keys: `group.key` → category `group`, message `key`
- JSON translations (no dot): category defaults to `messages`
- Uses `ProxyDecoratedCalls` trait for method forwarding

### Yii 3 — <class>AppDevPanel\Adapter\Yii3\Collector\Translator\TranslatorInterfaceProxy</class>

Decorates `Yiisoft\Translator\TranslatorInterface`. Intercepts `translate()` calls.

**Wiring:** Registered as a `trackedService` in `params.php` — the adapter's service proxy system handles decoration automatically.

```php
// All translate() calls are intercepted
$translator->translate('welcome', [], 'app', 'de');
```

- Default category: `app` (when `$category` is `null` and no `withDefaultCategory()` was called)
- Supports immutable `withDefaultCategory()` and `withLocale()` via `clone`

### Yii 2 — <class>AppDevPanel\Adapter\Yii2\Proxy\I18NProxy</class>

Extends `yii\i18n\I18N` and overrides `translate()`. Replaces the `i18n` application component.

**Wiring:** The module replaces `Yii::$app->i18n` with the proxy instance, copying existing translations config.

```php
// All Yii::t() calls are intercepted
Yii::t('app', 'welcome', [], 'de');
```

- Calls `$messageSource->translate()` directly — returns `false` when missing (more reliable than string comparison)
- Safe to use without collector (null-safe `$this->collector?->logTranslation()`)

## Configuration

Translation interception is enabled by default when the <class>AppDevPanel\Kernel\Collector\TranslatorCollector</class> is active. No additional configuration is needed.

:::tabs key:framework
== Symfony
```yaml
# config/packages/app_dev_panel.yaml
app_dev_panel:
    collectors:
        translator: true    # enabled by default
```
== Yii 2
```php
// application config
'modules' => [
    'app-dev-panel' => [
        'collectors' => [
            'translator' => true,   // enabled by default
        ],
    ],
],
```
== Yii 3
```php
// config/params.php
'app-dev-panel/yii3' => [
    'collectors' => [
        TranslatorCollector::class => true,  // enabled by default
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
    'translator' => true,   // enabled by default
],
```
:::

## Frontend Panel

The TranslatorPanel in the debug UI displays:

- **Summary badge** — total translation count and missing count
- **Translation table** — all recorded translations with category, locale, message, translated value, and missing status
- **Filters** — filter by locale, category, or missing status
- **Missing highlights** — missing translations are visually highlighted for quick identification
