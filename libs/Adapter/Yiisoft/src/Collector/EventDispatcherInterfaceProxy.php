<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Debug\Collector;

use Psr\EventDispatcher\EventDispatcherInterface;
use Yiisoft\Yii\Debug\ProxyDecoratedCalls;

final class EventDispatcherInterfaceProxy implements EventDispatcherInterface
{
    use ProxyDecoratedCalls;

    public function __construct(
        private readonly EventDispatcherInterface $decorated,
        private readonly EventCollector $collector
    ) {
    }

    public function dispatch(object $event): object
    {
        /** @psalm-var array{file: string, line: int} $callStack */
        $callStack = debug_backtrace()[0];

        $this->collector->collect($event, $callStack['file'] . ':' . $callStack['line']);

        return $this->decorated->dispatch($event);
    }
}
