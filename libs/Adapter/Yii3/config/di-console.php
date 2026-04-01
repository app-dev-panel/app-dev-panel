<?php

declare(strict_types=1);

use AppDevPanel\Api\Debug\Repository\CollectorRepository;
use AppDevPanel\Api\Debug\Repository\CollectorRepositoryInterface;
use AppDevPanel\Cli\Command\DebugServerCommand;
use AppDevPanel\Cli\Command\InspectConfigCommand;
use AppDevPanel\Cli\Command\InspectRoutesCommand;
use AppDevPanel\Kernel\Collector\Console\ConsoleAppInfoCollector;
use AppDevPanel\Kernel\Debugger;
use AppDevPanel\Kernel\DebuggerIgnoreConfig;
use AppDevPanel\Kernel\Storage\StorageInterface;
use Psr\Container\ContainerInterface;
use Yiisoft\Definitions\ReferencesArray;

require_once __DIR__ . '/helpers.php';

if (!isAppDevPanelEnabled($params)) {
    return [];
}

return [
    Debugger::class => [
        '__construct()' => [
            'collectors' => ReferencesArray::from(array_merge(
                $params['app-dev-panel/yii3']['collectors'],
                $params['app-dev-panel/yii3']['collectors.console'] ?? [],
            )),
            'ignoreConfig' => new DebuggerIgnoreConfig(
                commands: $params['app-dev-panel/yii3']['ignoredCommands'],
            ),
        ],
    ],
    ConsoleAppInfoCollector::class => [
        '__construct()' => [
            'adapterName' => 'Yii3',
        ],
    ],
    CollectorRepositoryInterface::class => static fn (StorageInterface $storage) => new CollectorRepository($storage),
    DebugServerCommand::class => [
        '__construct()' => [
            'address' => $params['app-dev-panel/yii3']['devServer']['address'],
            'port' => $params['app-dev-panel/yii3']['devServer']['port'],
        ],
    ],
    InspectRoutesCommand::class => static fn() => new InspectRoutesCommand(),
    InspectConfigCommand::class => static fn(ContainerInterface $container) => new InspectConfigCommand(
        $container,
        $params,
    ),
];
