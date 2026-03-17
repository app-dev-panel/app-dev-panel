<?php

declare(strict_types=1);

namespace AppDevPanel\Adapter\Symfony\Proxy;

use AppDevPanel\Kernel\Collector\EventCollector;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Wraps Symfony's event dispatcher to intercept dispatched events.
 *
 * Implements Symfony\Component\EventDispatcher\EventDispatcherInterface (which extends
 * Symfony\Contracts\EventDispatcher\EventDispatcherInterface and PSR-14) so that the
 * proxy satisfies all type checks including instanceof in SymfonyConfigProvider.
 *
 * The Kernel's generic EventDispatcherInterfaceProxy cannot be used here because:
 *  - It only implements PSR-14's dispatch(object): object
 *  - Symfony's dispatch() has a second parameter: dispatch(object, ?string): object
 *  - Symfony resolves the event_dispatcher service via its own interface, not PSR-14
 *  - Inspector needs getListeners() which only exists on the Component interface
 */
final class SymfonyEventDispatcherProxy implements EventDispatcherInterface
{
    public function __construct(
        private readonly EventDispatcherInterface $decorated,
        private readonly EventCollector $collector,
    ) {}

    public function dispatch(object $event, ?string $eventName = null): object
    {
        /** @psalm-var array{file: string, line: int} $callStack */
        $callStack = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 1)[0];

        $this->collector->collect($event, $callStack['file'] . ':' . $callStack['line']);

        return $this->decorated->dispatch($event, $eventName);
    }

    /**
     * @param callable|array $listener Symfony passes lazy subscriber arrays [$service, 'method']
     *                                  that aren't callable until the container resolves them
     */
    public function addListener(string $eventName, callable|array $listener, int $priority = 0): void
    {
        $this->decorated->addListener($eventName, $listener, $priority);
    }

    public function addSubscriber(EventSubscriberInterface $subscriber): void
    {
        $this->decorated->addSubscriber($subscriber);
    }

    public function removeListener(string $eventName, callable $listener): void
    {
        $this->decorated->removeListener($eventName, $listener);
    }

    public function removeSubscriber(EventSubscriberInterface $subscriber): void
    {
        $this->decorated->removeSubscriber($subscriber);
    }

    public function getListeners(?string $eventName = null): array
    {
        return $this->decorated->getListeners($eventName);
    }

    public function getListenerPriority(string $eventName, callable $listener): ?int
    {
        return $this->decorated->getListenerPriority($eventName, $listener);
    }

    public function hasListeners(?string $eventName = null): bool
    {
        return $this->decorated->hasListeners($eventName);
    }
}
