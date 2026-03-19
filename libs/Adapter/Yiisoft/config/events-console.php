<?php

declare(strict_types=1);

use AppDevPanel\Kernel\Collector\Console\CommandCollector;
use AppDevPanel\Kernel\Collector\Console\ConsoleAppInfoCollector;
use AppDevPanel\Kernel\Collector\EnvironmentCollector;
use AppDevPanel\Kernel\Debugger;
use AppDevPanel\Kernel\StartupContext;
use Symfony\Component\Console\Event\ConsoleCommandEvent;
use Symfony\Component\Console\Event\ConsoleErrorEvent;
use Symfony\Component\Console\Event\ConsoleTerminateEvent;
use Yiisoft\Yii\Console\Event\ApplicationShutdown;
use Yiisoft\Yii\Console\Event\ApplicationStartup;

require_once __DIR__ . '/helpers.php';

if (!isAppDevPanelEnabled($params)) {
    return [];
}

return [
    ApplicationStartup::class => [
        static fn(
            ApplicationStartup $event,
            Debugger $debugger,
        ) => $debugger->startup(StartupContext::forCommand($event->commandName)),
        static fn(
            ApplicationStartup $event,
            ConsoleAppInfoCollector $collector,
        ) => $collector->markApplicationStarted(),
        static fn(ApplicationStartup $event, EnvironmentCollector $collector) => $collector->collectFromGlobals(),
    ],
    ApplicationShutdown::class => [
        static fn(
            ApplicationShutdown $event,
            ConsoleAppInfoCollector $collector,
        ) => $collector->markApplicationFinished(),
        [Debugger::class, 'shutdown'],
    ],
    ConsoleCommandEvent::class => [
        [ConsoleAppInfoCollector::class, 'collect'],
        [CommandCollector::class,        'collect'],
    ],
    ConsoleErrorEvent::class => [
        [ConsoleAppInfoCollector::class, 'collect'],
        [CommandCollector::class,        'collect'],
    ],
    ConsoleTerminateEvent::class => [
        [ConsoleAppInfoCollector::class, 'collect'],
        [CommandCollector::class,        'collect'],
    ],
];
