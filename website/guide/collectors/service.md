---
title: Service Collector
description: "ADP Service Collector records DI container service resolutions with class names, tags, and dependencies."
---

# Service Collector

Captures DI container service method calls — invoked service, method, arguments, result, and timing.

![Service Collector panel](/images/collectors/service.png)

## What It Captures

| Field | Description |
|-------|-------------|
| `service` | Service identifier |
| `class` | Service class name |
| `method` | Method called |
| `arguments` | Method arguments |
| `result` | Return value |
| `status` | Call status (`success` or `error`) |
| `error` | Error message if failed |
| `timeStart` | Call start time |
| `timeEnd` | Call end time |

## Data Schema

```json
[
    {
        "service": "App\\Service\\UserService",
        "class": "App\\Service\\UserService",
        "method": "findById",
        "arguments": [42],
        "result": {"id": 42, "name": "John"},
        "status": "success",
        "error": null,
        "timeStart": 1711878000.100,
        "timeEnd": 1711878000.105
    }
]
```

**Summary** (shown in debug entry list):

```json
{
    "service": {
        "total": 5
    }
}
```

## Contract

```php
use AppDevPanel\Kernel\Collector\ServiceCollector;
use AppDevPanel\Kernel\Event\MethodCallRecord;

$collector->collect(new MethodCallRecord(
    service: 'App\\Service\\UserService',
    class: 'App\\Service\\UserService',
    method: 'findById',
    arguments: [42],
    result: $result,
    status: 'success',
    timeStart: $start,
    timeEnd: $end,
));
```

::: info
<class>\AppDevPanel\Kernel\Collector\ServiceCollector</class> implements <class>\AppDevPanel\Kernel\Collector\SummaryCollectorInterface</class> and depends on <class>\AppDevPanel\Kernel\Collector\TimelineCollector</class>.
:::

## How It Works

The collector is fed by <class>\AppDevPanel\Adapter\Yii3\Proxy\ContainerInterfaceProxy</class> which wraps the PSR-11 <class>Psr\Container\ContainerInterface</class>. When services are resolved and their methods are called through the proxy, the calls are intercepted and recorded.

## Debug Panel

- **Service call list** — all tracked method calls with class, method, and timing
- **Expandable details** — arguments and return values
- **Status indicators** — success (green) and error (red) badges
