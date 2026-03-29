---
title: Building Custom Collectors for Your Domain
date: 2026-03-20
author: ADP Team
tags: [tutorial, collectors, php]
---

<script setup>
import BlogPost from '../.vitepress/theme/components/BlogPost.vue';
</script>

<BlogPost
  title="Building Custom Collectors for Your Domain"
  date="2026-03-20"
  author="ADP Team"
  :tags="['tutorial', 'collectors', 'php']"
  readingTime="8 min"
/>

ADP ships with collectors for logs, events, HTTP requests, database queries, and more. But every application has domain-specific data worth inspecting. Maybe you want to track payment gateway calls, monitor feature flag evaluations, or record cache warming statistics. Custom collectors let you bring that data into the ADP panel alongside everything else.

This tutorial walks through building a custom collector from scratch.

## How Collectors Work

A collector in ADP implements `CollectorInterface`. During a request (or console command), the collector accumulates data. When the debugger flushes at the end of the lifecycle, each collector's data is serialized and written to storage. The API then serves it to the frontend.

The lifecycle looks like this:

1. **Startup** — The debugger activates all registered collectors
2. **Collection** — Proxies and manual calls feed data into the collector
3. **Flush** — The debugger calls `collect()` to get the final payload
4. **Storage** — The payload is serialized to JSON and stored

## Step 1: Define the Collector Class

Let's build a collector that tracks payment gateway interactions. Create a class that implements `CollectorInterface`:

```php
<?php

declare(strict_types=1);

namespace App\Debug;

use ADP\Kernel\Collector\CollectorInterface;
use ADP\Kernel\Collector\SummaryCollectorInterface;

final class PaymentCollector implements CollectorInterface, SummaryCollectorInterface
{
    private array $transactions = [];

    public function getName(): string
    {
        return 'payment';
    }

    public function collect(): array
    {
        return [
            'transactions' => $this->transactions,
            'total' => count($this->transactions),
            'totalAmount' => array_sum(array_column($this->transactions, 'amount')),
        ];
    }

    public function getSummary(): array
    {
        return [
            'payment' => [
                'total' => count($this->transactions),
                'failed' => count(array_filter(
                    $this->transactions,
                    fn(array $tx) => $tx['status'] === 'failed',
                )),
            ],
        ];
    }

    public function recordTransaction(
        string $gateway,
        string $transactionId,
        float $amount,
        string $currency,
        string $status,
        float $duration,
    ): void {
        $this->transactions[] = [
            'gateway' => $gateway,
            'transactionId' => $transactionId,
            'amount' => $amount,
            'currency' => $currency,
            'status' => $status,
            'duration' => $duration,
            'timestamp' => microtime(true),
        ];
    }
}
```

The `getName()` method returns a unique identifier used as the storage key. The `collect()` method returns the data payload that gets serialized. The optional `SummaryCollectorInterface` provides summary data shown in the debug toolbar.

## Step 2: Feed Data to the Collector

You have two options for feeding data into your collector: manual calls or a proxy wrapper.

### Option A: Manual Recording

Inject the collector and call it directly from your service:

```php
<?php

declare(strict_types=1);

namespace App\Service;

use App\Debug\PaymentCollector;

final class PaymentService
{
    public function __construct(
        private readonly PaymentGateway $gateway,
        private readonly PaymentCollector $collector,
    ) {}

    public function charge(string $customerId, float $amount, string $currency): PaymentResult
    {
        $start = microtime(true);

        $result = $this->gateway->charge($customerId, $amount, $currency);

        $this->collector->recordTransaction(
            gateway: 'stripe',
            transactionId: $result->id,
            amount: $amount,
            currency: $currency,
            status: $result->status,
            duration: microtime(true) - $start,
        );

        return $result;
    }
}
```

### Option B: Proxy Wrapper

For a cleaner separation, wrap your gateway in a proxy that records automatically:

```php
<?php

declare(strict_types=1);

namespace App\Debug;

use App\Service\PaymentGateway;
use App\Service\PaymentResult;

final class PaymentGatewayProxy extends PaymentGateway
{
    public function __construct(
        private readonly PaymentGateway $inner,
        private readonly PaymentCollector $collector,
    ) {}

    public function charge(string $customerId, float $amount, string $currency): PaymentResult
    {
        $start = microtime(true);
        $result = $this->inner->charge($customerId, $amount, $currency);

        $this->collector->recordTransaction(
            gateway: 'stripe',
            transactionId: $result->id,
            amount: $amount,
            currency: $currency,
            status: $result->status,
            duration: microtime(true) - $start,
        );

        return $result;
    }
}
```

The proxy approach follows the same pattern ADP uses internally for PSR interfaces. It keeps your business logic free of debugging concerns.

## Step 3: Register the Collector

Registration depends on your framework adapter.

**Symfony** — Tag the collector as an ADP collector in your services configuration:

```yaml
services:
    App\Debug\PaymentCollector:
        tags: ['adp.collector']
```

**Laravel** — Register in a service provider:

```php
$this->app->singleton(PaymentCollector::class);
$this->app->tag([PaymentCollector::class], 'adp.collectors');
```

**Yii 3** — Add to your DI configuration:

```php
return [
    PaymentCollector::class => PaymentCollector::class,
    'adp.collectors' => [
        PaymentCollector::class,
    ],
];
```

## Step 4: Verify in the Panel

After registering, restart your application and trigger a payment flow. Open the ADP panel and look for your collector's data in the debug entry. The summary data appears in the toolbar, and the full transaction list is available in the detail view.

## Tips for Writing Good Collectors

- **Keep payloads small.** Collectors run on every request. Avoid storing large objects or binary data. Summarize where possible.
- **Use timestamps.** Always record when events happened. This helps correlate data across collectors.
- **Implement SummaryCollectorInterface.** The summary appears in the toolbar and list view, giving a quick overview without opening the detail page.
- **Handle errors gracefully.** A collector should never throw exceptions that break the application. Wrap risky operations in try/catch.
- **Make it configurable.** Allow users to enable/disable your collector or set thresholds (e.g., only record transactions above a certain amount).

## Next Steps

With your custom collector in place, you get domain-specific visibility in the same panel where you inspect logs, queries, and HTTP calls. The ADP architecture makes this possible because collectors are simple, independent units with a clear contract.

In the next post, we will explore how ADP uses Server-Sent Events to push debug data to the panel in real time.
