<?php

declare(strict_types=1);

use Yiisoft\ErrorHandler\Event\ApplicationError;
use Yiisoft\Profiler\ProfilerInterface;
use AppDevPanel\Kernel\Collector\ExceptionCollector;
use AppDevPanel\Kernel\Collector\Web\RequestCollector;
use AppDevPanel\Kernel\Collector\Web\WebAppInfoCollector;
use AppDevPanel\Kernel\Debugger;
use Yiisoft\Yii\Http\Event\AfterEmit;
use Yiisoft\Yii\Http\Event\AfterRequest;
use Yiisoft\Yii\Http\Event\ApplicationShutdown;
use Yiisoft\Yii\Http\Event\ApplicationStartup;
use Yiisoft\Yii\Http\Event\BeforeRequest;

if (!(bool) ($params['app-dev-panel/yii-debug']['enabled'] ?? false)) {
    return [];
}

return [
    ApplicationStartup::class => [
        [Debugger::class, 'startup'],
        [WebAppInfoCollector::class, 'collect'],
    ],
    ApplicationShutdown::class => [
        [WebAppInfoCollector::class, 'collect'],
    ],
    BeforeRequest::class => [
        [Debugger::class, 'startup'],
        [WebAppInfoCollector::class, 'collect'],
        [RequestCollector::class, 'collect'],
    ],
    AfterRequest::class => [
        [WebAppInfoCollector::class, 'collect'],
        [RequestCollector::class, 'collect'],
    ],
    AfterEmit::class => [
        [ProfilerInterface::class, 'flush'],
        [WebAppInfoCollector::class, 'collect'],
        [Debugger::class, 'shutdown'],
    ],
    ApplicationError::class => [
        [ExceptionCollector::class, 'collect'],
    ],
];
