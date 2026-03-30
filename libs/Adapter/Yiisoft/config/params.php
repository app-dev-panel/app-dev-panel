<?php

declare(strict_types=1);

use AppDevPanel\Adapter\Yiisoft\Collector\Router\UrlMatcherInterfaceProxy;
use AppDevPanel\Adapter\Yiisoft\Collector\Translator\TranslatorInterfaceProxy;
use AppDevPanel\Adapter\Yiisoft\Collector\Validator\ValidatorInterfaceProxy;
use AppDevPanel\Adapter\Yiisoft\Proxy\ContainerInterfaceProxy;
use AppDevPanel\Cli\Command\DebugQueryCommand;
use AppDevPanel\Cli\Command\DebugResetCommand;
use AppDevPanel\Cli\Command\DebugServerBroadcastCommand;
use AppDevPanel\Cli\Command\DebugServerCommand;
use AppDevPanel\Kernel\Collector\Console\CommandCollector;
use AppDevPanel\Kernel\Collector\Console\ConsoleAppInfoCollector;
use AppDevPanel\Kernel\Collector\DeprecationCollector;
use AppDevPanel\Kernel\Collector\EnvironmentCollector;
use AppDevPanel\Kernel\Collector\EventCollector;
use AppDevPanel\Kernel\Collector\EventDispatcherInterfaceProxy;
use AppDevPanel\Kernel\Collector\ExceptionCollector;
use AppDevPanel\Kernel\Collector\HttpClientCollector;
use AppDevPanel\Kernel\Collector\HttpClientInterfaceProxy;
use AppDevPanel\Kernel\Collector\LogCollector;
use AppDevPanel\Kernel\Collector\LoggerInterfaceProxy;
use AppDevPanel\Kernel\Collector\OpenTelemetryCollector;
use AppDevPanel\Kernel\Collector\RouterCollector;
use AppDevPanel\Kernel\Collector\ServiceCollector;
use AppDevPanel\Kernel\Collector\Stream\FilesystemStreamCollector;
use AppDevPanel\Kernel\Collector\Stream\HttpStreamCollector;
use AppDevPanel\Kernel\Collector\TimelineCollector;
use AppDevPanel\Kernel\Collector\AuthorizationCollector;
use AppDevPanel\Kernel\Collector\TranslatorCollector;
use AppDevPanel\Kernel\Collector\ValidatorCollector;
use AppDevPanel\Kernel\Collector\VarDumperCollector;
use AppDevPanel\Kernel\Collector\Web\RequestCollector;
use AppDevPanel\Kernel\Collector\Web\WebAppInfoCollector;
use Psr\Container\ContainerInterface;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Http\Client\ClientInterface;
use Psr\Log\LoggerInterface;
use Yiisoft\Injector\Injector;
use Yiisoft\Router\UrlMatcherInterface;
use Yiisoft\Translator\TranslatorInterface as YiisoftTranslatorInterface;
use Yiisoft\Validator\ValidatorInterface;

/**
 * @var $params array
 */

return [
    'app-dev-panel/yiisoft' => [
        'enabled' => true,
        'devServer' => [
            'enabled' => true,
            'address' => '0.0.0.0',
            'port' => 8890,
        ],
        'collectors' => [
            EnvironmentCollector::class,
            LogCollector::class,
            EventCollector::class,
            ServiceCollector::class,
            HttpClientCollector::class,
            FilesystemStreamCollector::class,
            HttpStreamCollector::class,
            ExceptionCollector::class,
            DeprecationCollector::class,
            VarDumperCollector::class,
            TimelineCollector::class,
            ValidatorCollector::class,
            TranslatorCollector::class,
            AuthorizationCollector::class,
            OpenTelemetryCollector::class,
        ],
        'collectors.web' => [
            WebAppInfoCollector::class,
            RequestCollector::class,
            RouterCollector::class,
        ],
        'collectors.console' => [
            ConsoleAppInfoCollector::class,
            CommandCollector::class,
        ],
        'trackedServices' => [
            Injector::class => fn(ContainerInterface $container) => new Injector($container),
            LoggerInterface::class => [LoggerInterfaceProxy::class, LogCollector::class],
            EventDispatcherInterface::class => [EventDispatcherInterfaceProxy::class, EventCollector::class],
            ClientInterface::class => [HttpClientInterfaceProxy::class, HttpClientCollector::class],
            UrlMatcherInterface::class => [UrlMatcherInterfaceProxy::class, RouterCollector::class],
            ValidatorInterface::class => [ValidatorInterfaceProxy::class, ValidatorCollector::class],
            YiisoftTranslatorInterface::class => [TranslatorInterfaceProxy::class, TranslatorCollector::class],
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
        'logLevel' =>
            ContainerInterfaceProxy::LOG_ARGUMENTS
                | ContainerInterfaceProxy::LOG_RESULT
                | ContainerInterfaceProxy::LOG_ERROR,
        'path' => '@runtime/debug',
        'ignoredRequests' => [
            '/debug/api/**',
            '/inspect/api/**',
        ],
        'ignoredCommands' => [
            'completion',
            'help',
            'list',
            'serve',
            'debug:reset',
        ],
        'pathMapping' => [],
        'api' => [
            'enabled' => true,
            'allowedIps' => ['127.0.0.1', '::1'],
            'authToken' => '',
            'commandMap' => [],
        ],
    ],
    'yiisoft/yii-console' => [
        'commands' => [
            'debug:query' => DebugQueryCommand::class,
            DebugResetCommand::COMMAND_NAME => DebugResetCommand::class,
            DebugServerCommand::COMMAND_NAME => DebugServerCommand::class,
            DebugServerBroadcastCommand::COMMAND_NAME => DebugServerBroadcastCommand::class,
        ],
    ],
];
