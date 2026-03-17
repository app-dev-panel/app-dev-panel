<?php

declare(strict_types=1);

/**
 * DI definitions for the ADP API bridge.
 * Registers all API services into Yii's DI container, delegating to the framework-agnostic ApiApplication.
 */

use AppDevPanel\Adapter\Yiisoft\Api\AliasPathResolver;
use AppDevPanel\Adapter\Yiisoft\Api\YiiApiMiddleware;
use AppDevPanel\Api\ApiApplication;
use AppDevPanel\Api\Debug\Controller\DebugController;
use AppDevPanel\Api\Debug\Middleware\ResponseDataWrapper;
use AppDevPanel\Api\Debug\Middleware\TokenAuthMiddleware;
use AppDevPanel\Api\Debug\Repository\CollectorRepository;
use AppDevPanel\Api\Debug\Repository\CollectorRepositoryInterface;
use AppDevPanel\Api\Http\JsonResponseFactory;
use AppDevPanel\Api\Http\JsonResponseFactoryInterface;
use AppDevPanel\Api\Ingestion\Controller\IngestionController;
use AppDevPanel\Api\Inspector\Controller\CacheController;
use AppDevPanel\Api\Inspector\Controller\CommandController;
use AppDevPanel\Api\Inspector\Controller\ComposerController;
use AppDevPanel\Api\Inspector\Controller\FileController;
use AppDevPanel\Api\Inspector\Controller\GitController;
use AppDevPanel\Api\Inspector\Controller\GitRepositoryProvider;
use AppDevPanel\Api\Inspector\Controller\InspectController;
use AppDevPanel\Api\Inspector\Controller\OpcacheController;
use AppDevPanel\Api\Inspector\Controller\RequestController;
use AppDevPanel\Api\Inspector\Controller\RoutingController;
use AppDevPanel\Api\Inspector\Controller\ServiceController;
use AppDevPanel\Api\Inspector\Controller\TranslationController;
use AppDevPanel\Api\Inspector\Middleware\InspectorProxyMiddleware;
use AppDevPanel\Api\Middleware\IpFilterMiddleware;
use AppDevPanel\Api\PathResolverInterface;
use AppDevPanel\Kernel\Service\FileServiceRegistry;
use AppDevPanel\Kernel\Service\ServiceRegistryInterface;
use AppDevPanel\Kernel\Storage\StorageInterface;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\HttpFactory;
use Psr\Container\ContainerInterface;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Message\UriFactoryInterface;
use Yiisoft\Aliases\Aliases;

/** @var array $params */

require_once __DIR__ . '/helpers.php';

if (!isAppDevPanelEnabled($params)) {
    return [];
}

$apiConfig = $params['app-dev-panel/yii-debug']['api'] ?? [];
if (!($apiConfig['enabled'] ?? true)) {
    return [];
}

$allowedIps = $apiConfig['allowedIps'] ?? ['127.0.0.1', '::1'];
$authToken = $apiConfig['authToken'] ?? '';

return [
    // PSR-17 factories
    ResponseFactoryInterface::class => static fn () => new HttpFactory(),
    StreamFactoryInterface::class => static fn () => new HttpFactory(),
    UriFactoryInterface::class => static fn () => new HttpFactory(),

    // PSR-18 HTTP client
    ClientInterface::class => static fn () => new Client(['timeout' => 10]),

    // Path resolver
    PathResolverInterface::class => static fn (Aliases $aliases) => new AliasPathResolver($aliases),

    // JSON response factory
    JsonResponseFactoryInterface::class => static fn (
        ResponseFactoryInterface $responseFactory,
        StreamFactoryInterface $streamFactory,
    ) => new JsonResponseFactory($responseFactory, $streamFactory),

    // Service registry
    ServiceRegistryInterface::class => static fn (ContainerInterface $container) => new FileServiceRegistry(
        $container->get(Aliases::class)->get(
            $params['app-dev-panel/yii-debug']['path'] ?? '@runtime/debug',
        ) . '/services',
    ),

    // Collector repository
    CollectorRepositoryInterface::class => static fn (StorageInterface $storage) => new CollectorRepository($storage),

    // Middleware
    IpFilterMiddleware::class => static fn (
        ResponseFactoryInterface $responseFactory,
        StreamFactoryInterface $streamFactory,
    ) => new IpFilterMiddleware($responseFactory, $streamFactory, $allowedIps),

    TokenAuthMiddleware::class => static fn (
        ResponseFactoryInterface $responseFactory,
        StreamFactoryInterface $streamFactory,
    ) => new TokenAuthMiddleware($responseFactory, $streamFactory, $authToken),

    ResponseDataWrapper::class => static fn (
        JsonResponseFactoryInterface $jsonResponseFactory,
    ) => new ResponseDataWrapper($jsonResponseFactory),

    InspectorProxyMiddleware::class => static fn (
        ServiceRegistryInterface $serviceRegistry,
        ClientInterface $httpClient,
        ResponseFactoryInterface $responseFactory,
        StreamFactoryInterface $streamFactory,
        UriFactoryInterface $uriFactory,
    ) => new InspectorProxyMiddleware($serviceRegistry, $httpClient, $responseFactory, $streamFactory, $uriFactory),

    // Controllers
    DebugController::class => static fn (
        JsonResponseFactoryInterface $jsonResponseFactory,
        CollectorRepositoryInterface $collectorRepository,
        StorageInterface $storage,
        ResponseFactoryInterface $responseFactory,
    ) => new DebugController($jsonResponseFactory, $collectorRepository, $storage, $responseFactory),

    IngestionController::class => static fn (
        JsonResponseFactoryInterface $jsonResponseFactory,
        StorageInterface $storage,
    ) => new IngestionController($jsonResponseFactory, $storage),

    ServiceController::class => static fn (
        JsonResponseFactoryInterface $jsonResponseFactory,
        ServiceRegistryInterface $serviceRegistry,
    ) => new ServiceController($jsonResponseFactory, $serviceRegistry),

    FileController::class => static fn (
        JsonResponseFactoryInterface $jsonResponseFactory,
        PathResolverInterface $pathResolver,
    ) => new FileController($jsonResponseFactory, $pathResolver),

    GitRepositoryProvider::class => static fn (
        PathResolverInterface $pathResolver,
    ) => new GitRepositoryProvider($pathResolver),

    GitController::class => static fn (
        JsonResponseFactoryInterface $jsonResponseFactory,
        GitRepositoryProvider $gitRepositoryProvider,
    ) => new GitController($jsonResponseFactory, $gitRepositoryProvider),

    ComposerController::class => static fn (
        JsonResponseFactoryInterface $jsonResponseFactory,
        PathResolverInterface $pathResolver,
    ) => new ComposerController($jsonResponseFactory, $pathResolver),

    OpcacheController::class => static fn (
        JsonResponseFactoryInterface $jsonResponseFactory,
    ) => new OpcacheController($jsonResponseFactory),

    InspectController::class => static fn (
        JsonResponseFactoryInterface $jsonResponseFactory,
        ContainerInterface $container,
    ) => new InspectController($jsonResponseFactory, $container),

    CacheController::class => static fn (
        JsonResponseFactoryInterface $jsonResponseFactory,
        ContainerInterface $container,
    ) => new CacheController($jsonResponseFactory, $container),

    TranslationController::class => static fn (
        JsonResponseFactoryInterface $jsonResponseFactory,
        ContainerInterface $container,
    ) => new TranslationController($jsonResponseFactory, null, $container),

    CommandController::class => static function (
        JsonResponseFactoryInterface $jsonResponseFactory,
        PathResolverInterface $pathResolver,
        ContainerInterface $container,
    ) use ($params): CommandController {
        $commandMap = $params['app-dev-panel/yii-debug']['api']['commandMap'] ?? [];
        return new CommandController($jsonResponseFactory, $pathResolver, $container, $commandMap);
    },

    RoutingController::class => static fn (
        JsonResponseFactoryInterface $jsonResponseFactory,
    ) => new RoutingController($jsonResponseFactory),

    RequestController::class => static fn (
        JsonResponseFactoryInterface $jsonResponseFactory,
        CollectorRepositoryInterface $collectorRepository,
    ) => new RequestController($jsonResponseFactory, $collectorRepository),

    // ApiApplication
    ApiApplication::class => static fn (
        ContainerInterface $container,
        ResponseFactoryInterface $responseFactory,
        StreamFactoryInterface $streamFactory,
    ) => new ApiApplication($container, $responseFactory, $streamFactory),

    // Bridge middleware
    YiiApiMiddleware::class => static fn (
        ApiApplication $apiApplication,
    ) => new YiiApiMiddleware($apiApplication),
];
