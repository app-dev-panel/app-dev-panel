<?php

declare(strict_types=1);

use Cycle\Database\DatabaseProviderInterface;
use Psr\Container\ContainerInterface;
use Yiisoft\Db\Connection\ConnectionInterface;
use Yiisoft\Yii\Debug\Api\Debug\Http\HttpApplicationWrapper;
use Yiisoft\Yii\Debug\Api\Debug\Http\RouteCollectorWrapper;
use Yiisoft\Yii\Debug\Api\Debug\Repository\CollectorRepository;
use Yiisoft\Yii\Debug\Api\Debug\Repository\CollectorRepositoryInterface;
use Yiisoft\Yii\Debug\Api\Inspector\Database\Cycle\CycleSchemaProvider;
use Yiisoft\Yii\Debug\Api\Inspector\Database\Db\DbSchemaProvider;
use Yiisoft\Yii\Debug\Api\Inspector\Database\SchemaProviderInterface;
use Yiisoft\Yii\Debug\Storage\StorageInterface;

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
