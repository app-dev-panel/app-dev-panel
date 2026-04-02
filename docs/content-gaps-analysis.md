# Documentation Content Gaps Analysis

**Date**: 2026-04-02

## Overview

| Metric | Value |
|---|---|
| Total EN pages | 86 |
| Total RU pages | 41 |
| Translation coverage | 47.7% |
| Untranslated pages | 45 |

## Fully Translated Sections

| Section | EN | RU | Coverage |
|---|---|---|---|
| Root pages (index, sponsor) | 2 | 2 | 100% |
| Guide (core pages) | 23 | 23 | 100% |
| Adapters | 4 | 4 | 100% |
| API reference | 4 | 4 | 100% |
| Blog | 7 | 7 | 100% |

## Missing Translations

### Collectors Guide — 29 pages (0% translated)

The entire `guide/collectors/` section has no Russian translations:

- `log`, `event`, `exception`, `database`, `cache`, `redis`, `http-client`, `mailer`
- `queue`, `validator`, `router`, `translator`, `timeline`, `var-dumper`
- `request`, `environment`, `elasticsearch`, `opentelemetry`, `authorization`
- `deprecation`, `service`, `middleware`, `template`, `asset-bundle`
- `web-app-info`, `command`, `console-app-info`, `filesystem-stream`, `http-stream`

### Inspector Guide — 16 pages (0% translated)

The entire `guide/inspector/` section has no Russian translations:

- `routes`, `events`, `config`, `database`, `files`, `commands`
- `composer`, `git`, `authorization`, `cache`, `redis`, `elasticsearch`
- `translations`, `phpinfo`, `opcache`, `coverage`

## Sidebar Fallback Behavior

The Russian locale sidebar links to English paths (`/guide/collectors/...`, `/guide/inspector/...`) for untranslated pages. Users navigating from the Russian interface see English content for these sections.

## Reverse Gaps

No Russian pages exist without English counterparts. No broken sidebar links detected.

## Recommended Translation Priority

1. **Collectors** (29 pages) — largest section, core user-facing documentation
2. **Inspector** (16 pages) — second largest untranslated section
