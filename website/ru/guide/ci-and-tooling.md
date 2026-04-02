---
title: CI и инструменты
description: "CI-пайплайн ADP: GitHub Actions, Mago для качества PHP-кода, PHPUnit и Vitest для тестирования."
---

# CI и инструменты

ADP использует GitHub Actions для непрерывной интеграции и [Mago](https://mago.carthage.software/) для обеспечения качества PHP-кода.

## Mago — PHP-инструментарий

Mago — это инструментарий на Rust, заменяющий PHPStan, PHP-CS-Fixer и Psalm одним бинарником. Предоставляет три инструмента:

| Инструмент | Назначение | Команда |
|------------|-----------|---------|
| **Formatter** | Обеспечение стиля PSR-12 | `make mago-format` |
| **Linter** | Поиск code smell, несоответствий, антипаттернов | `make mago-lint` |
| **Analyzer** | Статический анализ: ошибки типов, null safety, логические баги | `make mago-analyze` |

### Запуск Mago

```bash
make mago                 # Все проверки (формат + линт + анализ)
make mago-fix             # Исправить форматирование, затем линт + анализ
make mago-playgrounds     # Проверить все playground-приложения
make mago-playgrounds-fix # Исправить форматирование в playground
```

### Конфигурация

Mago настраивается через `mago.toml` в корне проекта:

```toml
[source]
paths = ["libs/Kernel/src", "libs/API/src", ...]
includes = ["vendor"]     # Парсятся только для информации о типах

[formatter]
preset = "psr-12"

[linter]
default-level = "warning"
```

### Baseline

Существующие проблемы линтинга в legacy-коде подавлены через `mago-lint-baseline.php`. У анализатора нет baseline — правила, дающие ложные срабатывания, подавлены через `ignore` в `mago.toml`. Новый код не должен создавать новых проблем.

```bash
composer lint:baseline    # Перегенерировать lint baseline
```

## PHPUnit — тестирование

Тесты организованы по модулям с единым корневым `phpunit.xml.dist`:

```bash
make test-php             # Запустить все PHP-тесты
```

### Тестовые наборы

| Набор | Директория |
|-------|-----------|
| Kernel | `libs/Kernel/tests` |
| API | `libs/API/tests` |
| Cli | `libs/Cli/tests` |
| Adapter/Symfony | `libs/Adapter/Symfony/tests` |
| Adapter/Laravel | `libs/Adapter/Laravel/tests` |
| Adapter/Yii2 | `libs/Adapter/Yii2/tests` |
| Adapter/Cycle | `libs/Adapter/Cycle/tests` |
| McpServer | `libs/McpServer/tests` |

### Покрытие

Для покрытия требуется расширение PCOV:

```bash
pecl install pcov
php vendor/bin/phpunit --coverage-text          # Текстовая сводка
php vendor/bin/phpunit --coverage-html=coverage  # HTML-отчёт
```

## Проверки фронтенда

```bash
make frontend-check       # Prettier + ESLint
make frontend-fix         # Автоисправление проблем
make test-frontend        # Vitest юнит-тесты
make test-frontend-e2e    # Браузерные тесты (требуется Chrome)
```

## GitHub Actions

### CI Workflow (`ci.yml`)

Запускается на каждом push и PR.

**Матрица тестов:**

| ОС | PHP 8.4 | PHP 8.5 |
|----|:-------:|:-------:|
| Linux | ✅ | ✅ |
| Windows | ✅ | ✅ |

**Проверки Mago** запускаются как отдельные параллельные задачи:
- `mago fmt --check`
- `mago lint --reporting-format=github` (аннотирует файлы в PR)
- `mago analyze --reporting-format=github` (аннотирует файлы в PR)

### PR Report (`pr-report.yml`)

Запускается только на PR. Публикует два комментария:

1. **Отчёт покрытия** — Сводка покрытия кода от PHPUnit
2. **Отчёт Mago** — Статус pass/fail для формата, линта, анализа с раскрывающимся выводом при ошибках

## Полный пайплайн

Запуск полного CI-пайплайна локально:

```bash
make ci                   # Полный CI: все проверки + все тесты
make check                # Только проверки (Mago + фронтенд)
make test                 # Только тесты (PHP + фронтенд)
make all                  # То же, что make check && make test
```

## Добавление новой библиотеки

При добавлении новой библиотеки в `libs/`:

1. Добавьте пути `src/` и `tests/` в `mago.toml` под `[source] paths`
2. Добавьте директорию тестов в `phpunit.xml.dist` как новый `<testsuite>`
3. Добавьте директорию `src/` в секцию `<source><include>` в `phpunit.xml.dist`
