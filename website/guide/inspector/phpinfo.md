---
title: PHP Info
---

# PHP Info

View the full output of `phpinfo()` rendered in the panel.

![PHP Info](/images/inspector/phpinfo.png)

## What It Shows

The complete PHP configuration output including:
- PHP version and build info
- Loaded extensions and their settings
- Server API (SAPI)
- Configuration directives (`php.ini` values)
- Environment variables
- HTTP request/response headers

## API Endpoints

| Method | Path | Description |
|--------|------|-------------|
| GET | `/inspect/api/phpinfo` | PHP info output |

::: info
The output is the same as calling `phpinfo()` directly, rendered as HTML within the panel iframe.
:::
