<?php

declare(strict_types=1);

use AppDevPanel\Adapter\Yii3\Collector\Asset\AssetLoaderInterfaceProxy;
use AppDevPanel\Adapter\Yii3\Collector\Db\ConnectionInterfaceProxy;
use AppDevPanel\Adapter\Yii3\Collector\Mailer\MailerInterfaceProxy;
use AppDevPanel\Adapter\Yii3\Collector\Router\UrlMatcherInterfaceProxy;
use AppDevPanel\Adapter\Yii3\Collector\Translator\TranslatorInterfaceProxy;
use AppDevPanel\Adapter\Yii3\Collector\Validator\ValidatorInterfaceProxy;
use AppDevPanel\Adapter\Yii3\Proxy\ContainerInterfaceProxy;
use AppDevPanel\Cli\Command\DebugDumpCommand;
use AppDevPanel\Cli\Command\DebugQueryCommand;
use AppDevPanel\Cli\Command\DebugResetCommand;
use AppDevPanel\Cli\Command\DebugServerBroadcastCommand;
use AppDevPanel\Cli\Command\DebugServerCommand;
use AppDevPanel\Cli\Command\DebugSummaryCommand;
use AppDevPanel\Cli\Command\DebugTailCommand;
use AppDevPanel\Cli\Command\FrontendUpdateCommand;
use AppDevPanel\Cli\Command\InspectConfigCommand;
use AppDevPanel\Cli\Command\InspectDatabaseCommand;
use AppDevPanel\Cli\Command\InspectRoutesCommand;
use AppDevPanel\Kernel\Collector\AssetBundleCollector;
use AppDevPanel\Kernel\Collector\AuthorizationCollector;
use AppDevPanel\Kernel\Collector\CacheCollector;
use AppDevPanel\Kernel\Collector\CodeCoverageCollector;
use AppDevPanel\Kernel\Collector\Console\CommandCollector;
use AppDevPanel\Kernel\Collector\Console\ConsoleAppInfoCollector;
use AppDevPanel\Kernel\Collector\DatabaseCollector;
use AppDevPanel\Kernel\Collector\DeprecationCollector;
use AppDevPanel\Kernel\Collector\EnvironmentCollector;
use AppDevPanel\Kernel\Collector\EventCollector;
use AppDevPanel\Kernel\Collector\EventDispatcherInterfaceProxy;
use AppDevPanel\Kernel\Collector\ExceptionCollector;
use AppDevPanel\Kernel\Collector\HttpClientCollector;
use AppDevPanel\Kernel\Collector\HttpClientInterfaceProxy;
use AppDevPanel\Kernel\Collector\LogCollector;
use AppDevPanel\Kernel\Collector\LoggerInterfaceProxy;
use AppDevPanel\Kernel\Collector\MailerCollector;
use AppDevPanel\Kernel\Collector\OpenTelemetryCollector;
use AppDevPanel\Kernel\Collector\RouterCollector;
use AppDevPanel\Kernel\Collector\ServiceCollector;
use AppDevPanel\Kernel\Collector\Stream\FilesystemStreamCollector;
use AppDevPanel\Kernel\Collector\Stream\HttpStreamCollector;
use AppDevPanel\Kernel\Collector\TemplateCollector;
use AppDevPanel\Kernel\Collector\TimelineCollector;
use AppDevPanel\Kernel\Collector\TranslatorCollector;
use AppDevPanel\Kernel\Collector\ValidatorCollector;
use AppDevPanel\Kernel\Collector\VarDumperCollector;
use AppDevPanel\Kernel\Collector\Web\RequestCollector;
use AppDevPanel\Kernel\Collector\Web\WebAppInfoCollector;
use Psr\Container\ContainerInterface;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Http\Client\ClientInterface;
use Psr\Log\LoggerInterface;
use Yiisoft\Assets\AssetLoaderInterface;
use Yiisoft\Db\Connection\ConnectionInterface;
use Yiisoft\Injector\Injector;
use Yiisoft\Mailer\MailerInterface as YiisoftMailerInterface;
use Yiisoft\Router\UrlMatcherInterface;
use Yiisoft\Translator\TranslatorInterface as YiisoftTranslatorInterface;
use Yiisoft\Validator\ValidatorInterface;

/**
 * @var $params array
 */

return [
    'app-dev-panel/yii3' => [
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
            CodeCoverageCollector::class,
            DatabaseCollector::class,
            MailerCollector::class,
            TemplateCollector::class,
        ],
        'collectors.web' => [
            WebAppInfoCollector::class,
            RequestCollector::class,
            RouterCollector::class,
            AssetBundleCollector::class,
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
            AssetLoaderInterface::class => [AssetLoaderInterfaceProxy::class, AssetBundleCollector::class],
            ConnectionInterface::class => [ConnectionInterfaceProxy::class, DatabaseCollector::class],
            YiisoftMailerInterface::class => [MailerInterfaceProxy::class, MailerCollector::class],
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
        'storage.driver' => 'sqlite',
        'path' => '@runtime/debug',
        'ignoredRequests' => [
            '/debug/**',
            '/inspect/**',
        ],
        'ignoredCommands' => [
            'completion',
            'help',
            'list',
            'serve',
            'debug:reset',
        ],
        'pathMapping' => [],
        'panel' => [
            'staticUrl' => '', // Base URL for panel assets (empty = GitHub Pages default). Use http://localhost:3000 for Vite dev with HMR.
        ],
        'toolbar' => [
            'enabled' => true, // Inject the debug toolbar into HTML responses.
            'staticUrl' => '', // Base URL for toolbar assets (empty = uses panel staticUrl). Use http://localhost:3001 for Vite dev server.
        ],
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
            'debug:dump' => DebugDumpCommand::class,
            'debug:summary' => DebugSummaryCommand::class,
            'debug:tail' => DebugTailCommand::class,
            'inspect:db' => InspectDatabaseCommand::class,
            'inspect:routes' => InspectRoutesCommand::class,
            'inspect:config' => InspectConfigCommand::class,
            'frontend:update' => FrontendUpdateCommand::class,
            DebugResetCommand::COMMAND_NAME => DebugResetCommand::class,
            DebugServerCommand::COMMAND_NAME => DebugServerCommand::class,
            DebugServerBroadcastCommand::COMMAND_NAME => DebugServerBroadcastCommand::class,
        ],
    ],
];
