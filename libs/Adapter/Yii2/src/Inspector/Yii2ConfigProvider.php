<?php

declare(strict_types=1);

namespace AppDevPanel\Adapter\Yii2\Inspector;

use ReflectionClass;
use ReflectionProperty;
use yii\base\Application;
use yii\base\Component;
use yii\base\Event;
use Yiisoft\VarDumper\ClosureExporter;

/**
 * Provides configuration data from Yii 2's application for the ADP inspector.
 *
 * Registered as the 'config' service alias so that InspectController can inspect app config.
 * Equivalent to Symfony's SymfonyConfigProvider.
 */
final class Yii2ConfigProvider
{
    private static ?ReflectionProperty $classEventsProperty = null;
    private static ?ClosureExporter $closureExporter = null;

    public function __construct(
        private readonly Application $app,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function get(string $group): array
    {
        return match ($group) {
            'di', 'services' => $this->getComponents(),
            'params', 'parameters' => $this->getParams(),
            'events' => $this->getEventHandlers(),
            'events-web' => $this->getEventHandlers(),
            'modules' => $this->getModules(),
            default => [],
        };
    }

    /**
     * @return array<string, string>
     */
    private function getComponents(): array
    {
        return self::resolveDefinitionMap($this->app->getComponents());
    }

    /**
     * @return array<string, mixed>
     */
    private function getParams(): array
    {
        $params = $this->app->params;
        ksort($params);
        return $params;
    }

    /**
     * @return array<string, list<string>>
     */
    private function getEventHandlers(): array
    {
        $events = [];

        foreach ($this->getClassLevelEvents() as $eventName => $handlers) {
            foreach ($handlers as $className => $classHandlers) {
                $key = $className . '::' . $eventName;
                $events[$key] = array_map(static fn(array $handler): string => self::describeHandler(
                    $handler[0],
                ), $classHandlers);
            }
        }

        foreach ($this->getInstanceEvents($this->app) as $eventName => $handlers) {
            $key = $this->app::class . '::' . $eventName . ' (instance)';
            $events[$key] = array_map(static fn(array $handler): string => self::describeHandler(
                $handler[0],
            ), $handlers);
        }

        foreach ($this->app->getBehaviors() as $name => $behavior) {
            $events['behavior:' . $name] = [$behavior::class];
        }

        ksort($events);
        return $events;
    }

    /**
     * Read Event::$_events via reflection (class-level static event handlers).
     */
    private function getClassLevelEvents(): array
    {
        try {
            if (self::$classEventsProperty === null) {
                $ref = new ReflectionClass(Event::class);
                self::$classEventsProperty = $ref->getProperty('_events');
            }
            return self::$classEventsProperty->getValue() ?: [];
        } catch (\Throwable) {
            return [];
        }
    }

    /**
     * Read $component->_events via reflection (instance-level event handlers).
     */
    private function getInstanceEvents(Component $component): array
    {
        try {
            $ref = new ReflectionClass(Component::class);
            $prop = $ref->getProperty('_events');
            return $prop->getValue($component) ?: [];
        } catch (\Throwable) {
            return [];
        }
    }

    private static function describeHandler(mixed $handler): string
    {
        if (is_string($handler)) {
            return $handler;
        }
        if (is_array($handler) && count($handler) === 2) {
            $class = is_object($handler[0]) ? $handler[0]::class : (string) $handler[0];
            return $class . '::' . (string) $handler[1];
        }
        if ($handler instanceof \Closure) {
            return (self::$closureExporter ??= new ClosureExporter())->export($handler);
        }
        if (is_object($handler) && method_exists($handler, '__invoke')) {
            return $handler::class . '::__invoke';
        }
        return get_debug_type($handler);
    }

    /**
     * @return array<string, string>
     */
    private function getModules(): array
    {
        return self::resolveDefinitionMap($this->app->getModules());
    }

    /**
     * Resolve a map of Yii 2 definitions (object, array with 'class', string, or other) to class name strings.
     *
     * @return array<string, string>
     */
    private static function resolveDefinitionMap(array $definitions): array
    {
        $resolved = [];
        foreach ($definitions as $id => $definition) {
            $resolved[$id] = match (true) {
                is_object($definition) => $definition::class,
                is_array($definition) && array_key_exists('class', $definition) => $definition['class'],
                is_string($definition) => $definition,
                default => get_debug_type($definition),
            };
        }
        ksort($resolved);
        return $resolved;
    }
}
