<?php

declare(strict_types=1);

use Composer\Autoload\ClassLoader;
use Psr\Container\ContainerInterface;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Log\LoggerInterface;
use Yiisoft\Aliases\Aliases;
use Yiisoft\VarDumper\ClosureExporter;
use Yiisoft\VarDumper\UseStatementParser;
use Yiisoft\Yii\Debug\Collector\ContainerInterfaceProxy;
use Yiisoft\Yii\Debug\Collector\ContainerProxyConfig;
use Yiisoft\Yii\Debug\Collector\LogCollector;
use Yiisoft\Yii\Debug\Collector\LoggerInterfaceProxy;
use Yiisoft\Yii\Debug\Collector\ServiceCollector;
use Yiisoft\Yii\Debug\Collector\Stream\FilesystemStreamCollector;
use Yiisoft\Yii\Debug\DebuggerIdGenerator;
use Yiisoft\Yii\Debug\DebugServer\LoggerDecorator;
use Yiisoft\Yii\Debug\Storage\FileStorage;
use Yiisoft\Yii\Debug\Storage\StorageInterface;

/**
 * @var array $params
 */

$common = [
    StorageInterface::class => static function (ContainerInterface $container, Aliases $aliases) use ($params) {
        $params = $params['yiisoft/yii-debug'];
        $debuggerIdGenerator = $container->get(DebuggerIdGenerator::class);
        $excludedClasses = $params['dumper.excludedClasses'];
        $fileStorage = new FileStorage($aliases->get($params['path']), $debuggerIdGenerator, $excludedClasses);

        if (isset($params['historySize'])) {
            $fileStorage->setHistorySize((int) $params['historySize']);
        }

        return $fileStorage;
    },
];

if (!(bool) ($params['yiisoft/yii-debug']['enabled'] ?? false)) {
    return $common;
}

return array_merge([
    ContainerProxyConfig::class => static function (ContainerInterface $container) use ($params) {
        $params = $params['yiisoft/yii-debug'];
        $collector = $container->get(ServiceCollector::class);
        $dispatcher = $container->get(EventDispatcherInterface::class);
        $isDebuggerEnabled = (bool) ($params['enabled'] ?? false);
        $isDevServerEnabled = (bool) ($params['devServer']['enabled'] ?? false);

        $trackedServices = (array) ($params['trackedServices'] ?? []);

        if ($isDevServerEnabled) {
            $trackedServices[LoggerInterface::class] = static fn (
                ContainerInterface $container,
                LoggerInterface $logger,
            ) => new LoggerInterfaceProxy(new LoggerDecorator($logger), $container->get(LogCollector::class));
        }

        $path = $container->get(Aliases::class)->get('@runtime/cache/container-proxy');
        $logLevel = $params['logLevel'] ?? ContainerInterfaceProxy::LOG_NOTHING;

        return new ContainerProxyConfig(
            $isDebuggerEnabled,
            $trackedServices,
            $dispatcher,
            $collector,
            $path,
            $logLevel
        );
    },
    FilesystemStreamCollector::class => [
        '__construct()' => [
            'ignoredPathPatterns' => [
                /**
                 * Examples:
                 * - templates/
                 * - src/Directory/To/Ignore
                 */
            ],
            'ignoredClasses' => [
                ClosureExporter::class,
                UseStatementParser::class,
                FileStorage::class,
                ClassLoader::class,
            ],
        ],
    ],
], $common);
