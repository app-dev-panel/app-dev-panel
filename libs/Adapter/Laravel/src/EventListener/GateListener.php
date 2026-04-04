<?php

declare(strict_types=1);

namespace AppDevPanel\Adapter\Laravel\EventListener;

use AppDevPanel\Kernel\Collector\AuthorizationCollector;
use Illuminate\Auth\Access\Events\GateEvaluated;
use Illuminate\Contracts\Events\Dispatcher;

/**
 * Listens for Laravel Gate evaluation events and feeds AuthorizationCollector
 * with access decision data (granted/denied).
 */
final class GateListener
{
    /** @var \Closure(): AuthorizationCollector */
    private \Closure $collectorFactory;

    /**
     * @param \Closure(): AuthorizationCollector $collectorFactory
     */
    public function __construct(\Closure $collectorFactory)
    {
        $this->collectorFactory = $collectorFactory;
    }

    public function register(Dispatcher $events): void
    {
        $events->listen(GateEvaluated::class, function (GateEvaluated $event): void {
            $collector = ($this->collectorFactory)();
            $collector->logAccessDecision(
                attribute: $event->ability,
                subject: $this->formatArguments($event->arguments),
                result: $event->result ? 'ACCESS_GRANTED' : 'ACCESS_DENIED',
            );
        });
    }

    private function formatArguments(array $arguments): string
    {
        if ($arguments === []) {
            return '';
        }

        $parts = [];
        foreach ($arguments as $arg) {
            $parts[] = match (true) {
                is_object($arg) => $arg::class,
                is_string($arg) => $arg,
                default => get_debug_type($arg),
            };
        }

        return implode(', ', $parts);
    }
}
