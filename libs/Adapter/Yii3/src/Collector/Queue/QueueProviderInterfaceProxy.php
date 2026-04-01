<?php

declare(strict_types=1);

namespace AppDevPanel\Adapter\Yii3\Collector\Queue;

use AppDevPanel\Kernel\Collector\QueueCollector;
use BackedEnum;
use Yiisoft\Queue\Provider\QueueProviderInterface;
use Yiisoft\Queue\QueueInterface;

final class QueueProviderInterfaceProxy implements QueueProviderInterface
{
    public function __construct(
        private readonly QueueProviderInterface $queueProvider,
        private readonly QueueCollector $collector,
    ) {}

    public function get(string|BackedEnum $name): QueueInterface
    {
        return new QueueDecorator($this->queueProvider->get($name), $this->collector);
    }

    public function has(string|BackedEnum $name): bool
    {
        return $this->queueProvider->has($name);
    }
}
