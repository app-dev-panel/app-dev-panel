<?php

declare(strict_types=1);

namespace AppDevPanel\Adapter\Yii2\Inspector;

use ReflectionClass;
use ReflectionProperty;
use yii\base\Application;
use yii\base\Behavior;
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
        $components = [];

        foreach ($this->app->getComponents() as $id => $definition) {
            if (is_object($definition)) {
                $components[$id] = $definition::class;
            } elseif (is_array($definition) && isset($definition['class'])) {
                $components[$id] = $definition['class'];
            } elseif (is_string($definition)) {
                $components[$id] = $definition;
            } else {
                $components[$id] = get_debug_type($definition);
            }
        }

        ksort($components);
        return $components;
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

        // 1. Class-level events registered via Event::on() — stored in Event::$_events (private static).
        $classEvents = $this->getClassLevelEvents();
        foreach ($classEvents as $eventName => $handlers) {
            foreach ($handlers as $className => $classHandlers) {
                $key = $className . '::' . $eventName;
                $events[$key] = array_map(
                    static fn (array $handler): string => self::describeHandler($handler[0]),
                    $classHandlers,
                );
            }
        }

        // 2. Instance-level events on the application component via $app->on().
        $instanceEvents = $this->getInstanceEvents($this->app);
        foreach ($instanceEvents as $eventName => $handlers) {
            $key = $this->app::class . '::' . $eventName . ' (instance)';
            $events[$key] = array_map(
                static fn (array $handler): string => self::describeHandler($handler[0]),
                $handlers,
            );
        }

        // 3. Behaviors attached to the application.
        $behaviors = $this->app->getBehaviors();
        foreach ($behaviors as $name => $behavior) {
            $events['behavior:' . $name] = [
                $behavior instanceof Behavior ? $behavior::class : (is_object($behavior) ? $behavior::class : (string) $behavior),
            ];
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

    /**
     * Describe an event handler as a human-readable string.
     */
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
            return 'Closure';
        }
        if (is_object($handler)) {
            return $handler::class . '::__invoke';
        }
        return get_debug_type($handler);
    }

    /**
     * @return array<string, string>
     */
    private function getModules(): array
    {
        $modules = [];
        foreach ($this->app->getModules() as $id => $module) {
            if (is_object($module)) {
                $modules[$id] = $module::class;
            } elseif (is_array($module) && isset($module['class'])) {
                $modules[$id] = $module['class'];
            } elseif (is_string($module)) {
                $modules[$id] = $module;
            } else {
                $modules[$id] = get_debug_type($module);
            }
        }
        ksort($modules);
        return $modules;
    }
}
