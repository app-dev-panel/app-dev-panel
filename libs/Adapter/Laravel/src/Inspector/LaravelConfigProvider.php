<?php

declare(strict_types=1);

namespace AppDevPanel\Adapter\Laravel\Inspector;

use AppDevPanel\Kernel\Inspector\ClosureDescriptorTrait;
use Illuminate\Contracts\Foundation\Application;

/**
 * Provides configuration data from Laravel's container for the ADP inspector.
 *
 * Registered as 'config.adp' so InspectController can inspect app config.
 */
final class LaravelConfigProvider
{
    use ClosureDescriptorTrait;

    public function __construct(
        private readonly Application $app,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function get(string $group): array
    {
        return match ($group) {
            'di', 'services' => $this->getServices(),
            'params', 'parameters' => $this->getParameters(),
            'events' => $this->getEventListeners(),
            'events-web' => $this->getEventListeners(),
            'bundles', 'providers' => $this->getProviders(),
            default => [],
        };
    }

    /**
     * @return array<string, string>
     */
    private function getServices(): array
    {
        $services = [];

        if (method_exists($this->app, 'getBindings')) {
            $bindings = $this->app->getBindings();
            $keys = array_keys($bindings);
            sort($keys);
            foreach ($keys as $key) {
                $services[$key] = $key;
            }
        }

        return $services;
    }

    /**
     * @return array<string, mixed>
     */
    private function getParameters(): array
    {
        $config = $this->app->make('config');
        $params = $config->all();
        ksort($params);

        return $params;
    }

    /**
     * @return list<array{name: string, class: string|null, listeners: list<string|array>}>
     */
    private function getEventListeners(): array
    {
        $allListeners = $this->getRawEventListeners();
        if ($allListeners === null) {
            return [];
        }

        ksort($allListeners);

        $result = [];
        foreach ($allListeners as $eventName => $listeners) {
            $result[] = [
                'name' => $eventName,
                'class' => class_exists($eventName) ? $eventName : null,
                'listeners' => array_map($this->describeListener(...), $listeners),
            ];
        }

        return $result;
    }

    /**
     * @return array<string, list<mixed>>|null
     */
    private function getRawEventListeners(): ?array
    {
        if (!$this->app->bound('events')) {
            return null;
        }

        $dispatcher = $this->app->make('events');

        if (!method_exists($dispatcher, 'getRawListeners')) {
            return null;
        }

        return $dispatcher->getRawListeners();
    }

    /**
     * @return list<array{name: string, class: string}>
     */
    private function getProviders(): array
    {
        $providers = [];

        if (method_exists($this->app, 'getLoadedProviders')) {
            foreach ($this->app->getLoadedProviders() as $provider => $loaded) {
                $providers[] = [
                    'name' => class_basename($provider),
                    'class' => $provider,
                ];
            }
        }

        return $providers;
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
