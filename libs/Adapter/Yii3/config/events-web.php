<?php

declare(strict_types=1);

use AppDevPanel\Adapter\Yii3\Collector\Router\RouterDataExtractor;
use AppDevPanel\Adapter\Yii3\Collector\View\ViewEventListener;
use AppDevPanel\Kernel\Collector\EnvironmentCollector;
use AppDevPanel\Kernel\Collector\ExceptionCollector;
use AppDevPanel\Kernel\Collector\Web\RequestCollector;
use AppDevPanel\Kernel\Collector\Web\WebAppInfoCollector;
use AppDevPanel\Kernel\Debugger;
use AppDevPanel\Kernel\StartupContext;
use Yiisoft\ErrorHandler\Event\ApplicationError;
use Yiisoft\Profiler\ProfilerInterface;
use Yiisoft\View\Event\WebView\AfterRender;
use Yiisoft\View\Event\WebView\BeforeRender;
use Yiisoft\Yii\Http\Event\AfterEmit;
use Yiisoft\Yii\Http\Event\AfterRequest;
use Yiisoft\Yii\Http\Event\ApplicationShutdown;
use Yiisoft\Yii\Http\Event\ApplicationStartup;
use Yiisoft\Yii\Http\Event\BeforeRequest;

require_once __DIR__ . '/helpers.php';

if (!isAppDevPanelEnabled($params)) {
    return [];
}

return [
    ApplicationStartup::class => [
        static fn(ApplicationStartup $event, WebAppInfoCollector $collector) => $collector->markApplicationStarted(),
    ],
    ApplicationShutdown::class => [
        static fn(ApplicationShutdown $event, WebAppInfoCollector $collector) => $collector->markApplicationFinished(),
    ],
    BeforeRequest::class => [
        static fn(BeforeRequest $event, Debugger $debugger) => $debugger->startup(
            StartupContext::forRequest($event->getRequest()),
        ),
        static fn(BeforeRequest $event, WebAppInfoCollector $collector) => $collector->markRequestStarted(),
        static fn(BeforeRequest $event, RequestCollector $collector) => $collector->collectRequest(
            $event->getRequest(),
        ),
        static fn(BeforeRequest $event, EnvironmentCollector $collector) => $collector->collectFromRequest(
            $event->getRequest(),
        ),
    ],
    AfterRequest::class => [
        static fn(AfterRequest $event, RequestCollector $collector) => $event->getResponse() !== null
            ? $collector->collectResponse($event->getResponse())
            : null,
        static fn(AfterRequest $event, RouterDataExtractor $extractor) => $extractor->extract(),
        static fn(AfterRequest $event, WebAppInfoCollector $collector) => $collector->markRequestFinished(),
    ],
    AfterEmit::class => [
        [ProfilerInterface::class, 'flush'],
        static fn(AfterEmit $event, WebAppInfoCollector $collector) => $collector->markApplicationFinished(),
        [Debugger::class, 'shutdown'],
    ],
    ApplicationError::class => [
        static fn(ApplicationError $event, ExceptionCollector $collector) => $collector->collect(
            $event->getThrowable(),
        ),
    ],
    BeforeRender::class => [
        [ViewEventListener::class, 'beforeRender'],
    ],
    AfterRender::class => [
        [ViewEventListener::class, 'afterRender'],
    ],
];
