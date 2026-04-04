<?php

declare(strict_types=1);

use AppDevPanel\Adapter\Yii3\Proxy\ContainerInterfaceProxy;
use AppDevPanel\Adapter\Yii3\Proxy\ContainerProxyConfig;
use AppDevPanel\Kernel\Collector\LogCollector;
use AppDevPanel\Kernel\Collector\LoggerInterfaceProxy;
use AppDevPanel\Kernel\Collector\ServiceCollector;
use AppDevPanel\Kernel\Collector\Stream\FilesystemStreamCollector;
use AppDevPanel\Kernel\DebuggerIdGenerator;
use AppDevPanel\Kernel\DebugServer\LoggerDecorator;
use AppDevPanel\Kernel\Storage\SqliteStorage;
use AppDevPanel\Kernel\Storage\StorageInterface;
use Composer\Autoload\ClassLoader;
use Psr\Container\ContainerInterface;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Log\LoggerInterface;
use Yiisoft\Aliases\Aliases;
use Yiisoft\VarDumper\ClosureExporter;
use Yiisoft\VarDumper\UseStatementParser;

/**
 * @var array $params
 */

$common = [
    StorageInterface::class => static function (ContainerInterface $container, Aliases $aliases) use ($params) {
        $params = $params['app-dev-panel/yii3'];
        $debuggerIdGenerator = $container->get(DebuggerIdGenerator::class);
        $excludedClasses = $params['dumper.excludedClasses'];
        $storage = new SqliteStorage(
            $aliases->get($params['path']) . '/debug.db',
            $debuggerIdGenerator,
            $excludedClasses,
        );

        if (isset($params['historySize'])) {
            $storage->setHistorySize((int) $params['historySize']);
        }

        return $storage;
    },
];

require_once __DIR__ . '/helpers.php';

if (!isAppDevPanelEnabled($params)) {
    return $common;
}

$otelDefs = [];
if (interface_exists(\OpenTelemetry\SDK\Trace\SpanProcessorInterface::class)) {
    $otelDefs[\OpenTelemetry\SDK\Trace\SpanProcessorInterface::class] = static fn(
        ContainerInterface $container,
        \OpenTelemetry\SDK\Trace\SpanProcessorInterface $processor,
    ) => new \AppDevPanel\Kernel\Collector\SpanProcessorInterfaceProxy(
        $processor,
        $container->get(\AppDevPanel\Kernel\Collector\OpenTelemetryCollector::class),
    );
}

return array_merge(
    $otelDefs,
    [
        ContainerProxyConfig::class => static function (ContainerInterface $container) use ($params) {
            $params = $params['app-dev-panel/yii3'];
            $collector = $container->get(ServiceCollector::class);
            $dispatcher = $container->get(EventDispatcherInterface::class);
            $isDebuggerEnabled = (bool) ($params['enabled'] ?? false);
            $isDevServerEnabled = (bool) ($params['devServer']['enabled'] ?? false);

            $trackedServices = (array) ($params['trackedServices'] ?? []);

            if ($isDevServerEnabled) {
                $trackedServices[LoggerInterface::class] = static fn(
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
                $logLevel,
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
                    SqliteStorage::class,
                    ClassLoader::class,
                ],
            ],
        ],
    ],
    $common,
);
