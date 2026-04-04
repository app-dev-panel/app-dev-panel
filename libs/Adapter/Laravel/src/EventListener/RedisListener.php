<?php

declare(strict_types=1);

namespace AppDevPanel\Adapter\Laravel\EventListener;

use AppDevPanel\Kernel\Collector\RedisCollector;
use AppDevPanel\Kernel\Collector\RedisCommandRecord;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Redis\Events\CommandExecuted;

/**
 * Listens for Illuminate\Redis\Events\CommandExecuted and feeds the RedisCollector.
 */
final class RedisListener
{
    /** @var \Closure(): RedisCollector */
    private \Closure $collectorFactory;

    /**
     * @param \Closure(): RedisCollector $collectorFactory
     */
    public function __construct(\Closure $collectorFactory)
    {
        $this->collectorFactory = $collectorFactory;
    }

    public function register(Dispatcher $events): void
    {
        $events->listen(CommandExecuted::class, function (CommandExecuted $event): void {
            ($this->collectorFactory)()->logCommand(new RedisCommandRecord(
                connection: $event->connectionName,
                command: strtoupper($event->command),
                arguments: $event->parameters,
                result: null,
                duration: $event->time / 1000,
            ));
        });
    }
}
