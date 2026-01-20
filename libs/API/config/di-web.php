<?php

declare(strict_types=1);

use Cycle\Database\DatabaseProviderInterface;
use Psr\Container\ContainerInterface;
use Yiisoft\Db\Connection\ConnectionInterface;
use AppDevPanel\Adapter\Yiisoft\Api\Debug\Http\HttpApplicationWrapper;
use AppDevPanel\Adapter\Yiisoft\Api\Debug\Http\RouteCollectorWrapper;
use AppDevPanel\Adapter\Yiisoft\Api\Debug\Repository\CollectorRepository;
use AppDevPanel\Adapter\Yiisoft\Api\Debug\Repository\CollectorRepositoryInterface;
use AppDevPanel\Adapter\Yiisoft\Api\Inspector\Database\Cycle\CycleSchemaProvider;
use AppDevPanel\Adapter\Yiisoft\Api\Inspector\Database\Db\DbSchemaProvider;
use AppDevPanel\Adapter\Yiisoft\Api\Inspector\Database\SchemaProviderInterface;
use AppDevPanel\Adapter\Yiisoft\Storage\StorageInterface;

/**
 * @var $params array
 */

return [
    CollectorRepositoryInterface::class => static fn (StorageInterface $storage) => new CollectorRepository($storage),
    SchemaProviderInterface::class => function (ContainerInterface $container) {
        if ($container->has(DatabaseProviderInterface::class)) {
            return $container->get(CycleSchemaProvider::class);
        }

        if ($container->has(ConnectionInterface::class)) {
            return $container->get(DbSchemaProvider::class);
        }

        throw new LogicException(
            sprintf(
                'Inspecting database is not available. Configure "%s" service to be able to inspect database.',
                ConnectionInterface::class,
            )
        );
    },
    HttpApplicationWrapper::class => [
        '__construct()' => [
            'middlewareDefinitions' => $params['yiisoft/yii-debug-api']['middlewares'],
        ],
    ],
    RouteCollectorWrapper::class => [
        '__construct()' => [
            'middlewareDefinitions' => $params['yiisoft/yii-debug-api']['middlewares'],
        ],
    ],
];
