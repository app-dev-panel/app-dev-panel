---
title: Архитектура
description: "Многослойная архитектура ADP: Kernel, API, адаптеры и фронтенд. Как данные проходят от приложения к панели."
---

# Архитектура

ADP следует строгой многослойной архитектуре, где каждый слой имеет чёткие обязанности, а зависимости идут в одном направлении.

## Слои

### 1. Kernel (Ядро)

Основной движок. **Независим от фреймворков** — зависит только от PSR-интерфейсов и общих PHP-библиотек. Управляет:

- **Debugger** — Жизненный цикл (старт, сбор, сброс)
- **Collectors** — Сбор данных через <class>AppDevPanel\Kernel\Collector\CollectorInterface</class>
- **Storage** — Сохранение данных отладки (JSON-файлы по умолчанию) через <class>AppDevPanel\Kernel\Storage\StorageInterface</class>
- **Proxies** — Прозрачный перехват PSR-интерфейсов

### 2. API

HTTP-слой на PSR-7/15. Предоставляет:

- **REST-эндпоинты** — Получение записей отладки, данных коллекторов
- **SSE** — Уведомления в реальном времени о новых записях
- **Inspector** — Эндпоинты инспекции (конфигурация, маршруты, схема БД и др.)
- **MCP** — Интеграция с AI-ассистентами через Model Context Protocol
- **Ingestion** — Приём отладочных данных от внешних (не-PHP) приложений

### 3. Адаптеры

Мосты к фреймворкам. Каждый адаптер:

- Регистрирует прокси-сервисы в DI-контейнере фреймворка
- Связывает события жизненного цикла фреймворка с <class>AppDevPanel\Kernel\Debugger</class>`::startup()` / `::shutdown()`
- Настраивает коллекторы и хранилище с учётом особенностей фреймворка
- Регистрирует API-маршруты (`/debug/api/*`, `/inspect/api/*`)
- Отдаёт фронтенд панели отладки по маршруту `/debug`
- Реализует провайдеры инспектора для фреймворка (конфигурация, маршруты, схема БД)

### 4. Frontend

React 19 SPA:

- Дизайн-система Material-UI 5
- Redux Toolkit для управления состоянием
- Модульная система (Debug, Inspector, LLM, MCP, OpenAPI, Frames)

## Граф зависимостей

```
┌────────────────────────────────────────────────────────┐
│                  Направление зависимостей                │
│                                                         │
│   Adapter ──▶ API ──▶ Kernel                            │
│      │                   ▲                              │
│      └───────────────────┘                              │
│                                                         │
│   Cli ──▶ API ──▶ Kernel                                │
│                                                         │
│   Frontend ──▶ API (только через HTTP)                  │
└────────────────────────────────────────────────────────┘
```

- **Kernel** ни от чего не зависит (только PSR-интерфейсы)
- **API** зависит только от Kernel
- **Cli** зависит от Kernel и API
- **Adapter** зависит от Kernel, API и целевого фреймворка
- **Frontend** общается через HTTP — нет PHP-зависимостей

## Правила зависимостей

Основной принцип: **общие модули никогда не должны зависеть от кода, специфичного для фреймворка**.

| Модуль | Может зависеть от | Не может зависеть от |
|--------|-------------------|---------------------|
| **Kernel** | Только PSR-интерфейсы | API, Cli, Adapter, любой фреймворк |
| **API** | Kernel, PSR-интерфейсы | Adapter, любой фреймворк |
| **Cli** | Kernel, API, Symfony Console | Adapter, любой фреймворк |
| **Adapter** | Kernel, API, Cli, пакеты фреймворка | Другие адаптеры |
| **Frontend** | Ничего (только HTTP) | Любой PHP-пакет |

::: warning
Адаптеры не должны зависеть от других адаптеров. Каждый адаптер — независимый мост между Kernel и конкретным фреймворком.
:::

## Абстракции

Хранилище и сериализация остаются за интерфейсами для обеспечения подключаемости:

| Аспект | Абстракция | Реализации |
|--------|------------|------------|
| Хранилище данных | <class>AppDevPanel\Kernel\Storage\StorageInterface</class> | <class>AppDevPanel\Kernel\Storage\FileStorage</class>, <class>AppDevPanel\Kernel\Storage\MemoryStorage</class> |
| Сериализация объектов | <class>AppDevPanel\Kernel\Dumper</class> | На основе JSON (встроенная) |
| Инспекция БД | <class>AppDevPanel\Api\Inspector\Database\SchemaProviderInterface</class> | По адаптерам: <class>AppDevPanel\Adapter\Yii3\Inspector\DbSchemaProvider</class>, <class>AppDevPanel\Adapter\Symfony\Inspector\DoctrineSchemaProvider</class>, <class>AppDevPanel\Adapter\Laravel\Inspector\LaravelSchemaProvider</class>, <class>AppDevPanel\Adapter\Yii2\Inspector\NullSchemaProvider</class>, <class>AppDevPanel\Adapter\Cycle\Inspector\CycleSchemaProvider</class> |

## Поток данных

1. Приложение запускается с установленным адаптером
2. Адаптер регистрирует прокси, перехватывающие PSR-интерфейсы
3. Прокси передают данные коллекторам
4. По завершении запроса Debugger сбрасывает данные коллекторов в хранилище
5. API отдаёт сохранённые данные; SSE уведомляет фронтенд
6. Фронтенд рендерит данные

См. [Поток данных](/ru/guide/data-flow) для полного описания жизненного цикла.

## Модульная система фронтенда

Фронтенд использует модульную систему, где каждый модуль реализует `ModuleInterface`:

```typescript
interface ModuleInterface {
    routes: RouteObject[];
    reducers: Record<string, Reducer>;
    middlewares: Middleware[];
    standalone: boolean;
}
```

Текущие модули: Debug, Inspector, LLM, MCP, OpenAPI, Frames.

## Создание нового адаптера

При создании адаптера для нового фреймворка:

1. Создайте `libs/Adapter/<FrameworkName>/`
2. Адаптер **должен** зависеть от <pkg>app-dev-panel/kernel</pkg>
3. Адаптер **может** зависеть от <pkg>app-dev-panel/api</pkg> (для маршрутов и инспектора)
4. Адаптер **может** зависеть от <pkg>app-dev-panel/cli</pkg> (для CLI-команд)
5. Адаптер **не должен** зависеть от других адаптеров
6. Адаптер **не должен** модифицировать код Kernel или API — только подключаться через конфигурацию

### Обязанности адаптера

| Обязанность | Описание |
|-------------|----------|
| Маппинг жизненного цикла | Связать события фреймворка → <class>AppDevPanel\Kernel\Debugger</class>`::startup()` / `::shutdown()` |
| Подключение прокси | Зарегистрировать PSR-прокси Kernel как декораторы сервисов в DI фреймворка |
| Фреймворк-специфичные прокси | Создать прокси для не-PSR API (напр., <class>AppDevPanel\Adapter\Symfony\Proxy\SymfonyEventDispatcherProxy</class>) |
| Конфигурация коллекторов | Настроить активные коллекторы и передать параметры фреймворка |
| Настройка хранилища | Подключить <class>AppDevPanel\Kernel\Storage\StorageInterface</class> с путями фреймворка |
| Регистрация маршрутов | Зарегистрировать API-маршруты для `/debug/api/*`, `/inspect/api/*` и отдавать фронтенд по `/debug` |
| Провайдеры инспектора | Реализовать <class>AppDevPanel\Api\Inspector\Database\SchemaProviderInterface</class>, <class>AppDevPanel\Api\Inspector\Elasticsearch\ElasticsearchProviderInterface</class> и др. |

### Эталонные реализации

| Адаптер | Фреймворк | Паттерн |
|---------|-----------|---------|
| Symfony | Symfony 6.4–8.x | Bundle + Extension + CompilerPass |
| Yii2 | Yii 2 | Module + BootstrapInterface |
| Yii 3 | Yii 3 | Config plugin + ServiceProvider |
| Laravel | Laravel 11.x–13.x | ServiceProvider (register + boot) |
| Spiral | Spiral 3.14+ | Bootloader + PSR-15 middleware |

### Минимальный чеклист

1. `composer.json` с зависимостями <pkg>app-dev-panel/kernel</pkg> + <pkg>app-dev-panel/api</pkg>
2. Маппинг событий жизненного цикла → <class>AppDevPanel\Kernel\Debugger</class>`::startup()` / `::shutdown()`
3. Регистрация PSR-прокси Kernel как декораторов сервисов (логгер, события, HTTP-клиент)
4. Подключение <class>AppDevPanel\Kernel\Storage\FileStorage</class> с путём, подходящим для фреймворка
5. Регистрация маршрутов API-контроллеров
6. Создание [playground-приложения](/ru/guide/playgrounds) для тестирования и демо
