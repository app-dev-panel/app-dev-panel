---
title: Translations Inspector
---

# Translations Inspector

Browse and edit translation catalogs across all locales.

## Features

| Feature | Description |
|---------|-------------|
| Catalog browser | View all translation catalogs and their messages |
| Locale comparison | Compare translations across locales |
| Live editing | Update translation messages directly from the panel |

## How It Works

The inspector reads all registered translation category sources and displays messages organized by catalog and locale. You can browse messages, search for specific keys, and edit translations in place.

## API Endpoints

| Method | Path | Description |
|--------|------|-------------|
| GET | `/inspect/api/translations` | All catalogs and messages for all locales |
| PUT | `/inspect/api/translations` | Update a translation message |

**Update request body:**
```json
{
    "catalog": "messages",
    "locale": "en",
    "key": "welcome.title",
    "value": "Welcome to our app"
}
```

## Adapter Support

Requires framework-specific translation integration (e.g., Symfony Translator, Laravel Lang). Returns 501 if translations are not available.

::: tip
Changes made via the Translations Inspector update the application's translation source directly. This is useful for quick fixes during development.
:::
