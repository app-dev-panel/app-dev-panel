---
title: Участие в разработке
---

# Участие в разработке

ADP — это монорепозиторий, содержащий PHP-библиотеки бэкенда и React/TypeScript фронтенд. Это руководство описывает настройку среды разработки, соглашения о коде и добавление новых компонентов.

## Требования

- PHP 8.4+
- Node.js 18+ и npm
- Composer

## Установка

```bash
make install              # Установить ВСЕ зависимости (PHP + фронтенд + playground)
```

Или выборочно:

```bash
make install-php          # Composer install (корень)
make install-frontend     # npm install (libs/frontend)
make install-playgrounds  # Composer install для каждого playground
```

## Запуск тестов

```bash
make test                 # Запустить ВСЕ тесты параллельно (PHP + фронтенд)
make test-php             # PHP юнит-тесты (PHPUnit)
make test-frontend        # Фронтенд юнит-тесты (Vitest)
make test-frontend-e2e    # Браузерные тесты фронтенда (требуется Chrome)
```

Для отчётов покрытия PHP установите расширение PCOV:

```bash
pecl install pcov
php vendor/bin/phpunit --coverage-text
```

## Качество кода

ADP использует [Mago](https://mago.carthage.software/) для PHP (форматирование, линтинг, статический анализ) и Prettier + ESLint для TypeScript.

```bash
make check                # Запустить ВСЕ проверки качества кода
make fix                  # Исправить все автоисправляемые проблемы

# Только PHP
make mago                 # Проверка форматирования + линтинг + анализ
make mago-fix             # Исправить форматирование, затем линтинг + анализ

# Только фронтенд
make frontend-check       # Prettier + ESLint
make frontend-fix         # Исправить проблемы фронтенда
```

### Baseline Mago

Существующие проблемы линтинга в legacy-коде подавлены через baseline-файл. У анализатора нет baseline — правила, дающие ложные срабатывания, подавлены через `ignore` в `mago.toml`. Новый код не должен создавать новых проблем.

```bash
composer lint:baseline    # Перегенерировать lint baseline после исправления существующих проблем
```

## Стиль кода

### PHP

- **PER-CS (PER-2)** через [Mago](https://mago.carthage.software/)
- `declare(strict_types=1)` в каждом файле
- `final class` по умолчанию
- PSR-интерфейсы для всех абстракций

### TypeScript

- **Prettier 3.8+**: одинарные кавычки, trailing commas, 120 символов в строке, 4 пробела, `objectWrap: "collapse"`
- **ESLint 9** с `@typescript-eslint`
- `type` вместо `interface` (`consistent-type-definitions: "type"`)
- Функциональные React-компоненты, паттерны Redux Toolkit

## Зависимости модулей

Строгие правила зависимостей обеспечивают фреймворко-независимость:

```
Adapter → API → Kernel
  │               ↑
  └───────────────┘
Cli → Kernel
Frontend → API (только HTTP)
```

| Модуль | Может зависеть от | Не может зависеть от |
|--------|-------------------|---------------------|
| Kernel | PSR-интерфейсы, общие PHP-библиотеки | API, Cli, Adapter |
| API | Kernel, PSR-интерфейсы | Adapter, Cli |
| Cli | Kernel, Symfony Console | API, Adapter |
| Adapter | Kernel, API, пакеты фреймворка | Другие адаптеры |

## Соглашения о тестировании

- Один тестовый класс на исходный класс: `src/Foo/Bar.php` → `tests/Unit/Foo/BarTest.php`
- Только inline-моки (`$this->createMock()`, анонимные классы)
- Без общих тестовых утилит, без классов тестового окружения
- `assertSame()` вместо `assertEquals()`
- Data providers через атрибут `#[DataProvider('name')]`
- Коллекторы наследуют `AbstractCollectorTestCase`

## Рабочий процесс

1. Создайте feature-ветку
2. Напишите код и тесты для ваших изменений
3. Запустите проверки: `make fix && make test`
4. Убедитесь, что всё работает: `make all` (проверки + тесты)
5. Отправьте pull request

Все проверки должны пройти перед мержем.

## Добавление коллектора

1. Создайте класс, реализующий <class>AppDevPanel\Kernel\Collector\CollectorInterface</class>, в `libs/Kernel/src/Collector/`
2. Реализуйте `startup()`, `shutdown()`, `getCollected()`
3. Опционально реализуйте <class>AppDevPanel\Kernel\Collector\SummaryCollectorInterface</class> для метаданных в списке записей
4. Напишите тест, наследующий `AbstractCollectorTestCase`
5. Зарегистрируйте в конфигах адаптера (напр., `libs/Adapter/Yiisoft/config/params.php`)

См. [Коллекторы](/ru/guide/collectors) для описания контракта интерфейса.

## Добавление страницы инспектора

### Бэкенд

1. Создайте контроллер в `libs/API/src/Inspector/Controller/`
2. Добавьте маршрут в `libs/API/config/routes.php`
3. Напишите тест контроллера, наследующий `ControllerTestCase`

### Фронтенд

1. Создайте компонент страницы в `packages/panel/src/Module/Inspector/Pages/`
2. Добавьте RTK Query эндпоинт в `packages/panel/src/Module/Inspector/API/`
3. Добавьте маршрут в конфигурацию маршрутов модуля инспектора

## Структура проекта

| Директория | Содержимое |
|------------|------------|
| `libs/Kernel/` | Ядро: debugger, коллекторы, хранилище, прокси |
| `libs/API/` | HTTP API: REST-эндпоинты, SSE, middleware |
| `libs/McpServer/` | MCP-сервер для интеграции с AI-ассистентами |
| `libs/Cli/` | CLI-команды |
| `libs/Adapter/` | Адаптеры фреймворков (Yii 3, Symfony, Laravel, Yii 2, Cycle) |
| `libs/frontend/` | React-фронтенд (панель, тулбар, SDK-пакеты) |
| `playground/` | Демо-приложения для каждого фреймворка |

## CI-пайплайн

GitHub Actions запускается на каждом push и PR:

- PHP-тесты на PHP 8.4 и 8.5 (Linux + Windows)
- Mago: формат, линтинг и статический анализ
- Проверки и тесты фронтенда
- Отчёты покрытия публикуются как комментарии к PR

Запуск полного CI-пайплайна локально:

```bash
make ci
```
