---
title: Участие в разработке
---

# Участие в разработке

ADP -- монорепозиторий, содержащий PHP-библиотеки бэкенда и React/TypeScript фронтенд. Это руководство описывает настройку среды разработки и запуск проверок.

## Требования

- PHP 8.4+
- Node.js 18+ и npm
- Composer

## Установка

```bash
make install              # Установить ВСЕ зависимости (PHP + фронтенд + playground)
```

Или установить выборочно:

```bash
make install-php          # Composer install (корень)
make install-frontend     # npm install (libs/frontend)
make install-playgrounds  # Composer install для каждого playground-приложения
```

## Запуск тестов

```bash
make test                 # Запустить ВСЕ тесты параллельно (PHP + фронтенд)
make test-php             # PHP юнит-тесты (PHPUnit)
make test-frontend        # Фронтенд юнит-тесты (Vitest)
make test-frontend-e2e    # Браузерные тесты фронтенда (требуется Chrome)
```

Для отчётов о покрытии PHP установите расширение PCOV:

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
make mago                 # Проверка форматирования + линт + анализ
make mago-fix             # Исправить форматирование, затем линт + анализ

# Только фронтенд
make frontend-check       # Prettier + ESLint
make frontend-fix         # Исправить проблемы фронтенда
```

## Рабочий процесс

1. **Напишите код** и тесты для ваших изменений
2. **Запустите проверки**: `make fix && make test`
3. **Проверьте всё**: `make all` (проверки + тесты вместе)

Все проверки должны пройти перед отправкой pull request.

## Структура проекта

| Директория | Содержимое |
|------------|------------|
| `libs/Kernel/` | Ядро: отладчик, коллекторы, хранилище, прокси |
| `libs/API/` | HTTP API: REST-эндпоинты, SSE, middleware |
| `libs/McpServer/` | MCP-сервер для интеграции с AI-ассистентами |
| `libs/Cli/` | CLI-команды |
| `libs/Adapter/` | Адаптеры фреймворков (Yii 3, Symfony, Laravel, Yii 2, Cycle) |
| `libs/frontend/` | React-фронтенд (панель, тулбар, SDK-пакеты) |
| `playground/` | Демо-приложения для каждого фреймворка |

## CI-конвейер

GitHub Actions запускается при каждом push и PR:

- PHP-тесты на PHP 8.4 и 8.5 (Linux + Windows)
- Mago: форматирование, линтинг, статический анализ
- Проверки и тесты фронтенда
- Отчёты о покрытии публикуются как комментарии к PR

Запуск полного CI-конвейера локально:

```bash
make ci
```
