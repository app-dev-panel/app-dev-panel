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

Их можно раздавать напрямую веб-сервером или встраивать в PHP-адаптеры.

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
