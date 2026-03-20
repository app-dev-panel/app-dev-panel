<?php

declare(strict_types=1);

namespace AppDevPanel\Adapter\Yiisoft\Collector\Queue;

use AppDevPanel\Kernel\Collector\QueueCollector;
use Yiisoft\Queue\Message\MessageInterface;
use Yiisoft\Queue\QueueInterface;
use Yiisoft\Queue\Worker\WorkerInterface;

final class QueueWorkerInterfaceProxy implements WorkerInterface
{
    public function __construct(
        private readonly WorkerInterface $worker,
        private readonly QueueCollector $collector,
    ) {}

    public function process(MessageInterface $message, QueueInterface $queue): MessageInterface
    {
        $this->collector->collectWorkerProcessing($message, $queue->getName());
        return $this->worker->process($message, $queue);
    }
}
