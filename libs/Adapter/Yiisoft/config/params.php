<?php

declare(strict_types=1);

use Psr\Container\ContainerInterface;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Http\Client\ClientInterface;
use Psr\Log\LoggerInterface;
use Yiisoft\Injector\Injector;
use Yiisoft\Yii\Debug\Collector\Console\CommandCollector;
use Yiisoft\Yii\Debug\Collector\Console\ConsoleAppInfoCollector;
use Yiisoft\Yii\Debug\Collector\ContainerInterfaceProxy;
use Yiisoft\Yii\Debug\Collector\EventCollector;
use Yiisoft\Yii\Debug\Collector\EventDispatcherInterfaceProxy;
use Yiisoft\Yii\Debug\Collector\ExceptionCollector;
use Yiisoft\Yii\Debug\Collector\HttpClientCollector;
use Yiisoft\Yii\Debug\Collector\HttpClientInterfaceProxy;
use Yiisoft\Yii\Debug\Collector\LogCollector;
use Yiisoft\Yii\Debug\Collector\LoggerInterfaceProxy;
use Yiisoft\Yii\Debug\Collector\ServiceCollector;
use Yiisoft\Yii\Debug\Collector\Stream\FilesystemStreamCollector;
use Yiisoft\Yii\Debug\Collector\Stream\HttpStreamCollector;
use Yiisoft\Yii\Debug\Collector\TimelineCollector;
use Yiisoft\Yii\Debug\Collector\VarDumperCollector;
use Yiisoft\Yii\Debug\Collector\Web\RequestCollector;
use Yiisoft\Yii\Debug\Collector\Web\WebAppInfoCollector;
use Yiisoft\Yii\Debug\Command\DebugResetCommand;
use Yiisoft\Yii\Debug\Command\DebugServerBroadcastCommand;
use Yiisoft\Yii\Debug\Command\DebugServerCommand;

/**
 * @var $params array
 */

return [
    'yiisoft/yii-debug' => [
        'enabled' => true,
        'devServer' => [
            'enabled' => true,
        ],
        'collectors' => [
            LogCollector::class,
            EventCollector::class,
            ServiceCollector::class,
            HttpClientCollector::class,
            FilesystemStreamCollector::class,
            HttpStreamCollector::class,
            ExceptionCollector::class,
            VarDumperCollector::class,
            TimelineCollector::class,
        ],
        'collectors.web' => [
            WebAppInfoCollector::class,
            RequestCollector::class,
        ],
        'collectors.console' => [
            ConsoleAppInfoCollector::class,
            CommandCollector::class,
        ],
        'trackedServices' => [
            Injector::class => fn (ContainerInterface $container) => new Injector($container),
            LoggerInterface::class => [LoggerInterfaceProxy::class, LogCollector::class],
            EventDispatcherInterface::class => [EventDispatcherInterfaceProxy::class, EventCollector::class],
            ClientInterface::class => [HttpClientInterfaceProxy::class, HttpClientCollector::class],
        ],
        'dumper.excludedClasses' => [
            'PhpParser\\Parser\\Php7',
            'PhpParser\\NodeTraverser',
            'PhpParser\\NodeVisitor\\NameResolver',
            'PhpParser\\NameContext',
            'PhpParser\\Node\\Name',
            'PhpParser\\ErrorHandler\\Throwing',
            'Spiral\\Attributes\\Internal\\AttributeParser',
            'Doctrine\\Inflector\\Rules\\Pattern',
            'Doctrine\\Inflector\\Rules\\Word',
            'Doctrine\\Inflector\\Rules\\Substitution',
            'Doctrine\\Inflector\\Rules\\Transformation',
        ],
        'logLevel' => ContainerInterfaceProxy::LOG_ARGUMENTS | ContainerInterfaceProxy::LOG_RESULT | ContainerInterfaceProxy::LOG_ERROR,
        'path' => '@runtime/debug',
        'ignoredRequests' => [
            // Paths to ignore the debugger, e.g.:
            //'/assets/*',
        ],
        'ignoredCommands' => [
            'completion',
            'help',
            'list',
            'serve',
            'debug/reset',
        ],
    ],
    'yiisoft/yii-console' => [
        'commands' => [
            'debug:reset' => DebugResetCommand::class,
            DebugServerCommand::COMMAND_NAME => DebugServerCommand::class,
            DebugServerBroadcastCommand::COMMAND_NAME => DebugServerBroadcastCommand::class,
        ],
    ],
];
