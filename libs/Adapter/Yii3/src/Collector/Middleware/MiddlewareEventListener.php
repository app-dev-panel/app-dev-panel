<?php

declare(strict_types=1);

namespace AppDevPanel\Adapter\Yii3\Collector\Middleware;

use AppDevPanel\Kernel\Collector\MiddlewareCollector;
use ReflectionClass;
use Yiisoft\Middleware\Dispatcher\Event\AfterMiddleware;
use Yiisoft\Middleware\Dispatcher\Event\BeforeMiddleware;

/**
 * Listens to Yii middleware events and feeds normalized data
 * to the framework-agnostic Kernel MiddlewareCollector.
 */
final class MiddlewareEventListener
{
    public function __construct(
        private readonly MiddlewareCollector $collector,
    ) {}

    public function collect(BeforeMiddleware|AfterMiddleware $event): void
    {
        $name = $this->resolveMiddlewareName($event);

        if ($event instanceof BeforeMiddleware) {
            $this->collector->collectBefore($name, microtime(true), memory_get_usage(), $event->getRequest());
        } else {
            $this->collector->collectAfter($name, microtime(true), memory_get_usage(), $event->getResponse());
        }
    }

    private function resolveMiddlewareName(BeforeMiddleware|AfterMiddleware $event): string
    {
        if (
            method_exists($event->getMiddleware(), '__debugInfo')
            && new ReflectionClass($event->getMiddleware())->isAnonymous()
        ) {
            $callback = $event->getMiddleware()->__debugInfo()['callback'];
            if (is_array($callback)) {
                if (is_string($callback[0])) {
                    return implode('::', $callback);
                }
                return $callback[0]::class . '::' . $callback[1];
            }
            if (is_string($callback)) {
                return '{closure:' . $callback . '}';
            }
            return 'object(Closure)#' . spl_object_id($callback);
        }

        return $event->getMiddleware()::class;
    }
}
