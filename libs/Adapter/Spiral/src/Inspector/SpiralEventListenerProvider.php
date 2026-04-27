<?php

declare(strict_types=1);

namespace AppDevPanel\Adapter\Spiral\Inspector;

use AppDevPanel\Kernel\Inspector\ClosureDescriptor;
use Closure;
use Psr\Container\ContainerInterface;
use ReflectionClass;
use Throwable;

/**
 * Reads listeners registered with `spiral/events` (`Spiral\Events\ListenerRegistryInterface`)
 * and emits them in the canonical inspector shape.
 *
 * `ListenerRegistryInterface` exposes only `addListener()` publicly — the actual list of
 * registered listeners lives on a private `$listeners` property of the concrete registry
 * implementation. Reflection is used to read it; if the property is absent (registry is
 * a custom implementation that stores listeners differently), an empty payload is returned.
 *
 * Used internally by {@see SpiralConfigProvider} to satisfy the `'events'` group request
 * routed through the `'config'` container alias by
 * {@see \AppDevPanel\Api\Inspector\Controller\InspectController::eventListeners()}.
 */
final class SpiralEventListenerProvider
{
    public function __construct(
        private readonly ContainerInterface $container,
    ) {}

    /**
     * @return list<array{name: string, class: string|null, listeners: list<mixed>}>
     */
    public function getInspectorPayload(): array
    {
        if (!interface_exists(\Spiral\Events\ListenerRegistryInterface::class)) {
            return [];
        }
        if (!$this->container->has(\Spiral\Events\ListenerRegistryInterface::class)) {
            return [];
        }

        try {
            $registry = $this->container->get(\Spiral\Events\ListenerRegistryInterface::class);
        } catch (Throwable) {
            return [];
        }

        if (!is_object($registry)) {
            return [];
        }

        $listeners = $this->extractListeners($registry);
        if ($listeners === null) {
            return [];
        }

        ksort($listeners);

        $result = [];
        foreach ($listeners as $eventName => $eventListeners) {
            if (!is_string($eventName) || !is_array($eventListeners)) {
                continue;
            }
            $result[] = [
                'name' => $eventName,
                'class' => class_exists($eventName) ? $eventName : null,
                'listeners' => array_values(array_map($this->describeListener(...), $eventListeners)),
            ];
        }

        return $result;
    }

    /**
     * @return array<string, array<int, mixed>>|null
     */
    private function extractListeners(object $registry): ?array
    {
        try {
            $reflection = new ReflectionClass($registry);
        } catch (Throwable) {
            return null;
        }

        $current = $reflection;
        while ($current !== false) {
            if ($current->hasProperty('listeners')) {
                try {
                    $property = $current->getProperty('listeners');
                    $value = $property->getValue($registry);
                    if (is_array($value)) {
                        /** @var array<string, array<int, mixed>> $value */
                        return $value;
                    }
                } catch (Throwable) {
                    return null;
                }
                return null;
            }
            $current = $current->getParentClass();
        }

        return null;
    }

    /**
     * @return string|array<string, mixed>
     */
    private function describeListener(mixed $listener): string|array
    {
        if (is_string($listener)) {
            return $listener;
        }
        if ($listener instanceof Closure) {
            return ClosureDescriptor::describe($listener);
        }
        if (is_array($listener) && count($listener) === 2) {
            $target = $listener[0] ?? null;
            $method = $listener[1] ?? null;
            $class = is_object($target) ? $target::class : (is_string($target) ? $target : get_debug_type($target));
            return [
                'class' => $class,
                'method' => is_string($method) ? $method : get_debug_type($method),
            ];
        }
        if (is_object($listener)) {
            return [
                'class' => $listener::class,
                'method' => '__invoke',
            ];
        }

        return get_debug_type($listener);
    }
}
