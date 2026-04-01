<?php

declare(strict_types=1);

namespace AppDevPanel\Adapter\Symfony\Inspector;

use AppDevPanel\Kernel\Inspector\ClosureDescriptorTrait;
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
    use ClosureDescriptorTrait;

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
     * @return list<array{name: string, class: string|null, listeners: list<string|array>}>
     */
    private function getEventListeners(): array
    {
        $dispatcher = $this->resolveEventDispatcher();
        if ($dispatcher === null) {
            return [];
        }

        $reverseAliases = $this->buildReverseAliasMap();

        $result = [];
        /** @var array<string, list<callable>> $allListeners */
        $allListeners = $dispatcher->getListeners();
        ksort($allListeners);

        foreach ($allListeners as $eventName => $eventListeners) {
            $result[] = [
                'name' => $eventName,
                'class' => class_exists($eventName) ? $eventName : $reverseAliases[$eventName] ?? null,
                'listeners' => array_map($this->describeListener(...), $eventListeners),
            ];
        }

        return $result;
    }

    private function resolveEventDispatcher(): ?EventDispatcherInterface
    {
        if (!$this->container->has('event_dispatcher')) {
            return null;
        }

        $dispatcher = $this->container->get('event_dispatcher');
        if (!$dispatcher instanceof EventDispatcherInterface) {
            return null;
        }

        return $dispatcher;
    }

    /**
     * Build reverse alias map: string alias -> FQCN.
     *
     * @return array<string, string>
     */
    private function buildReverseAliasMap(): array
    {
        $aliases = $this->containerParameters['event_dispatcher.event_aliases'] ?? [];
        if (!is_array($aliases)) {
            return [];
        }

        $reverseAliases = [];
        foreach ($aliases as $class => $alias) {
            if (!is_string($class) || !is_string($alias)) {
                continue;
            }
            $reverseAliases[$alias] = $class;
        }
        return $reverseAliases;
    }

    /**
     * @return string|array{__closure: true, source: string, file: string|null, startLine: int|null, endLine: int|null}
     */
    private function describeListener(mixed $listener): string|array
    {
        if (is_string($listener)) {
            return $listener;
        }
        if (is_array($listener) && count($listener) === 2) {
            $class = is_object($listener[0]) ? $listener[0]::class : (string) $listener[0];
            return $class . '::' . $listener[1];
        }
        if ($listener instanceof \Closure) {
            return self::describeClosure($listener);
        }
        if (is_object($listener) && method_exists($listener, '__invoke')) {
            return $listener::class . '::__invoke';
        }
        return get_debug_type($listener);
    }
}
