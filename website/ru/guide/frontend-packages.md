---
title: Фронтенд-пакеты
description: "Фронтенд npm-пакеты ADP: @app-dev-panel/panel, @app-dev-panel/toolbar и @app-dev-panel/sdk на GitHub Packages."
---

# Фронтенд-пакеты

ADP предоставляет три npm-пакета под скоупом `@app-dev-panel`, опубликованные в [GitHub Packages](https://github.com/orgs/app-dev-panel/packages).

| Пакет | Описание |
|-------|----------|
| `@app-dev-panel/sdk` | Общая библиотека: React-компоненты, API-клиенты (RTK Query), система тем, утилиты |
| `@app-dev-panel/panel` | Основное SPA — панель отладки |
| `@app-dev-panel/toolbar` | Встраиваемый виджет-тулбар для интеграции в приложение |

## Установка

### 1. Настройка реестра GitHub Packages

Фронтенд-пакеты ADP размещены в GitHub Packages. Добавьте скоуп реестра в проект:

```bash
echo "@app-dev-panel:registry=https://npm.pkg.github.com" >> .npmrc
```

::: tip Аутентификация
GitHub Packages требует аутентификации даже для публичных пакетов. Создайте [personal access token](https://github.com/settings/tokens) с правом `read:packages` и добавьте в `~/.npmrc`:

```bash
//npm.pkg.github.com/:_authToken=YOUR_GITHUB_TOKEN
```
:::

### 2. Установка пакетов

```bash
# Только SDK (компоненты, API-клиенты, утилиты)
npm install @app-dev-panel/sdk

# Полное приложение панели
npm install @app-dev-panel/panel

# Виджет тулбара
npm install @app-dev-panel/toolbar
```

## Пакеты

### SDK (`@app-dev-panel/sdk`)

Базовая библиотека, используемая панелью и тулбаром. Содержит:

- **API-клиенты** — RTK Query эндпоинты для debug-данных, инспектора, git и LLM API
- **React-компоненты** — `JsonRenderer`, `CodeHighlight`, `DataGrid`, `SearchFilter`, `EmptyState`, `CommandPalette`
- **Компоненты макета** — `TopBar`, `UnifiedSidebar`, `EntrySelector`, `ContentPanel`
- **Система тем** — MUI 5 тема со светлым/тёмным режимом, токены дизайна, фирменные цвета
- **Утилиты** — нечёткий поиск, транслитерация раскладки (QWERTY/ЙЦУКЕН), форматирование дат
- **SSE** — хук `useServerSentEvents` для обновлений debug-записей в реальном времени
- **Управление состоянием** — Redux Toolkit слайсы для состояния приложения, debug-записей, уведомлений

```typescript
import { createAppTheme } from '@app-dev-panel/sdk/Component/Theme/DefaultTheme';
import { JsonRenderer } from '@app-dev-panel/sdk/Component/JsonRenderer';
import { useServerSentEvents } from '@app-dev-panel/sdk/Component/useServerSentEvents';
```

### Panel (`@app-dev-panel/panel`)

Основное SPA панели отладки. Модули:

- **Debug** — панели коллекторов (логи, база данных, события, исключения, таймлайн и др.)
- **Inspector** — состояние приложения в реальном времени (маршруты, конфигурация, БД, git, кэш, файлы, переводы и др.)
- **LLM** — AI-чат и анализ
- **MCP** — страница настройки MCP-сервера
- **OpenAPI** — интеграция Swagger UI

### Toolbar (`@app-dev-panel/toolbar`)

Встраиваемый виджет, показывающий компактную панель отладки внизу приложения. Отображает ключевые метрики (время запроса, память, количество запросов) и ссылки на полную панель.

## Готовые сборки

Каждый [GitHub Release](https://github.com/app-dev-panel/app-dev-panel/releases) включает собранные статические файлы:

| Файл | Содержимое |
|------|------------|
| `panel-dist.tar.gz` | Production-сборка панели |
| `toolbar-dist.tar.gz` | Production-сборка тулбара |
| `frontend-dist.zip` | Сборка панели + тулбара (`bundle.{js,css}` в корне, тулбар в `toolbar/`) для CLI `frontend:update` |

Их можно раздавать напрямую веб-сервером или встраивать в PHP-адаптеры.

## Composer-дистрибутив — `app-dev-panel/frontend-assets`

SPA панели также публикуется как Composer-пакет, чтобы PHP-приложения получали собранный фронт автоматически при установке адаптера. Это основной канал — каждый адаптер (`adapter-yii3`, `adapter-symfony`, `adapter-laravel`, `adapter-yii2`) требует его.

| Пакет | Namespace | Содержит |
|-------|-----------|----------|
| `app-dev-panel/frontend-assets` | `AppDevPanel\FrontendAssets\` | Предсобранный `dist/` + хелпер `FrontendAssets::path()` |

### Как собирается и релизится

Исходники лежат в `libs/frontend/packages/panel`. Директория `dist/` **не** хранится в монорепе — она генерируется в момент релиза воркфлоу `Monorepo Split` (`.github/workflows/split.yml`):

1. На каждом `push` в `master` / `*.x` / `v*` воркфлоу выполняет `npm ci && npm run build -w packages/sdk && npm run build -w packages/panel` внутри `libs/frontend/`.
2. Вывод Vite копируется в `libs/FrontendAssets/dist/` и добавляется одноразовым локальным коммитом.
3. `splitsh-lite` извлекает `libs/FrontendAssets/` (включая `dist/`) как subtree SHA.
4. Subtree force-пушится в [`app-dev-panel/frontend-assets`](https://github.com/app-dev-panel/frontend-assets) — и тегается версией релиза, если триггером был тег `v*`.

Packagist и `composer require` видят именно этот split-репозиторий. Монорепа `libs/FrontendAssets/` хранит только `composer.json`, `src/FrontendAssets.php` и заглушку `.gitkeep` — всё в `dist/` игнорируется локально.

### Как используется

При установке любого адаптера транзитивно подтягивается `app-dev-panel/frontend-assets`. Хелпер `FrontendAssets::path()` возвращает абсолютный путь к собранному `dist/`:

```php
use AppDevPanel\FrontendAssets\FrontendAssets;

FrontendAssets::path();    // /vendor/app-dev-panel/frontend-assets/dist
FrontendAssets::exists();  // true если dist/index.html присутствует
```

CLI-команда `serve` использует этот хелпер в качестве значения по умолчанию для `--frontend-path`, поэтому `php vendor/bin/adp serve` работает из коробки без дополнительных флагов.

### Обновление фронтенда

Поддерживаются два канала:

1. **Composer (по умолчанию)** — `composer update app-dev-panel/frontend-assets` подтягивает последний тег split-репозитория.
2. **Прямая загрузка** — для PHAR-установок или когда Composer недоступен, используйте CLI `frontend:update` (см. [CLI](./cli.md#frontend-update)) для скачивания `frontend-dist.zip` из GitHub Release и распаковки на месте.

## Разработка

### Требования

- Node.js 21+
- npm 10+

### Настройка

```bash
git clone https://github.com/app-dev-panel/app-dev-panel.git
cd app-dev-panel/libs/frontend
npm install
```

### Команды

```bash
npm start              # Запуск Vite dev-серверов (panel + toolbar + sdk)
npm run build          # Production-сборка всех пакетов
npm run check          # Проверка Prettier + ESLint
npm test               # Запуск unit-тестов Vitest
npm run test:e2e       # Запуск браузерных E2E-тестов (требует Chrome)
```

### Структура проекта

```
libs/frontend/
├── packages/
│   ├── sdk/           # Общая библиотека (компоненты, API, тема, утилиты)
│   ├── panel/         # Основное SPA
│   └── toolbar/       # Виджет тулбара
├── lerna.json         # Независимое версионирование
└── package.json       # Корень npm workspaces
```

### Технологии

- React 19, TypeScript 5.5+
- Vite 5 (сборщик)
- MUI 5 (Material UI компоненты)
- Redux Toolkit + RTK Query (управление состоянием + API)
- React Router 6 (навигация)
- Vitest (тестирование)
- Prettier 3.8+ и ESLint 9 (качество кода)
