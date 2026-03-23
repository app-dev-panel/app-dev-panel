<?php

declare(strict_types=1);

namespace AppDevPanel\Adapter\Yii2\Inspector;

use ReflectionClass;
use ReflectionProperty;
use yii\base\Application;
use yii\base\Component;
use yii\base\Event;

/**
 * Provides configuration data from Yii 2's application for the ADP inspector.
 *
 * Registered as the 'config' service alias so that InspectController can inspect app config.
 * Equivalent to Symfony's SymfonyConfigProvider.
 */
final class Yii2ConfigProvider
{
    private static ?ReflectionProperty $classEventsProperty = null;

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
        return $this->resolveDefinitionMap($this->app->getComponents());
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

        $this->collectClassLevelEventHandlers($events);
        $this->collectInstanceEventHandlers($events);
        $this->collectBehaviorHandlers($events);

        ksort($events);
        return $events;
    }

    /**
     * @param array<string, list<string>> $events
     */
    private function collectClassLevelEventHandlers(array &$events): void
    {
        $classEvents = $this->getClassLevelEvents();
        foreach ($classEvents as $eventName => $handlers) {
            foreach ($handlers as $className => $classHandlers) {
                $key = $className . '::' . $eventName;
                $events[$key] = array_map(static fn(array $handler): string => self::describeHandler(
                    $handler[0],
                ), $classHandlers);
            }
        }
    }

    /**
     * @param array<string, list<string>> $events
     */
    private function collectInstanceEventHandlers(array &$events): void
    {
        $instanceEvents = $this->getInstanceEvents($this->app);
        foreach ($instanceEvents as $eventName => $handlers) {
            $key = $this->app::class . '::' . $eventName . ' (instance)';
            $events[$key] = array_map(static fn(array $handler): string => self::describeHandler(
                $handler[0],
            ), $handlers);
        }
    }

    /**
     * @param array<string, list<string>> $events
     */
    private function collectBehaviorHandlers(array &$events): void
    {
        $behaviors = $this->app->getBehaviors();
        foreach ($behaviors as $name => $behavior) {
            $events['behavior:' . $name] = [$behavior::class];
        }
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

    /**
     * Describe an event handler as a human-readable string.
     */
    private static function describeHandler(mixed $handler): string
    {
        return match (true) {
            is_string($handler) => $handler,
            is_array($handler) && count($handler) === 2 => self::describeArrayHandler($handler),
            $handler instanceof \Closure => 'Closure',
            is_object($handler) => $handler::class . '::__invoke',
            default => get_debug_type($handler),
        };
    }

    /**
     * Describe an array-style [class/object, method] event handler.
     */
    private static function describeArrayHandler(array $handler): string
    {
        $class = is_object($handler[0]) ? $handler[0]::class : (string) $handler[0];
        return $class . '::' . (string) $handler[1];
    }

    /**
     * @return array<string, string>
     */
    private function getModules(): array
    {
        return $this->resolveDefinitionMap($this->app->getModules());
    }

    /**
     * Resolve a map of Yii 2 definitions (object, array with 'class', string, or other) to class name strings.
     *
     * @return array<string, string>
     */
    private function resolveDefinitionMap(array $definitions): array
    {
        $resolved = [];
        foreach ($definitions as $id => $definition) {
            $resolved[$id] = self::resolveDefinitionType($definition);
        }
        ksort($resolved);
        return $resolved;
    }

    /**
     * Resolve a single Yii 2 definition to a human-readable type string.
     */
    private static function resolveDefinitionType(mixed $definition): string
    {
        if (is_object($definition)) {
            return $definition::class;
        }
        if (is_array($definition) && array_key_exists('class', $definition)) {
            return $definition['class'];
        }
        if (is_string($definition)) {
            return $definition;
        }
        return get_debug_type($definition);
    }
}
