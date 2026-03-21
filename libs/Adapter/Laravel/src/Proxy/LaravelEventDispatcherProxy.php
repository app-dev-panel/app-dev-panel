<?php

declare(strict_types=1);

namespace AppDevPanel\Adapter\Laravel\Proxy;

use AppDevPanel\Kernel\Collector\EventCollector;
use Illuminate\Contracts\Events\Dispatcher;

/**
 * Wraps Laravel's event dispatcher to intercept dispatched events.
 *
 * Implements Illuminate\Contracts\Events\Dispatcher so the proxy
 * satisfies all type checks within the Laravel framework.
 */
final class LaravelEventDispatcherProxy implements Dispatcher
{
    public function __construct(
        private readonly Dispatcher $decorated,
        private readonly EventCollector $collector,
    ) {}

    public function dispatch($event, $payload = [], $halt = false): mixed
    {
        if (is_object($event)) {
            $callStack = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 1)[0];
            $this->collector->collect($event, ($callStack['file'] ?? '') . ':' . ($callStack['line'] ?? 0));
        }

        return $this->decorated->dispatch($event, $payload, $halt);
    }

    public function listen($events, $listener = null): void
    {
        $this->decorated->listen($events, $listener);
    }

    public function hasListeners($eventName): bool
    {
        return $this->decorated->hasListeners($eventName);
    }

    public function subscribe($subscriber): void
    {
        $this->decorated->subscribe($subscriber);
    }

    public function until($event, $payload = []): mixed
    {
        return $this->decorated->until($event, $payload);
    }

    public function push($event, $payload = []): void
    {
        $this->decorated->push($event, $payload);
    }

    public function flush($event): void
    {
        $this->decorated->flush($event);
    }

    public function forget($event): void
    {
        $this->decorated->forget($event);
    }

    public function forgetPushed(): void
    {
        $this->decorated->forgetPushed();
    }

    /**
     * Forward any other method calls to the decorated dispatcher.
     */
    public function __call(string $method, array $parameters): mixed
    {
        return $this->decorated->{$method}(...$parameters);
    }
}
