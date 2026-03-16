<?php

declare(strict_types=1);

namespace AppDevPanel\Adapter\Yiisoft\Collector\Queue;

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
        $this->collector->collectWorkerProcessing($message, $queue);
        return $this->worker->process($message, $queue);
    }
}
