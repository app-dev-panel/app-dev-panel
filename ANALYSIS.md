# Анализ репозитория App-Dev-Panel

## Описание проекта

**App-Dev-Panel** — полнофункциональный инструмент отладки и разработки для приложений на **Yii Framework 3**.
Аналог Symfony Profiler / Laravel Telescope для экосистемы Yii 3.

Проект состоит из двух частей:
- **Backend** (PHP 8.2+) — API-сервер, интегрируемый с Yii-приложениями для сбора данных отладки, профилирования и инспекции
- **Frontend** (React/TypeScript) — веб-панель для визуализации и анализа состояния приложения

## Стек технологий

### Backend
- PHP 8.2+
- Yii Framework 3
- PSR-стандарты (HTTP, контейнеры, логирование, кеширование)
- Symfony Console, Var-Dumper
- Guzzle HTTP Client
- Gitonomy (Git-библиотека)

### Frontend
- React 18.3+
- TypeScript 5.5+
- Vite (сборка)
- Material-UI (MUI) 5+
- Redux Toolkit (состояние)
- React Router 6 (навигация)
- React Hook Form (формы)
- Swagger UI React (API-документация)
- Workbox (PWA/Service Worker)

### Инструменты разработки
- PHPStan, Psalm (статический анализ PHP)
- PHP-CS-Fixer (стиль кода)
- Vitest (тесты frontend)
- PHPUnit (тесты backend)
- ESLint + Prettier (форматирование)
- npm workspaces, Lerna (монорепо)
- Docker + docker-compose

## Структура проекта

```
/app-dev-panel/
├── /app/                              # PHP-приложение (демо + точка входа)
│   ├── /src/
│   │   ├── /Web/                      # Web-роуты и actions
│   │   ├── /Console/                  # CLI-команды
│   │   └── /Shared/                   # Общая логика
│   ├── /config/                       # Конфигурация
│   │   ├── /common/                   # Общая (DI, роуты, параметры)
│   │   ├── /web/                      # Web-специфичная
│   │   ├── /console/                  # Console-специфичная
│   │   └── /environments/             # Окружения (dev/test/prod)
│   └── /public/                       # Публичные файлы
│
├── /libs/                             # Библиотеки монорепо
│   ├── /Kernel/                       # Ядро отладчика
│   │   └── /src/
│   │       ├── Debugger.php           # Основной класс отладчика
│   │       ├── /Collector/            # Коллекторы данных
│   │       ├── /Storage/              # Интерфейсы хранилища
│   │       └── /Event/                # Классы событий
│   │
│   ├── /API/                          # HTTP API слой
│   │   └── /src/
│   │       ├── /Debug/                # Эндпоинты отладки
│   │       │   ├── /Controller/       # API-контроллеры
│   │       │   ├── /Middleware/        # Middleware запросов
│   │       │   └── /Repository/       # Репозитории данных
│   │       └── /Inspector/            # Эндпоинты инспекции
│   │
│   ├── /Cli/                          # CLI-интеграция
│   ├── /Adapter/Yiisoft/              # Адаптер для Yii Framework
│   │
│   └── /yii-dev-panel/               # Frontend монорепо
│       ├── /packages/
│       │   ├── /yii-dev-panel/        # Основное React SPA
│       │   │   └── /src/Module/
│       │   │       ├── /Debug/        # Модуль отладки
│       │   │       ├── /Inspector/    # Модуль инспекции
│       │   │       ├── /Gii/          # Модуль генерации кода
│       │   │       └── /OpenApi/      # Модуль API-документации
│       │   ├── /yii-dev-toolbar/      # Встраиваемая панель
│       │   └── /yii-dev-panel-sdk/    # Общий SDK
│       └── docker-compose.yml
```

## Основные модули

### Debug Module — Отладка запросов
- История запросов и их повтор
- 12+ коллекторов данных:
  - LogCollector — логи
  - EventCollector — события
  - ServiceCollector — вызовы DI-контейнера
  - ValidatorCollector — валидация
  - RequestCollector — информация о запросе
  - RouterCollector — маршрутизация
  - MiddlewareCollector — middleware
  - AssetCollector — ассеты
  - ExceptionCollector — исключения
  - HttpClientCollector — HTTP-клиент
  - TimelineCollector — таймлайн
  - VarDumperCollector — дампы переменных

### Inspector Module — Инспекция приложения
- Маршруты, группы, middleware, actions
- Параметры и конфигурация приложения
- DI-контейнер: сервисы, теги, определения
- Обозреватель файлов и исходного кода
- Переводы (каталоги переводов)
- Выполнение команд (PHPUnit, Codeception)
- Схема базы данных (Yii DB / Cycle ORM)
- Управление Git (статус, ветки, checkout)
- PHP Info
- Composer-пакеты

### Gii Module — Генерация кода
- Генераторы (контроллеры и др.)
- Пошаговый UI генерации

### OpenAPI Module — API-документация
- Просмотр документации через Swagger UI

## Архитектурные решения

### Backend
- **Collector Pattern** — модульные сборщики данных с единым интерфейсом
- **Storage Abstraction** — подключаемое хранилище через интерфейсы
- **Proxy Pattern** — интеграция с фреймворком через прокси-объекты
- **Repository Pattern** — абстракция доступа к данным
- **Event-Driven** — отладчик подключается к событиям фреймворка
- **Server-Sent Events (SSE)** — обновления в реальном времени

### Frontend
- **Монорепо с Workspaces** — общий SDK для переиспользования
- **Redux Toolkit** — централизованное управление состоянием
- **Module Federation** — возможность подключения удалённых панелей
- **PWA** — оффлайн-поддержка через Service Workers
- **Feature Modules** — самодостаточные модули (Debug, Inspector, Gii, OpenAPI)
- **Redux State Sync** — синхронизация состояния между окнами браузера
