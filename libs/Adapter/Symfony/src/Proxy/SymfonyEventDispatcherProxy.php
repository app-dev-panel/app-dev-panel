<?php

declare(strict_types=1);

namespace AppDevPanel\Adapter\Symfony\Proxy;

use AppDevPanel\Kernel\Collector\EventCollector;
use AppDevPanel\Kernel\ProxyDecoratedCalls;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

/**
 * Wraps Symfony's event dispatcher to intercept dispatched events.
 *
 * Implements Symfony\Contracts\EventDispatcher\EventDispatcherInterface (which extends PSR-14)
 * so that the proxy satisfies type checks in Symfony's DI container. The Kernel's generic
 * EventDispatcherInterfaceProxy cannot be used here because:
 *  - It only implements PSR-14's dispatch(object): object
 *  - Symfony's dispatch() has a second parameter: dispatch(object, ?string): object
 *  - Symfony resolves the event_dispatcher service via its own interface, not PSR-14
 *
 * All non-dispatch methods (addListener, addSubscriber, etc.) are forwarded via __call.
 */
final class SymfonyEventDispatcherProxy implements EventDispatcherInterface
{
    use ProxyDecoratedCalls;

    public function __construct(
        private readonly object $decorated,
        private readonly EventCollector $collector,
    ) {}

    public function dispatch(object $event, ?string $eventName = null): object
    {
        /** @psalm-var array{file: string, line: int} $callStack */
        $callStack = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 1)[0];

        $this->collector->collect($event, $callStack['file'] . ':' . $callStack['line']);

        return $this->decorated->dispatch($event, $eventName);
    }
}
