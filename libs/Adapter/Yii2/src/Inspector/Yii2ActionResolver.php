<?php

declare(strict_types=1);

namespace AppDevPanel\Adapter\Yii2\Inspector;

use yii\base\InlineAction;

/**
 * Resolves Yii 2 route strings (e.g. "site/index") to controller class + action method.
 *
 * Uses Yii::$app->createController() to resolve routes accurately,
 * respecting modules, controllerMap, and custom namespaces.
 */
final class Yii2ActionResolver
{
    /**
     * Resolve a Yii 2 route string to [className, methodName].
     *
     * @return array{string, string}|null Null if the route cannot be resolved to an existing controller.
     */
    public static function resolve(string $route): ?array
    {
        $app = \Yii::$app;
        if ($app === null) {
            return null;
        }

        try {
            $result = $app->createController($route);
        } catch (\Throwable) {
            return null;
        }

        if ($result === false) {
            return null;
        }

        [$controller, $actionId] = $result;

        $className = $controller::class;

        try {
            $action = $controller->createAction($actionId);
        } catch (\Throwable) {
            // Fall back to convention-based method name
            $methodName = 'action' . str_replace(' ', '', ucwords(str_replace('-', ' ', $actionId)));
            return [$className, $methodName];
        }

        if ($action instanceof InlineAction) {
            return [$className, $action->actionMethod];
        }

        // Standalone action class
        return [$action::class, 'run'];
    }
}
