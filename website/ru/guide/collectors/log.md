---
title: Коллектор логов
---

# Коллектор логов

Собирает PSR-3 сообщения логов, записанные во время запроса или консольной команды — уровень, сообщение, контекст и место вызова.

![Панель коллектора логов](/images/collectors/log.png)

## Что собирает

| Поле | Описание |
|------|----------|
| `time` | Временная метка записи лога |
| `level` | Уровень лога PSR-3 (`debug`, `info`, `warning`, `error` и т.д.) |
| `message` | Сообщение лога (строка или stringable) |
| `context` | Массив контекстных данных, переданных с вызовом лога |
| `line` | Исходный файл и строка, откуда был выполнен вызов лога |

## Схема данных

```json
[
    {
        "time": 1711878000.123,
        "level": "info",
        "message": "User logged in",
        "context": {"userId": 42},
        "line": "/app/src/AuthService.php:87"
    }
]
```

**Сводка** (отображается в списке отладочных записей):

```json
{
    "logger": {
        "total": 5
    }
}
```

## Контракт

```php
use AppDevPanel\Kernel\Collector\LogCollector;

$collector->collect(
    level: 'info',
    message: 'User logged in',
    context: ['userId' => 42],
    line: '/app/src/AuthService.php:87',
);
```

::: info
<class>\AppDevPanel\Kernel\Collector\LogCollector</class> реализует <class>\AppDevPanel\Kernel\Collector\SummaryCollectorInterface</class> и зависит от <class>\AppDevPanel\Kernel\Collector\TimelineCollector</class> для интеграции с кросс-коллекторной временной шкалой.
:::

## Как это работает

Коллектор получает данные от <class>\AppDevPanel\Kernel\Collector\LoggerInterfaceProxy</class> — декоратора PSR-3 <class>Psr\Log\LoggerInterface</class>. Когда прокси зарегистрирован как логгер приложения, каждый вызов `$logger->info(...)`, `$logger->error(...)` и т.д. автоматически перехватывается и передаётся коллектору.

Ручная настройка не требуется при использовании адаптера (Symfony, Laravel, Yii) — прокси регистрируется автоматически.

## Панель отладки

- **Фильтруемый список логов** — поиск по тексту сообщения или уровню лога
- **Цветовая кодировка уровней** — каждый уровень PSR-3 имеет свой цветной бейдж
- **Раскрываемые записи** — клик для просмотра полных контекстных данных и места вызова
- **Количество записей** — общее число записей лога отображается в бейдже боковой панели
