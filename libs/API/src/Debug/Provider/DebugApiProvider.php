<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Debug\Api\Debug\Provider;

use Psr\Container\ContainerInterface;
use Yiisoft\Di\ServiceProviderInterface;
use Yiisoft\Router\RouteCollectorInterface;
use Yiisoft\Yii\Debug\Api\Debug\Http\HttpApplicationWrapper;
use Yiisoft\Yii\Debug\Api\Debug\Http\RouteCollectorWrapper;
use Yiisoft\Yii\Http\Application;

final class DebugApiProvider implements ServiceProviderInterface
{
    /**
     * @psalm-suppress InaccessibleMethod
     */
    public function getDefinitions(): array
    {
        return [];
    }

    /**
     * @psalm-suppress UndefinedClass
     */
    public function getExtensions(): array
    {
        $extensions = [
            RouteCollectorInterface::class => static function (
                ContainerInterface $container,
                RouteCollectorInterface $routeCollector
            ) {
                /**
                 * Register debug middlewares twice because a `Subfolder` middleware may rewrite base URL
                 */
                $routerCollectionWrapper = $container->get(RouteCollectorWrapper::class);
                $routerCollectionWrapper->wrap($routeCollector);

                return $routeCollector;
            },
        ];
        if (class_exists(Application::class)) {
            $extensions[Application::class] = static function (
                ContainerInterface $container,
                Application $application
            ): Application {
                $applicationWrapper = $container->get(HttpApplicationWrapper::class);
                $applicationWrapper->wrap($application);

                return $application;
            };
        }
        return $extensions;
    }
}
