---
title: Playground-приложения
description: "Демо-приложения ADP для Symfony, Laravel, Yii 3, Yii 2 и Spiral. Используйте как эталонные примеры интеграции."
---

# Playground-приложения

Playground-приложения — это минимальные рабочие приложения, демонстрирующие интеграцию ADP с конкретными PHP-фреймворками. Каждый playground устанавливает соответствующий адаптер ADP, настраивает коллекторы и предоставляет демо-эндпоинты для генерации отладочных данных.

## Доступные playground-приложения

| Playground | Фреймворк | Порт | Адаптер |
|------------|-----------|------|---------|
| `yii3-app` | Yii 3 | 8101 | <pkg>app-dev-panel/adapter-yii3</pkg> |
| `symfony-app` | Symfony 7 | 8102 | <pkg>app-dev-panel/adapter-symfony</pkg> |
| `yii2-basic-app` | Yii 2 | 8103 | <pkg>app-dev-panel/adapter-yii2</pkg> |
| `laravel-app` | Laravel 12 | 8104 | <pkg>app-dev-panel/adapter-laravel</pkg> |
| `spiral-app` | Spiral 3 | 8105 | <pkg>app-dev-panel/adapter-spiral</pkg> |

## Запуск playground-приложений

### Установка зависимостей

```bash
make install-playgrounds
```

### Запуск серверов

Каждый playground работает на своём порту. Запускайте в отдельных терминалах:

:::tabs key:framework
== Symfony
```bash
cd playground/symfony-app && PHP_CLI_SERVER_WORKERS=3 php -S 127.0.0.1:8102 -t public
```
== Yii 2
```bash
cd playground/yii2-basic-app && PHP_CLI_SERVER_WORKERS=3 php -S 127.0.0.1:8103 -t public
```
== Yii 3
```bash
cd playground/yii3-app && ./yii serve --port=8101
```
== Laravel
```bash
cd playground/laravel-app && PHP_CLI_SERVER_WORKERS=3 php -S 127.0.0.1:8104 -t public
```
== Spiral
```bash
make serve-spiral
# (или: cd playground/spiral-app && PHP_CLI_SERVER_WORKERS=3 php -S 127.0.0.1:8105 -t public)
```
:::

::: tip
`PHP_CLI_SERVER_WORKERS=3` необходим для SSE — один воркер обрабатывает SSE-поток, остальные обслуживают API-запросы.
:::

## Общие URL

Все playground-приложения предоставляют одинаковую структуру URL:

| Путь | Назначение |
|------|------------|
| `/` | Главная / демо-страница |
| `/debug/api/` | Список отладочных записей (JSON) |
| `/debug/api/view/{id}` | Полные данные отладочной записи |
| `/debug/api/summary/{id}` | Сводка записи |
| `/inspect/api/*` | Эндпоинты инспектора |
| `/test/fixtures/*` | Тестовые fixture-эндпоинты |

## Методы интеграции

Каждый фреймворк использует свой подход к регистрации адаптера:

| Фреймворк | Интеграция | Регистрация |
|-----------|------------|-------------|
| Yii 3 | Config plugin | Автоматически через `yiisoft/config` |
| Symfony | Bundle | Вручную в `config/bundles.php` (только dev/test) |
| Laravel | Package discovery | Автоматически через `extra.laravel.providers` |
| Yii 2 | Module + Bootstrap | Авто-bootstrap через `extra.bootstrap` в composer |
| Spiral | Bootloader + PSR-15 middleware | Вручную `defineBootloaders()` + HTTP-конвейер |

### Пути хранилищ

| Фреймворк | Путь | Разрешение |
|-----------|------|------------|
| Yii 3 | `runtime/debug/` | Алиас `@runtime` |
| Symfony | `var/debug/` | `%kernel.project_dir%` |
| Laravel | `storage/debug/` | `storage_path('debug')` |
| Yii2 | `runtime/debug/` | Алиас `@runtime` |
| Spiral | `sys_get_temp_dir()/app-dev-panel` | переопределяется через `APP_DEV_PANEL_STORAGE_PATH` |

## Запуск тестовых fixture

Fixture — это автоматизированные тестовые эндпоинты, проверяющие каждый коллектор:

```bash
make fixtures              # Все playground параллельно
make fixtures-yii3         # Только Yii 3
make fixtures-symfony      # Только Symfony
make fixtures-yii2         # Только Yii2
make fixtures-laravel      # Только Laravel
make fixtures-spiral       # Только Spiral
```

Для PHPUnit E2E-тестов (требуются запущенные серверы):

```bash
make test-fixtures         # Все playground
make test-fixtures-yii3    # Только Yii 3
```

## Добавление нового playground

Чтобы добавить playground для нового фреймворка:

1. Создайте `playground/<framework>-app/` с минимальным приложением на основе официального скелетона фреймворка
2. Установите пакеты ADP через path-репозитории:

```json
{
    "repositories": [
        {"type": "path", "url": "../../libs/Kernel"},
        {"type": "path", "url": "../../libs/API"},
        {"type": "path", "url": "../../libs/Adapter/<Framework>"}
    ],
    "require": {
        "app-dev-panel/adapter-<framework>": "*"
    }
}
```

3. Настройте коллекторы, хранилище и API-маршруты согласно документации адаптера
4. Реализуйте эндпоинты `/test/fixtures/*`, соответствующие `FixtureRegistry` (см. [Участие в разработке](/ru/guide/contributing))
5. Добавьте цели в Makefile для serve, fixtures и проверок Mago
6. Назначьте следующий свободный порт (8106+)

### Распределение портов

| Порт | Назначение |
|------|------------|
| 8100 | Frontend dev-сервер |
| 8101 | Yii 3 |
| 8102 | Symfony |
| 8103 | Yii2 |
| 8104 | Laravel |
| 8105 | Spiral |
| 8106+ | Свободны |
