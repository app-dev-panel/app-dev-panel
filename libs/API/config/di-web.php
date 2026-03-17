<?php

declare(strict_types=1);

use Cycle\Database\DatabaseProviderInterface;
use GuzzleHttp\Client;
use Psr\Container\ContainerInterface;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Yiisoft\Db\Connection\ConnectionInterface;
use AppDevPanel\Api\Debug\Http\HttpApplicationWrapper;
use AppDevPanel\Api\Debug\Http\RouteCollectorWrapper;
use AppDevPanel\Api\Debug\Middleware\TokenAuthMiddleware;
use AppDevPanel\Api\Debug\Repository\CollectorRepository;
use AppDevPanel\Api\Debug\Repository\CollectorRepositoryInterface;
use AppDevPanel\Api\Inspector\Controller\InspectController;
use AppDevPanel\Api\Inspector\Controller\RequestController;
use AppDevPanel\Api\Inspector\Controller\TranslationController;
use AppDevPanel\Api\Inspector\Database\Cycle\CycleSchemaProvider;
use AppDevPanel\Api\Inspector\Database\Db\DbSchemaProvider;
use AppDevPanel\Api\Inspector\Database\SchemaProviderInterface;
use AppDevPanel\Kernel\Service\FileServiceRegistry;
use AppDevPanel\Kernel\Service\ServiceRegistryInterface;
use AppDevPanel\Kernel\Storage\StorageInterface;

/**
 * @var $params array
 */

return [
    ClientInterface::class => static fn (): ClientInterface => new Client(['timeout' => 10]),
    CollectorRepositoryInterface::class => static fn (StorageInterface $storage) => new CollectorRepository($storage),
    ServiceRegistryInterface::class => static function (ContainerInterface $container) use ($params) {
        $storagePath = $params['app-dev-panel/yii-debug-api']['path'] ?? sys_get_temp_dir() . '/adp-services';
        return new FileServiceRegistry($storagePath);
    },
    TokenAuthMiddleware::class => static function (
        ResponseFactoryInterface $responseFactory,
        StreamFactoryInterface $streamFactory,
    ) use ($params): TokenAuthMiddleware {
        $token = (string) ($params['app-dev-panel/yii-debug-api']['authToken'] ?? '');
        return new TokenAuthMiddleware($responseFactory, $streamFactory, $token);
    },
    InspectController::class => [
        '__construct()' => [
            'params' => $params,
        ],
    ],
    TranslationController::class => [
        '__construct()' => [
            'params' => $params,
        ],
    ],
    RequestController::class => [
        '__construct()' => [
            'allowedHosts' => $params['app-dev-panel/yii-debug-api']['requestReplay']['allowedHosts'] ?? [],
        ],
    ],
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
            'middlewareDefinitions' => $params['app-dev-panel/yii-debug-api']['middlewares'],
        ],
    ],
    RouteCollectorWrapper::class => [
        '__construct()' => [
            'middlewareDefinitions' => $params['app-dev-panel/yii-debug-api']['middlewares'],
        ],
    ],
];
