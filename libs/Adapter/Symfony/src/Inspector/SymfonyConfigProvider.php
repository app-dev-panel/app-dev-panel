<?php

declare(strict_types=1);

namespace AppDevPanel\Adapter\Symfony\Inspector;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Provides configuration data from Symfony's container for the ADP inspector.
 *
 * Registered as the 'config' service in the container so that
 * {@see \AppDevPanel\Api\Inspector\Controller\InspectController} can inspect app config.
 */
final class SymfonyConfigProvider
{
    public function __construct(
        private readonly ContainerInterface $container,
        private readonly array $containerParameters = [],
        private readonly array $bundleConfig = [],
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function get(string $group): array
    {
        return match ($group) {
            'di', 'services' => $this->getServices(),
            'params', 'parameters' => $this->containerParameters,
            'events' => $this->getEventListeners(),
            'events-web' => $this->getEventListeners(),
            'bundles' => $this->bundleConfig,
            default => [],
        };
    }

    /**
     * @return array<string, string>
     */
    private function getServices(): array
    {
        $services = [];
        if (method_exists($this->container, 'getServiceIds')) {
            /** @var list<string> $ids */
            $ids = $this->container->getServiceIds();
            sort($ids);
            foreach ($ids as $id) {
                $services[$id] = $id;
            }
        }
        return $services;
    }

    /**
     * @return array<string, list<string>>
     */
    private function getEventListeners(): array
    {
        if (!$this->container->has('event_dispatcher')) {
            return [];
        }

        $dispatcher = $this->container->get('event_dispatcher');
        if (!$dispatcher instanceof EventDispatcherInterface) {
            return [];
        }

        $listeners = [];
        /** @var array<string, list<callable>> $allListeners */
        $allListeners = $dispatcher->getListeners();
        foreach ($allListeners as $eventName => $eventListeners) {
            $listeners[$eventName] = [];
            foreach ($eventListeners as $listener) {
                $listeners[$eventName][] = $this->describeListener($listener);
            }
        }
        ksort($listeners);

        return $listeners;
    }

    private function describeListener(mixed $listener): string
    {
        if (is_string($listener)) {
            return $listener;
        }
        if (is_array($listener) && count($listener) === 2) {
            $class = is_object($listener[0]) ? $listener[0]::class : (string) $listener[0];
            return $class . '::' . $listener[1];
        }
        if ($listener instanceof \Closure) {
            $ref = new \ReflectionFunction($listener);
            $class = $ref->getClosureScopeClass();
            if ($class !== null) {
                return $class->getName() . '::' . ($ref->getName() !== '{closure}' ? $ref->getName() : '{closure}');
            }
            return $ref->getName();
        }
        if (is_object($listener) && method_exists($listener, '__invoke')) {
            return $listener::class . '::__invoke';
        }
        return get_debug_type($listener);
    }
}
