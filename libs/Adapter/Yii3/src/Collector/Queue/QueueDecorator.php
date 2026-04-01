<?php

declare(strict_types=1);

namespace AppDevPanel\Adapter\Yii3\Collector\Queue;

use AppDevPanel\Kernel\Collector\QueueCollector;
use BackedEnum;
use Yiisoft\Queue\Adapter\AdapterInterface;
use Yiisoft\Queue\JobStatus;
use Yiisoft\Queue\Message\MessageInterface;
use Yiisoft\Queue\Middleware\Push\MiddlewarePushInterface;
use Yiisoft\Queue\QueueInterface;

final class QueueDecorator implements QueueInterface
{
    public function __construct(
        private readonly QueueInterface $queue,
        private readonly QueueCollector $collector,
    ) {}

    public function status(string|int $id): JobStatus
    {
        $result = $this->queue->status($id);
        $this->collector->collectStatus((string) $id, $result->key());
        return $result;
    }

    public function push(
        MessageInterface $message,
        string|array|callable|MiddlewarePushInterface ...$middlewareDefinitions,
    ): MessageInterface {
        $message = $this->queue->push($message, ...$middlewareDefinitions);
        $this->collector->collectPush($this->queue->getName(), $message, array_values($middlewareDefinitions));
        return $message;
    }

    public function run(int $max = 0): int
    {
        return $this->queue->run($max);
    }

    public function listen(): void
    {
        $this->queue->listen();
    }

    public function withAdapter(AdapterInterface $adapter, string|BackedEnum|null $queueName = null): static
    {
        return new self($this->queue->withAdapter($adapter, $queueName), $this->collector);
    }

    public function getName(): string
    {
        return $this->queue->getName();
    }
}
