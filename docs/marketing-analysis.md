# ADP — Product Marketing Analysis & Strategy

**Date**: 2026-03-24
**Document type**: Strategic marketing analysis
**Target audience**: Product team, marketing, engineering leads

---

## Table of Contents

1. [Executive Summary](#1-executive-summary)
2. [Product Overview](#2-product-overview)
3. [Competitive Analysis](#3-competitive-analysis)
4. [Feature Analysis & USPs](#4-feature-analysis--usps)
5. [SWOT Analysis](#5-swot-analysis)
6. [Target Audience & Personas](#6-target-audience--personas)
7. [Marketing Strategy](#7-marketing-strategy)
8. [Internal Improvements](#8-internal-improvements--recommendations)
9. [Go-To-Market Plan](#9-go-to-market-plan)
10. [KPIs & Metrics](#10-kpis--metrics)

---

## 1. Executive Summary

ADP (Application Development Panel) — единственный на рынке **framework-agnostic** отладочный панель для PHP, который работает с Yii 3, Symfony, Laravel и Yii 2 из коробки. В отличие от конкурентов (Laravel Telescope, Symfony Profiler, Clockwork, Ray), привязанных к одному фреймворку, ADP предлагает единый интерфейс для любого PHP-проекта.

### Ключевые дифференциаторы

| Фактор | ADP | Конкуренты |
|--------|-----|-----------|
| Framework support | 4 фреймворка + language-agnostic API | 1 фреймворк каждый |
| Real-time streaming | UDP + SSE | Только HTTP polling |
| Inspector (live state) | 20+ страниц инспекции | Нет аналога |
| Multi-app debugging | Service Registry | Нет |
| Стоимость | Open Source (бесплатно) | Ray — платный, Blackfire — платный |
| Language-agnostic ingestion | OpenAPI 3.1 + Python/TS клиенты | Только PHP |

### Главный тезис

> **"One panel for all your PHP apps. And beyond."**

ADP — это не очередной debugbar. Это полноценная **платформа разработчика**, объединяющая отладку, инспекцию, мониторинг и инструменты (Git, Composer, Code Generation) в одном UI.

---

## 2. Product Overview

### 2.1 Что это

ADP — debugging и development панель, которая:
- Собирает runtime-данные из приложения (логи, события, запросы, исключения, SQL, кэш, очереди, почта)
- Предоставляет web UI для инспекции и анализа
- Работает как SPA + встраиваемый тулбар
- Поддерживает real-time стриминг через UDP и SSE

### 2.2 Архитектура (маркетинговый угол)

```
Любое PHP-приложение → Adapter → Kernel (28 коллекторов) → API (40+ endpoints) → React SPA
```

**Для разработчика это значит:**
- Подключил пакет — работает
- Один UI для всех проектов (Symfony утром, Laravel вечером — один и тот же ADP)
- Не нужно учить разные инструменты для разных фреймворков

### 2.3 Полный каталог возможностей

**28 коллекторов данных:**

| Категория | Коллекторы | Что видит разработчик |
|-----------|-----------|----------------------|
| Логирование | LogCollector, VarDumperCollector | Все логи с уровнями, dump() вызовы |
| HTTP | RequestCollector, HttpClientCollector, HttpStreamCollector | Входящие/исходящие запросы, заголовки, тела |
| База данных | DatabaseCollector | SQL-запросы, время выполнения, биндинги, backtrace |
| Ошибки | ExceptionCollector | Stack traces, chained exceptions, контекст |
| События | EventCollector | Все dispatched events с таймингом |
| Производительность | TimelineCollector, WebAppInfoCollector, ConsoleAppInfoCollector | Timeline, память, время запроса |
| Middleware | MiddlewareCollector | Стек middleware с before/handler/after фазами |
| Кэш | CacheCollector | Hit/miss, операции, ключи |
| Почта | MailerCollector | Отправленные письма с превью |
| Очереди | QueueCollector | Задания, статусы, длительность |
| Валидация | ValidatorCollector | Правила и ошибки валидации |
| Роутинг | RouterCollector | Matched routes, controllers |
| Шаблоны | TwigCollector, WebViewCollector, AssetBundleCollector | Рендеренные шаблоны, ассеты |
| Безопасность | SecurityCollector | Пользователь, роли, firewall |
| Окружение | EnvironmentCollector | PHP config, переменные |
| Файловая система | FilesystemStreamCollector | Файловые операции |
| Сервисы | ServiceCollector | DI-контейнер, вызовы методов |

**Inspector — живая инспекция приложения (20+ страниц):**

| Страница | Что делает |
|----------|-----------|
| Configuration | Параметры DI-контейнера |
| Routes | Браузер маршрутов с проверкой |
| Database | Схема БД, просмотр таблиц с пагинацией |
| File Explorer | Навигация по файлам проекта |
| Git | Статус, лог, checkout прямо из панели |
| Commands | Запуск CLI-команд из UI |
| Composer | Управление пакетами |
| Cache | Просмотр и очистка кэша |
| OPcache | Статистика OPcache |
| Translations | Просмотр и редактирование переводов |
| Tests | Запуск тестов |
| Events | Все слушатели событий |
| Container | Сервисы DI-контейнера |
| PHPInfo | Конфигурация PHP |

**Инструменты разработчика:**
- **Gii** — генерация кода с preview и diff
- **OpenAPI** — Swagger UI для API
- **cURL Builder** — генерация cURL-команд из запросов
- **Request Replay** — повторное выполнение запросов

---

## 3. Competitive Analysis

### 3.1 Конкурентная карта рынка

```
                        Framework-Specific ◄──────────────────► Framework-Agnostic
                              │                                        │
              ┌───────────────┼────────────────┐                       │
    Бесплатно │  Telescope    │  Symfony Prof.  │                   ★ ADP ★
              │  Debugbar     │  Clockwork      │                      │
              ├───────────────┼────────────────┤                       │
     Платно   │  Ray ($49)    │                 │                      │
              │  Blackfire    │  Tideways       │                      │
              └───────────────┴────────────────┘                       │
```

**ADP занимает уникальную нишу**: бесплатный, framework-agnostic, с функционалом, превосходящим платные решения.

### 3.2 Feature Matrix (конкурентное сравнение)

| Feature | ADP | Telescope | Symfony Prof. | Clockwork | Ray | Debugbar |
|---------|:---:|:---------:|:-------------:|:---------:|:---:|:--------:|
| Multi-framework | **4** | 1 | 1 | 3 | 2 | 1 |
| Auto-collectors | **28** | 14 | ~12 | ~10 | 0 | ~8 |
| Live Inspector | **20+** | 0 | 0 | 0 | 0 | 0 |
| Real-time SSE | yes | no | no | no | yes | no |
| UDP streaming | yes | no | no | no | no | no |
| Code generation | yes | no | no | no | no | no |
| Git integration | yes | no | no | no | no | no |
| DB browser | yes | no | no | no | no | no |
| File explorer | yes | no | no | no | no | no |
| Command palette | yes | no | no | no | no | no |
| Dark mode | yes | no | partial | n/a | yes | no |
| PWA/Offline | yes | no | no | no | no | no |
| Multi-app | yes | no | no | no | no | no |
| Language-agnostic | yes | no | no | no | partial | no |
| Open source | yes | yes | yes | yes | no | yes |
| cURL builder | yes | no | no | no | no | no |
| Request replay | yes | no | no | no | no | no |
| Fuzzy search | yes | no | no | no | no | no |
| i18n editor | yes | no | no | no | no | no |

### 3.3 Ключевые выводы

- **vs Telescope**: ADP превосходит по количеству фич в 2-3 раза + работает не только с Laravel
- **vs Symfony Profiler**: ADP даёт superior UX (SPA, dark mode, command palette) + Inspector
- **vs Clockwork**: ADP — полноценная платформа vs легковесный extension
- **vs Ray ($49/год)**: ADP бесплатный, автоматический сбор данных vs ручной dump()
- **vs Debugbar**: ADP — SPA с Inspector и multi-framework support


---

## 4. Feature Analysis & USPs

### 4.1 Killer Features (5 главных продающих фич)

**KF-1: "One Panel, Any Framework"**
- Посыл: *"Switch projects, not tools"*
- Разработчик с несколькими фреймворками получает один инструмент
- Продвижение: скринкаст переключения между Laravel и Symfony в одном ADP

**KF-2: Inspector — Live App Introspection**
- Посыл: *"X-ray your running app"*
- 20+ страниц live-инспекции: routes, DB schema, DI, config, Git, files
- Ни один конкурент не предлагает этого
- Продвижение: demo-видео "всё об приложении без CLI"

**KF-3: Language-Agnostic Ingestion API**
- Посыл: *"Debug anything. PHP, Python, Node — one panel."*
- OpenAPI 3.1 спецификация + клиенты Python/TypeScript
- Продвижение: пример PHP + Python ML-сервис в одном ADP

**KF-4: Multi-App Service Registry**
- Посыл: *"Microservices? One debug panel for all."*
- Сервисы регистрируются, инспекция маршрутизируется автоматически
- Продвижение: архитектурная диаграмма микросервисов

**KF-5: Developer Toolkit (Git, Composer, Gii, cURL)**
- Посыл: *"Your IDE's best friend"*
- Генерация кода, управление пакетами, Git, cURL, повтор запросов
- Продвижение: workflow-видео

### 4.2 Hidden Gems (недооценённые фичи для контента)

| # | Фича | Маркетинговый потенциал |
|---|------|----------------------|
| 1 | Request Replay | "Отлаживай webhooks без повтора действий" |
| 2 | cURL Builder | "Сгенерируй cURL для любого запроса одним кликом" |
| 3 | Translation Editor | "Редактируй переводы без перезагрузки" |
| 4 | Middleware Waterfall | "Визуализируй стек middleware с таймингами" |
| 5 | Object Graph Inspector | "Инспектируй PHP-объекты на любую глубину" |
| 6 | Fuzzy Search + QWERTY/ЙЦУКЕН | "Ищи без переключения раскладки" |
| 7 | Command Palette (Ctrl+K) | "Навигация как в VS Code" |
| 8 | OPcache Inspector | "OPcache статистика без phpinfo()" |
| 9 | SQL EXPLAIN в панели | "Оптимизируй запросы, не покидая браузер" |
| 10 | PWA + Offline | "Добавь на рабочий стол, работай без сети" |

---

## 5. SWOT Analysis

### Strengths (Сильные стороны)
- S1: Единственный framework-agnostic debug panel на рынке
- S2: 28 коллекторов — больше всех конкурентов
- S3: Inspector (20+ страниц) — нет аналогов
- S4: Language-agnostic ingestion API (OpenAPI 3.1)
- S5: Open Source + бесплатный = низкий барьер входа
- S6: Современный tech stack (React 18, MUI, RTK, Vite)
- S7: PWA с offline-поддержкой
- S8: Multi-app Service Registry для микросервисов
- S9: Real-time UDP + SSE streaming
- S10: Комплексные dev tools (Git, Composer, Gii)

### Weaknesses (Слабые стороны)
- W1: Нет бренда/узнаваемости → нужен маркетинг
- W2: Остаточные следы Yii-происхождения (порядок в docs, frontend README) → завершить ребрендинг
- W3: Нет документации для конечных пользователей → Getting Started guides
- W4: Нет видео-контента → скринкасты и конференции
- W5: Auth/CSRF не завершены → приоритет в roadmap
- W6: Нет code splitting (frontend) → lazy loading
- W7: Нет виртуализации больших списков → react-window
- W8: Laravel adapter недостаточно протестирован → E2E fixtures
- W9: Нет plugin/extension API → архитектурная задача
- W10: Только self-hosted → рассмотреть SaaS

### Opportunities (Возможности)
- O1: PHP-инструменты фрагментированы → занять позицию "единого решения"
- O2: Микросервисы — тренд → Multi-app debugging востребован
- O3: Полиглот-проекты → Language-agnostic API как преимущество
- O4: AI в dev tools → добавить AI-анализ логов/exceptions
- O5: IDE extensions market → VS Code/JetBrains плагины
- O6: Laravel-сообщество крупнейшее → таргетированный маркетинг
- O7: Open Source Sponsorship → монетизация

### Threats (Угрозы)
- T1: "Telescope достаточно" для Laravel-разработчиков
- T2: Symfony Profiler встроен в фреймворк
- T3: APM-инструменты расширяют dev-time функционал
- T4: Низкий interest к Yii глобально → важно позиционировать ADP как framework-agnostic (ребрендинг уже проведён)


---

## 6. Target Audience & Personas

### Persona 1: "Multi-Framework Developer" (фрилансер/агентство)
- **Профиль**: 5+ лет опыта, 2-3 фреймворка, 3-5 проектов
- **Боль**: разные debug-инструменты, переключение контекста
- **Сообщение**: *"Один инструмент для всех фреймворков"*
- **Канал**: YouTube, Habr, Reddit r/PHP, dev.to

### Persona 2: "Tech Lead" (агентство/продуктовая команда)
- **Профиль**: управляет командой 5-15, проекты на разных стеках
- **Боль**: стандартизация инструментов, onboarding
- **Сообщение**: *"Стандартизируйте debugging в команде"*
- **Канал**: конференции, LinkedIn, Packagist

### Persona 3: "Microservices Architect"
- **Профиль**: распределённые системы, PHP + другие языки
- **Боль**: нет единой картины отладки между сервисами
- **Сообщение**: *"Один debug panel для всех микросервисов"*
- **Канал**: GitHub, tech blogs, conference talks

### Persona 4: "Learning Developer" (junior)
- **Профиль**: 0-2 года, изучает фреймворк
- **Боль**: не понимает внутренности приложения
- **Сообщение**: *"Загляни внутрь. Пойми, как работает фреймворк."*
- **Канал**: YouTube tutorials, Stack Overflow

---

## 7. Marketing Strategy

### 7.1 Позиционирование

**Tagline**: *"The Universal PHP Debug Panel"*
**Субтагline**: *"One panel for Symfony, Laravel, Yii — and beyond"*

**Positioning statement:**
> ADP — единственная open-source платформа для отладки и инспекции PHP-приложений, которая работает с любым фреймворком. В отличие от Telescope (только Laravel) или Symfony Profiler (только Symfony), ADP предлагает единый, современный UI с 28 коллекторами, живой инспекцией приложения и поддержкой микросервисов.

### 7.2 Контент-стратегия

#### Tier 1: Awareness (узнаваемость)

| # | Контент | Формат | Канал | Приоритет |
|---|---------|--------|-------|-----------|
| 1 | "Why I switched from Telescope to ADP" | Blog post | dev.to, Habr, Medium | P0 |
| 2 | "ADP vs Telescope vs Clockwork: Honest comparison" | Blog post | dev.to, Reddit | P0 |
| 3 | "Debugging Laravel with ADP in 5 minutes" | Video (3-5 min) | YouTube | P0 |
| 4 | "One debug panel for all PHP frameworks" | Demo video | YouTube, Twitter/X | P0 |
| 5 | "ADP: X-ray your running app" | Short-form | Twitter/X, LinkedIn | P1 |
| 6 | Conference talk: "Framework-Agnostic Debugging" | Talk | PHP conferences | P1 |

#### Tier 2: Consideration (рассмотрение)

| # | Контент | Формат | Канал |
|---|---------|--------|-------|
| 1 | "Getting Started with ADP + Laravel" | Tutorial | docs, YouTube |
| 2 | "Getting Started with ADP + Symfony" | Tutorial | docs, YouTube |
| 3 | "Debugging microservices with ADP" | Deep dive | Blog, YouTube |
| 4 | "ADP Inspector: 20 things you didn't know about your app" | Listicle | Blog |
| 5 | "Debug PHP + Python in one panel" | Tutorial | Blog |

#### Tier 3: Adoption (внедрение)

| # | Контент | Формат | Канал |
|---|---------|--------|-------|
| 1 | Interactive playground/demo | Web app | adp.dev (гипотетически) |
| 2 | "5 ADP tricks for daily debugging" | Video series | YouTube |
| 3 | Official VS Code extension announcement | Blog | VS Code marketplace |
| 4 | "How we use ADP at [Company]" | Case study | Blog |

### 7.3 Каналы продвижения

| Канал | Аудитория | Действие | Метрика |
|-------|----------|----------|---------|
| **GitHub** | Open Source developers | README с GIF-demo, releases, discussions | Stars, forks |
| **Packagist** | PHP developers | Оптимизация описания, keywords | Downloads |
| **Reddit r/PHP** | PHP community | Posts, comments, AMAs | Upvotes, comments |
| **dev.to** | Global developers | Comparison articles, tutorials | Views, reactions |
| **Habr** | RU developers | Статьи, обзоры | Views, bookmarks |
| **YouTube** | Visual learners | Screencasts, tutorials | Views, subscribers |
| **Twitter/X** | Tech community | Feature previews, short demos | Impressions |
| **LinkedIn** | Tech leads, CTOs | Professional posts | Engagement |
| **PHP Conferences** | Active community | Talks, workshops | Leads, mentions |
| **Laravel News** | Laravel developers | Sponsored/editorial | Traffic |
| **Symfony Blog** | Symfony developers | Community contribution | Traffic |

### 7.4 Launch Campaigns

#### Campaign 1: "The Great Migration" (Laravel focus)

**Цель**: привлечь Laravel-разработчиков (крупнейшее PHP-сообщество)
**Формат**: серия из 3 статей + видео
1. "What ADP does that Telescope can't" — сравнение фич
2. "Migrate from Telescope to ADP in 10 minutes" — пошаговый гайд
3. "ADP Inspector: see your Laravel app like never before" — Inspector demo

#### Campaign 2: "Debug Everything" (polyglot focus)

**Цель**: привлечь polyglot-разработчиков и микросервисные команды
**Формат**: demo + blog post
1. PHP backend + Python ML service + Node.js API — все в одном ADP
2. Architecture diagram + setup guide

#### Campaign 3: "Framework Freedom" (agency focus)

**Цель**: агентства с проектами на разных фреймворках
**Формат**: case study + LinkedIn posts
1. "How [Agency] standardized debugging across 20 projects"
2. ROI calculator: время сэкономленное на onboarding


---

## 8. Internal Improvements & Recommendations

### 8.1 Критические улучшения (влияют на adoption)

| # | Улучшение | Модуль | Влияние на маркетинг | Приоритет |
|---|----------|--------|---------------------|-----------|
| 1 | **Getting Started docs** для каждого фреймворка | Docs | Без этого разработчик уйдёт через 2 минуты | P0 |
| 2 | **Interactive demo/playground** (публичный) | Infrastructure | "Try before install" — ключ к adoption | P0 |
| 3 | **GIF/видео demo на GitHub README** | Docs | GitHub README — главная точка входа | P0 |
| 4 | **Один composer require** для установки | Adapters | Сложная установка = потеря пользователей | P0 |
| 5 | **Zero-config defaults** | Adapters | "Установил — работает" без конфигов | P0 |

### 8.2 UX-улучшения (влияют на retention)

| # | Улучшение | Модуль | Описание |
|---|----------|--------|----------|
| 1 | **Onboarding wizard** | Frontend | При первом запуске показать ключевые фичи |
| 2 | **Code splitting / lazy loading** | Frontend | Ускорить первую загрузку SPA |
| 3 | **Виртуализация списков** (react-window) | Frontend | Не тормозить на 1000+ записях |
| 4 | **Keyboard shortcuts guide** | Frontend | Overlay с горячими клавишами (Shift+?) |
| 5 | **Tooltips на иконках** | Frontend | Не все иконки очевидны |
| 6 | **Empty states с подсказками** | Frontend | "Нет SQL-запросов? Возможно, ORM не подключен к ADP" |
| 7 | **Performance budget** | Frontend | Метрики LCP < 2s, TTI < 3s |

### 8.3 Технические улучшения (влияют на quality)

| # | Улучшение | Модуль | Описание |
|---|----------|--------|----------|
| 1 | **Завершить security hardening** | API | Auth, CSRF, postMessage validation |
| 2 | **Plugin/Extension API** | Kernel | Позволить сообществу создавать коллекторы |
| 3 | **Laravel adapter E2E tests** | Testing | Laravel adapter должен быть production-ready |
| 4 | **Exponential backoff для SSE** | Frontend | Graceful reconnection |
| 5 | **Error boundaries на каждом модуле** | Frontend | Ошибка в одном панели не ломает весь UI |
| 6 | **Accessibility audit** | Frontend | WCAG 2.1 AA compliance |
| 7 | **Bundle size monitoring** | Frontend | Alerting на рост бандла |

### 8.4 Стратегические инициативы

| # | Инициатива | Описание | Потенциал |
|---|-----------|----------|----------|
| 1 | **VS Code Extension** | ADP panel прямо в VS Code | Огромный охват, удобство |
| 2 | **JetBrains Plugin** | ADP panel в PhpStorm | Целевая аудитория PHP |
| 3 | **AI Log Analysis** | GPT/Claude анализирует exceptions и предлагает fix | Тренд 2025-2026, wow-factor |
| 4 | **Docker one-liner** | `docker run adp` — standalone ADP server | Мгновенный старт |
| 5 | **Cloud/SaaS версия** | Hosted ADP для команд | Монетизация, enterprise |
| 6 | **WordPress adapter** | Огромное сообщество WordPress | Масштабирование аудитории |
| 7 | **Node.js / Python native adapters** | Не только ingestion API, а полноценные адаптеры | Расширение за пределы PHP |

### 8.5 Завершение ребрендинга (остаточные следы Yii)

**Контекст**: ADP произошёл из Yii Debug, но ребрендинг уже проведён — org `app-dev-panel`, пакеты `app-dev-panel/*`, ядро не зависит от Yii. Ассоциация минимальна, но есть остатки, которые стоит устранить.

**Что осталось:**
- Документация (getting-started.md) — Yii идёт первым в списках (исторический порядок)
- Frontend README — содержит "Maintained by Yii Software", ссылки на Yii Forum
- Gii модуль — Yii-специфичное название, незнакомое Laravel/Symfony разработчикам
- Git history — видно происхождение из yiisoft/yii-debug

**Рекомендации:**
1. В документации и маркетинге ставить Laravel и Symfony первыми (по размеру сообщества)
2. Обновить frontend README — убрать Yii-специфичные ссылки
3. Рассмотреть переименование Gii → Code Generator (более универсальное название)
4. GitHub topics: `laravel`, `symfony`, `php-debugging`, `php-profiler`
5. Packagist keywords: `debug-panel`, `laravel-debug`, `symfony-debug`, `php-profiler`

---

## 9. Go-To-Market Plan

### Phase 1: Foundation (месяц 1-2)

| Неделя | Действие |
|--------|----------|
| 1-2 | Getting Started docs для Laravel + Symfony |
| 2-3 | GIF-demo для GitHub README |
| 3-4 | Interactive playground (public demo) |
| 4 | "ADP vs Telescope" blog post на dev.to + Reddit |
| 5-6 | YouTube screencast: "ADP in 5 minutes" |
| 7-8 | Packagist optimization + GitHub README rewrite |

### Phase 2: Growth (месяц 3-4)

| Неделя | Действие |
|--------|----------|
| 9-10 | "Debug Everything" campaign (polyglot demo) |
| 11-12 | Conference talk submission (PHP conferences) |
| 13-14 | VS Code extension MVP |
| 15-16 | Laravel News editorial/sponsorship |

### Phase 3: Scale (месяц 5-6)

| Неделя | Действие |
|--------|----------|
| 17-18 | Docker one-liner launch |
| 19-20 | AI Log Analysis feature (beta) |
| 21-22 | WordPress adapter (alpha) |
| 23-24 | Community showcase + contributors program |

---

## 10. KPIs & Metrics

### Awareness metrics

| Метрика | Текущее | Цель (6 мес) | Цель (12 мес) |
|---------|---------|-------------|--------------|
| GitHub Stars | ? | 1,000 | 5,000 |
| Packagist downloads/month | ? | 5,000 | 20,000 |
| Website unique visitors/month | ? | 10,000 | 50,000 |
| YouTube views (total) | 0 | 10,000 | 50,000 |

### Adoption metrics

| Метрика | Текущее | Цель (6 мес) | Цель (12 мес) |
|---------|---------|-------------|--------------|
| Active installations (est.) | ? | 1,000 | 5,000 |
| GitHub Issues (community) | ? | 50 | 200 |
| Contributors | ? | 10 | 30 |
| Laravel adapter installs | ? | 2,000 | 10,000 |
| Symfony adapter installs | ? | 1,000 | 5,000 |

### Engagement metrics

| Метрика | Цель |
|---------|------|
| Time to first debug entry | < 5 minutes |
| Getting Started completion rate | > 70% |
| Feature discovery (Inspector usage) | > 30% of users |
| Return usage (weekly active) | > 40% |

---

## Appendix A: Messaging Framework

### Elevator Pitch (30 секунд)

> ADP — это open-source debug panel для PHP, который работает с Laravel, Symfony, Yii и любым другим фреймворком. В отличие от Telescope или Symfony Profiler, которые привязаны к одному фреймворку, ADP даёт единый интерфейс для всех проектов. Плюс уникальные фичи: живая инспекция приложения, поддержка микросервисов и даже дебаг Python/Node-сервисов через language-agnostic API.

### One-liners для разных каналов

| Канал | Сообщение |
|-------|----------|
| GitHub README | "The Universal PHP Debug Panel — works with Laravel, Symfony, Yii, and any PSR-compatible framework" |
| Twitter/X | "Stop switching debug tools. ADP works with ANY PHP framework. 28 collectors, live inspector, microservice support. Free & open source." |
| Reddit r/PHP | "I built a framework-agnostic debug panel that works with Laravel, Symfony, and Yii. Here's why I think it's better than Telescope." |
| Habr | "ADP: единая debug-панель для всех PHP-фреймворков. 28 коллекторов, inspector, микросервисы" |
| LinkedIn | "Tired of learning different debug tools for each PHP framework? ADP provides one unified panel for your entire stack." |
| Conference abstract | "Framework-agnostic debugging: how we built a single debug panel for the entire PHP ecosystem" |

### Key Messages по персонам

| Persona | Primary Message | Secondary Message |
|---------|----------------|-------------------|
| Multi-framework dev | "One tool for all your projects" | "28 auto-collectors — install and forget" |
| Tech Lead | "Standardize debugging across your team" | "Same UI for Laravel, Symfony, Yii" |
| Microservices architect | "Debug all services from one panel" | "Language-agnostic: PHP, Python, Node" |
| Junior developer | "See inside your app" | "Learn how frameworks work with Inspector" |

---

## Appendix B: Quick Wins (можно сделать за 1-2 дня каждый)

1. **Добавить GIF-demo в GitHub README** — самый высокий ROI
2. **Оптимизировать Packagist description и keywords** — бесплатный трафик
3. **Написать "ADP vs Telescope" пост** — controversy = трафик
4. **Создать /examples директорию** — минимальные примеры для каждого фреймворка
5. **Добавить badges в README** (tests, coverage, downloads, license)
6. **Twitter/X account** — регулярные посты о фичах
7. **GitHub Discussions** — включить для community support
8. **CONTRIBUTING.md** — привлечь контрибьюторов
9. **Logo и branding** — профессиональный логотип для узнаваемости
10. **"Awesome ADP"** — список ресурсов, плагинов, примеров

