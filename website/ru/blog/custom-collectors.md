---
title: Создание пользовательских коллекторов для вашего домена
date: 2026-03-20
author: ADP Team
tags: [tutorial, collectors, php]
---

<script setup>
import BlogPost from '../../.vitepress/theme/components/BlogPost.vue';
</script>

<BlogPost
  title="Создание пользовательских коллекторов для вашего домена"
  date="2026-03-20"
  author="ADP Team"
  :tags="['tutorial', 'collectors', 'php']"
  readingTime="8 мин"
/>

ADP поставляется с коллекторами для логов, событий, HTTP-запросов, запросов к базе данных и многого другого. Но у каждого приложения есть доменные данные, которые стоит инспектировать. Возможно, вы хотите отслеживать вызовы платёжного шлюза, мониторить оценку feature-флагов или записывать статистику прогрева кеша. Пользовательские коллекторы позволяют вывести эти данные в панель ADP наряду со всем остальным.

В этом руководстве мы пошагово создадим собственный коллектор.

## Как работают коллекторы

Коллектор в ADP реализует интерфейс `CollectorInterface`. Во время запроса (или консольной команды) коллектор накапливает данные. Когда отладчик завершает сброс в конце жизненного цикла, данные каждого коллектора сериализуются и записываются в хранилище. Затем API передаёт их фронтенду.

Жизненный цикл выглядит так:

1. **Запуск** — Отладчик активирует все зарегистрированные коллекторы
2. **Сбор** — Прокси и ручные вызовы передают данные в коллектор
3. **Сброс** — Отладчик вызывает `collect()` для получения финального payload
4. **Хранение** — Payload сериализуется в JSON и сохраняется

## Шаг 1: Определяем класс коллектора

Создадим коллектор, отслеживающий взаимодействия с платёжным шлюзом. Создайте класс, реализующий `CollectorInterface`:

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

Метод `getName()` возвращает уникальный идентификатор, используемый как ключ хранилища. Метод `collect()` возвращает payload данных для сериализации. Опциональный `SummaryCollectorInterface` предоставляет сводные данные, отображаемые в тулбаре.

## Шаг 2: Передаём данные в коллектор

Есть два варианта передачи данных в коллектор: ручные вызовы или прокси-обёртка.

### Вариант А: Ручная запись

Внедрите коллектор и вызывайте его напрямую из сервиса:

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

### Вариант Б: Прокси-обёртка

Для более чистого разделения оберните шлюз в прокси, который записывает автоматически:

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

Подход с прокси следует тому же паттерну, который ADP использует внутри для PSR-интерфейсов. Он сохраняет бизнес-логику свободной от отладочных зависимостей.

## Шаг 3: Регистрация коллектора

Регистрация зависит от адаптера вашего фреймворка.

**Symfony** — Пометьте коллектор тегом в конфигурации сервисов:

```yaml
services:
    App\Debug\PaymentCollector:
        tags: ['adp.collector']
```

**Laravel** — Зарегистрируйте в сервис-провайдере:

```php
$this->app->singleton(PaymentCollector::class);
$this->app->tag([PaymentCollector::class], 'adp.collectors');
```

**Yii 3** — Добавьте в конфигурацию DI:

```php
return [
    PaymentCollector::class => PaymentCollector::class,
    'adp.collectors' => [
        PaymentCollector::class,
    ],
];
```

## Шаг 4: Проверка в панели

После регистрации перезапустите приложение и выполните платёжный сценарий. Откройте панель ADP и найдите данные вашего коллектора в отладочной записи. Сводные данные отображаются в тулбаре, а полный список транзакций доступен в детальном представлении.

## Советы по написанию хороших коллекторов

- **Держите payload компактным.** Коллекторы работают при каждом запросе. Избегайте хранения больших объектов или бинарных данных. Суммаризируйте где возможно.
- **Используйте временные метки.** Всегда записывайте, когда произошли события. Это помогает коррелировать данные между коллекторами.
- **Реализуйте SummaryCollectorInterface.** Сводка отображается в тулбаре и списке, давая быстрый обзор без открытия детальной страницы.
- **Обрабатывайте ошибки корректно.** Коллектор никогда не должен выбрасывать исключения, ломающие приложение. Оборачивайте рискованные операции в try/catch.
- **Сделайте его настраиваемым.** Позвольте пользователям включать/отключать коллектор или задавать пороги (например, записывать только транзакции выше определённой суммы).

## Дальнейшие шаги

С установленным пользовательским коллектором вы получаете доменную видимость в той же панели, где инспектируете логи, запросы и HTTP-вызовы. Архитектура ADP делает это возможным, потому что коллекторы — это простые, независимые единицы с чётким контрактом.

В следующей публикации мы рассмотрим, как ADP использует Server-Sent Events для передачи отладочных данных в панель в реальном времени.
