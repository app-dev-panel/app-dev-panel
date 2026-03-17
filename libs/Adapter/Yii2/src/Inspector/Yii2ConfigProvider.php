<?php

declare(strict_types=1);

namespace AppDevPanel\Adapter\Yii2\Inspector;

use yii\base\Application;

/**
 * Provides configuration data from Yii 2's application for the ADP inspector.
 *
 * Registered as the 'config' service alias so that InspectController can inspect app config.
 * Equivalent to Symfony's SymfonyConfigProvider.
 */
final class Yii2ConfigProvider
{
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

        // Collect events from the application
        $behaviors = $this->app->getBehaviors();
        foreach ($behaviors as $name => $behavior) {
            $events['behavior:' . $name] = [is_object($behavior) ? $behavior::class : (string) $behavior];
        }

        return $events;
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
