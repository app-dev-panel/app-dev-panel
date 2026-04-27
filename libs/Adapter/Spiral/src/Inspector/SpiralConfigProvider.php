<?php

declare(strict_types=1);

namespace AppDevPanel\Adapter\Spiral\Inspector;

use Spiral\Boot\BootloadManager\InitializerInterface;
use Spiral\Boot\DirectoriesInterface;
use Spiral\Boot\EnvironmentInterface;
use Spiral\Core\Container;
use Spiral\Core\Internal\Introspector;
use Throwable;

/**
 * Provides configuration data from Spiral's container/environment for the ADP inspector.
 *
 * Bound under the `'config'` alias so {@see \AppDevPanel\Api\Inspector\Controller\InspectController}
 * can introspect application config via duck-typed `$container->get('config')->get(string $group)`.
 *
 * Each Spiral introspection source (the bootload registry, environment, directories) is
 * optional — apps that don't expose them simply yield empty arrays for the corresponding
 * group, never an error.
 */
final class SpiralConfigProvider
{
    public function __construct(
        private readonly Container $container,
        private readonly ?EnvironmentInterface $env = null,
        private readonly ?DirectoriesInterface $dirs = null,
        private readonly ?InitializerInterface $initializer = null,
        private readonly ?SpiralEventListenerProvider $events = null,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function get(string $group): array
    {
        return match ($group) {
            'di', 'services' => $this->getServices(),
            'params', 'parameters' => $this->getParams(),
            'events', 'events-web' => $this->getEvents(),
            'bundles' => $this->getBootloaders(),
            default => [],
        };
    }

    /**
     * @return array<string, string>
     */
    private function getServices(): array
    {
        $aliases = $this->readBindingAliases();
        sort($aliases);

        $out = [];
        foreach ($aliases as $alias) {
            $out[$alias] = $alias;
        }

        return $out;
    }

    /**
     * @return list<string>
     */
    private function readBindingAliases(): array
    {
        try {
            $accessor = Introspector::getAccessor($this->container);
            $state = $accessor->state;
            $bindings = $state->bindings;
        } catch (Throwable) {
            return [];
        }

        if (!is_array($bindings)) {
            return [];
        }

        $aliases = [];
        foreach (array_keys($bindings) as $alias) {
            if (is_string($alias) && $alias !== '') {
                $aliases[] = $alias;
            }
        }

        return $aliases;
    }

    /**
     * @return array<string, mixed>
     */
    private function getParams(): array
    {
        $params = [];

        if ($this->env !== null) {
            try {
                $envValues = $this->env->getAll();
                if (is_array($envValues)) {
                    foreach ($envValues as $key => $value) {
                        if (is_string($key) && $key !== '') {
                            $params[$key] = $value;
                        }
                    }
                }
            } catch (Throwable) {
                // Environment introspection is best-effort.
            }
        }

        if ($this->dirs !== null) {
            try {
                $directories = $this->dirs->getAll();
                if (is_array($directories) && $directories !== []) {
                    foreach ($directories as $name => $path) {
                        if (is_string($name) && $name !== '') {
                            $params['directories.' . $name] = $path;
                        }
                    }
                }
            } catch (Throwable) {
                // Directory introspection is best-effort.
            }
        }

        return $params;
    }

    /**
     * @return list<array{name: string, class: string|null, listeners: list<mixed>}>
     */
    private function getEvents(): array
    {
        if ($this->events === null) {
            return [];
        }

        return $this->events->getInspectorPayload();
    }

    /**
     * @return array<string, string>
     */
    private function getBootloaders(): array
    {
        if ($this->initializer === null) {
            return [];
        }

        try {
            $registry = $this->initializer->getRegistry();
            $classes = $registry->getClasses();
        } catch (Throwable) {
            return [];
        }

        $out = [];
        foreach ($classes as $class) {
            if (is_string($class) && $class !== '') {
                $out[$class] = $class;
            }
        }

        ksort($out);

        return $out;
    }
}
