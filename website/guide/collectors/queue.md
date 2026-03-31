---
title: Queue Collector
---

# Queue Collector

Captures message queue operations — dispatched messages, processing status, failures, and duplicate detection.

![Queue Collector panel](/images/collectors/queue.png)

## What It Captures

| Field | Description |
|-------|-------------|
| `pushes` | Messages pushed to queues, grouped by queue name |
| `statuses` | Message status updates (ID and status) |
| `processingMessages` | Messages currently being processed |
| `messages` | Dispatched/handled messages with metadata |
| `messageCount` | Total message count |
| `failedCount` | Number of failed messages |

## Data Schema

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

**Summary** (shown in debug entry list):

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

## Contract

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
`QueueCollector` implements `SummaryCollectorInterface`, depends on `TimelineCollector`, and uses `DuplicateDetectionTrait`.
:::

## How It Works

Framework adapters intercept message bus/queue operations:
- **Symfony**: Messenger middleware and event listeners
- **Laravel**: Queue event listeners (`JobProcessing`, `JobProcessed`, `JobFailed`)
- **Yii 3**: Queue proxy decorator

## Debug Panel

- **Message list** — all dispatched and handled messages with status
- **Queue grouping** — messages grouped by queue name
- **Status badges** — dispatched (blue), handled (green), failed (red)
- **Duplicate detection** — highlights repeated identical messages
