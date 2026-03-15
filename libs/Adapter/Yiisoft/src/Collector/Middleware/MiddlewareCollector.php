<?php

declare(strict_types=1);

namespace AppDevPanel\Adapter\Yiisoft\Collector\Middleware;

use AppDevPanel\Kernel\Collector\CollectorTrait;
use AppDevPanel\Kernel\Collector\SummaryCollectorInterface;
use AppDevPanel\Kernel\Collector\TimelineCollector;
use ReflectionClass;
use Yiisoft\Middleware\Dispatcher\Event\AfterMiddleware;
use Yiisoft\Middleware\Dispatcher\Event\BeforeMiddleware;

final class MiddlewareCollector implements SummaryCollectorInterface
{
    use CollectorTrait;

    /** @var array[] */
    private array $beforeStack = [];

    /** @var array[] */
    private array $afterStack = [];

    public function __construct(
        private readonly TimelineCollector $timelineCollector,
    ) {}

    public function getCollected(): array
    {
        if (!$this->isActive()) {
            return [];
        }
        $beforeStack = $this->beforeStack;
        $afterStack = $this->afterStack;
        $beforeAction = array_pop($beforeStack);
        $afterAction = array_shift($afterStack);
        $actionHandler = [];

        if (is_array($beforeAction) && is_array($afterAction)) {
            $actionHandler = $this->getActionHandler($beforeAction, $afterAction);
        }

        return [
            'beforeStack' => $beforeStack,
            'actionHandler' => $actionHandler,
            'afterStack' => $afterStack,
        ];
    }

    public function collect(BeforeMiddleware|AfterMiddleware $event): void
    {
        if (!$this->isActive()) {
            return;
        }

        if (
            method_exists($event->getMiddleware(), '__debugInfo')
            && new ReflectionClass($event->getMiddleware())->isAnonymous()
        ) {
            $callback = $event->getMiddleware()->__debugInfo()['callback'];
            if (is_array($callback)) {
                if (is_string($callback[0])) {
                    $name = implode('::', $callback);
                } else {
                    $name = $callback[0]::class . '::' . $callback[1];
                }
            } elseif (is_string($callback)) {
                $name = '{closure:' . $callback . '}';
            } else {
                $name = 'object(Closure)#' . spl_object_id($callback);
            }
        } else {
            $name = $event->getMiddleware()::class;
        }
        if ($event instanceof BeforeMiddleware) {
            $this->beforeStack[] = [
                'name' => $name,
                'time' => microtime(true),
                'memory' => memory_get_usage(),
                'request' => $event->getRequest(),
            ];
        } else {
            $this->afterStack[] = [
                'name' => $name,
                'time' => microtime(true),
                'memory' => memory_get_usage(),
                'response' => $event->getResponse(),
            ];
        }
        $this->timelineCollector->collect($this, spl_object_id($event));
    }

    public function getSummary(): array
    {
        if (!$this->isActive()) {
            return [];
        }
        return [
            'total' => ($total = count($this->beforeStack)) > 0 ? $total - 1 : 0,
        ];
    }

    private function reset(): void
    {
        $this->beforeStack = [];
        $this->afterStack = [];
    }

    private function getActionHandler(array $beforeAction, array $afterAction): array
    {
        return [
            'name' => $beforeAction['name'],
            'startTime' => $beforeAction['time'],
            'request' => $beforeAction['request'],
            'response' => $afterAction['response'],
            'endTime' => $afterAction['time'],
            'memory' => $afterAction['memory'],
        ];
    }
}
