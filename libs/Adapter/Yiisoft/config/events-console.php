<?php

declare(strict_types=1);

use Symfony\Component\Console\Event\ConsoleCommandEvent;
use Symfony\Component\Console\Event\ConsoleErrorEvent;
use Symfony\Component\Console\Event\ConsoleTerminateEvent;
use Yiisoft\Yii\Console\Event\ApplicationShutdown;
use Yiisoft\Yii\Console\Event\ApplicationStartup;
use Yiisoft\Yii\Debug\Collector\Console\CommandCollector;
use Yiisoft\Yii\Debug\Collector\Console\ConsoleAppInfoCollector;
use Yiisoft\Yii\Debug\Debugger;

if (!(bool)($params['yiisoft/yii-debug']['enabled'] ?? false)) {
    return [];
}

return [
    ApplicationStartup::class => [
        [Debugger::class, 'startup'],
        [ConsoleAppInfoCollector::class, 'collect'],
    ],
    ApplicationShutdown::class => [
        [ConsoleAppInfoCollector::class, 'collect'],
        [Debugger::class, 'shutdown'],
    ],
    ConsoleCommandEvent::class => [
        [ConsoleAppInfoCollector::class, 'collect'],
        [CommandCollector::class, 'collect'],
    ],
    ConsoleErrorEvent::class => [
        [ConsoleAppInfoCollector::class, 'collect'],
        [CommandCollector::class, 'collect'],
    ],
    ConsoleTerminateEvent::class => [
        [ConsoleAppInfoCollector::class, 'collect'],
        [CommandCollector::class, 'collect'],
    ],
];
