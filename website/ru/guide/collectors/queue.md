---
title: Коллектор очередей
---

# Коллектор очередей

Захватывает операции с очередями сообщений — отправленные сообщения, статус обработки, ошибки и обнаружение дубликатов.

![Панель коллектора очередей](/images/collectors/queue.png)

## Собираемые данные

| Поле | Описание |
|------|----------|
| `pushes` | Сообщения, отправленные в очереди, сгруппированные по имени очереди |
| `statuses` | Обновления статуса сообщений (идентификатор и статус) |
| `processingMessages` | Сообщения, находящиеся в обработке |
| `messages` | Отправленные/обработанные сообщения с метаданными |
| `messageCount` | Общее количество сообщений |
| `failedCount` | Количество сообщений с ошибками |

## Схема данных

```json
{
    "pushes": {
        "default": [...]
    },
    "statuses": [
        {"id": "msg-1", "status": "handled"}
    ],
    "processingMessages": {},
    "messages": [...],
    "messageCount": 3,
    "failedCount": 0,
    "duplicates": {
        "groups": [],
        "totalDuplicatedCount": 0
    }
}
```

**Сводка** (отображается в списке отладочных записей):

```json
{
    "queue": {
        "countPushes": 2,
        "countStatuses": 1,
        "countProcessingMessages": 0,
        "messageCount": 3,
        "failedCount": 0,
        "duplicateGroups": 0,
        "totalDuplicatedCount": 0
    }
}
```

## Контракт

```php
use AppDevPanel\Kernel\Collector\QueueCollector;
use AppDevPanel\Kernel\Collector\MessageRecord;

// Log a dispatched/handled message
$collector->logMessage(new MessageRecord(
    class: 'App\\Message\\SendNotification',
    status: 'dispatched',
    queue: 'default',
    handlerClass: 'App\\Handler\\NotificationHandler',
));

// Or use individual methods
$collector->collectPush(queueName: 'default', message: $message);
$collector->collectStatus(id: 'msg-1', status: 'handled');
```

::: info
<class>\AppDevPanel\Kernel\Collector\QueueCollector</class> реализует <class>\AppDevPanel\Kernel\Collector\SummaryCollectorInterface</class>, зависит от <class>\AppDevPanel\Kernel\Collector\TimelineCollector</class> и использует <class>\AppDevPanel\Kernel\Collector\DuplicateDetectionTrait</class>.
:::

## Как это работает

Адаптеры фреймворков перехватывают операции шины сообщений/очереди:
- **Symfony**: middleware и слушатели событий Messenger
- **Laravel**: слушатели событий очереди (`JobProcessing`, `JobProcessed`, `JobFailed`)
- **Yii 3**: прокси-декоратор очереди

## Панель отладки

- **Список сообщений** — все отправленные и обработанные сообщения со статусом
- **Группировка по очередям** — сообщения сгруппированы по имени очереди
- **Бейджи статусов** — отправлено (синий), обработано (зелёный), ошибка (красный)
- **Обнаружение дубликатов** — подсветка повторяющихся идентичных сообщений
