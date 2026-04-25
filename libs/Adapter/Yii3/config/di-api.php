<?php

declare(strict_types=1);

/**
 * DI definitions for the ADP API bridge.
 * Registers all API services into Yii's DI container, delegating to the framework-agnostic ApiApplication.
 */

use AppDevPanel\Adapter\Yii3\Api\AliasPathResolver;
use AppDevPanel\Adapter\Yii3\Api\ToolbarMiddleware;
use AppDevPanel\Adapter\Yii3\Api\YiiApiMiddleware;
use AppDevPanel\Adapter\Yii3\Inspector\DbSchemaProvider;
use AppDevPanel\Adapter\Yii3\Inspector\Yii3AuthorizationConfigProvider;
use AppDevPanel\Adapter\Yii3\Inspector\Yii3ConfigProvider;
use AppDevPanel\Api\ApiApplication;
use AppDevPanel\Api\Debug\Controller\DebugController;
use AppDevPanel\Api\Debug\Controller\SettingsController;
use AppDevPanel\Api\Debug\Middleware\ResponseDataWrapper;
use AppDevPanel\Api\Debug\Middleware\TokenAuthMiddleware;
use AppDevPanel\Api\Debug\Repository\CollectorRepository;
use AppDevPanel\Api\Debug\Repository\CollectorRepositoryInterface;
use AppDevPanel\Api\Http\JsonResponseFactory;
use AppDevPanel\Api\Http\JsonResponseFactoryInterface;
use AppDevPanel\Api\Ingestion\Controller\IngestionController;
use AppDevPanel\Api\Ingestion\Controller\OtlpController;
use AppDevPanel\Api\Inspector\Authorization\AuthorizationConfigProviderInterface;
use AppDevPanel\Api\Inspector\Controller\AuthorizationController;
use AppDevPanel\Api\Inspector\Controller\CacheController;
use AppDevPanel\Api\Inspector\Controller\CommandController;
use AppDevPanel\Api\Inspector\Controller\ComposerController;
use AppDevPanel\Api\Inspector\Controller\DatabaseController;
use AppDevPanel\Api\Inspector\Controller\ElasticsearchController;
use AppDevPanel\Api\Inspector\Controller\FileController;
use AppDevPanel\Api\Inspector\Controller\GitController;
use AppDevPanel\Api\Inspector\Controller\GitRepositoryProvider;
use AppDevPanel\Api\Inspector\Controller\HttpMockController;
use AppDevPanel\Api\Inspector\Controller\InspectController;
use AppDevPanel\Api\Inspector\Controller\OpcacheController;
use AppDevPanel\Api\Inspector\Controller\RequestController;
use AppDevPanel\Api\Inspector\Controller\RoutingController;
use AppDevPanel\Api\Inspector\Controller\ServiceController;
use AppDevPanel\Api\Inspector\Controller\TranslationController;
use AppDevPanel\Api\Inspector\Database\NullSchemaProvider;
use AppDevPanel\Api\Inspector\Database\SchemaProviderInterface;
use AppDevPanel\Api\Inspector\Elasticsearch\ElasticsearchProviderInterface;
use AppDevPanel\Api\Inspector\Elasticsearch\NullElasticsearchProvider;
use AppDevPanel\Api\Inspector\HttpMock\HttpMockProviderInterface;
use AppDevPanel\Api\Inspector\HttpMock\NullHttpMockProvider;
use AppDevPanel\Api\Inspector\Middleware\InspectorProxyMiddleware;
use AppDevPanel\Api\Llm\Acp\AcpCommandVerifier;
use AppDevPanel\Api\Llm\Acp\AcpCommandVerifierInterface;
use AppDevPanel\Api\Llm\Acp\AcpDaemonManager;
use AppDevPanel\Api\Llm\Acp\AcpDaemonManagerInterface;
use AppDevPanel\Api\Llm\Controller\LlmController;
use AppDevPanel\Api\Llm\FileLlmHistoryStorage;
use AppDevPanel\Api\Llm\FileLlmSettings;
use AppDevPanel\Api\Llm\LlmHistoryStorageInterface;
use AppDevPanel\Api\Llm\LlmProviderService;
use AppDevPanel\Api\Llm\LlmSettingsInterface;
use AppDevPanel\Api\Mcp\Controller\McpController;
use AppDevPanel\Api\Mcp\Controller\McpSettingsController;
use AppDevPanel\Api\Mcp\McpSettings;
use AppDevPanel\Api\Middleware\IpFilterMiddleware;
use AppDevPanel\Api\NullPathMapper;
use AppDevPanel\Api\Panel\PanelConfig;
use AppDevPanel\Api\Panel\PanelController;
use AppDevPanel\Api\PathMapper;
use AppDevPanel\Api\PathMapperInterface;
use AppDevPanel\Api\PathResolverInterface;
use AppDevPanel\Api\Toolbar\ToolbarConfig;
use AppDevPanel\Api\Toolbar\ToolbarInjector;
use AppDevPanel\Kernel\DebuggerIdGenerator;
use AppDevPanel\Kernel\Service\FileServiceRegistry;
use AppDevPanel\Kernel\Service\ServiceRegistryInterface;
use AppDevPanel\Kernel\Storage\StorageInterface;
use AppDevPanel\McpServer\Inspector\InspectorClient;
use AppDevPanel\McpServer\McpServer;
use AppDevPanel\McpServer\McpToolRegistryFactory;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\HttpFactory;
use Psr\Container\ContainerInterface;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Message\UriFactoryInterface;
use Psr\Log\LoggerInterface;
use Yiisoft\Aliases\Aliases;

/** @var array $params */

require_once __DIR__ . '/helpers.php';

if (!isAppDevPanelEnabled($params)) {
    return [];
}

$apiConfig = $params['app-dev-panel/yii3']['api'] ?? [];
if (!($apiConfig['enabled'] ?? true)) {
    return [];
}

$allowedIps = $apiConfig['allowedIps'] ?? ['127.0.0.1', '::1'];
$authToken = $apiConfig['authToken'] ?? '';
$inspectorUrl = $apiConfig['inspectorUrl'] ?? null;

return [
    // Alias so framework-agnostic inspector controllers can fetch the merged config
    // via $container->has('config') / get('config'). Yii 3 registers the merged
    // configuration under ConfigInterface::class; wrap it in Yii3ConfigProvider so
    // event listener callables (closures, [class, method] tuples) are serialised
    // into the structured shape the inspector frontend expects.
    'config' => static fn(\Yiisoft\Config\ConfigInterface $config) => new Yii3ConfigProvider($config),

    // PSR-17 factories
    RequestFactoryInterface::class => static fn() => new HttpFactory(),
    ResponseFactoryInterface::class => static fn() => new HttpFactory(),
    StreamFactoryInterface::class => static fn() => new HttpFactory(),
    UriFactoryInterface::class => static fn() => new HttpFactory(),

    // PSR-18 HTTP client
    ClientInterface::class => static fn() => new Client(['timeout' => 10]),

    // Path resolver
    PathResolverInterface::class => static fn(Aliases $aliases) => new AliasPathResolver($aliases),

    // Path mapper (remote/container ↔ local/host)
    PathMapperInterface::class => static function () use ($params): PathMapperInterface {
        $rules = $params['app-dev-panel/yii3']['pathMapping'] ?? [];
        return $rules !== [] ? new PathMapper($rules) : new NullPathMapper();
    },

    // JSON response factory
    JsonResponseFactoryInterface::class => static fn(
        ResponseFactoryInterface $responseFactory,
        StreamFactoryInterface $streamFactory,
    ) => new JsonResponseFactory($responseFactory, $streamFactory),

    // Service registry
    ServiceRegistryInterface::class => static fn(ContainerInterface $container) => new FileServiceRegistry(
        $container->get(Aliases::class)->get($params['app-dev-panel/yii3']['path'] ?? '@runtime/debug') . '/services',
    ),

    // Schema provider (database inspection)
    SchemaProviderInterface::class => static function (ContainerInterface $container): SchemaProviderInterface {
        try {
            $db = $container->get(\Yiisoft\Db\Connection\ConnectionInterface::class);
            return new DbSchemaProvider($db);
        } catch (\Throwable) {
            return new NullSchemaProvider();
        }
    },

    // Database controller
    DatabaseController::class => static fn(
        JsonResponseFactoryInterface $jsonResponseFactory,
        SchemaProviderInterface $schemaProvider,
    ) => new DatabaseController($jsonResponseFactory, $schemaProvider),

    // Authorization provider — wires Yii's RBAC/User/Auth services when available,
    // falls back to the no-op provider when the whole `app-dev-panel/yii3` params subtree
    // and none of the optional packages (yiisoft/rbac, yiisoft/user, yiisoft/auth,
    // yiisoft/access) are present.
    AuthorizationConfigProviderInterface::class => static function (ContainerInterface $container) use (
        $params,
    ): AuthorizationConfigProviderInterface {
        $yii3Params = $params['app-dev-panel/yii3'] ?? [];
        return new Yii3AuthorizationConfigProvider($container, is_array($yii3Params) ? $yii3Params : []);
    },

    // Authorization controller
    AuthorizationController::class => static fn(
        JsonResponseFactoryInterface $jsonResponseFactory,
        AuthorizationConfigProviderInterface $authorizationConfigProvider,
    ) => new AuthorizationController($jsonResponseFactory, $authorizationConfigProvider),

    // HTTP mock provider
    HttpMockProviderInterface::class => static fn() => new NullHttpMockProvider(),

    // HTTP mock controller
    HttpMockController::class => static fn(
        JsonResponseFactoryInterface $jsonResponseFactory,
        HttpMockProviderInterface $httpMockProvider,
    ) => new HttpMockController($jsonResponseFactory, $httpMockProvider),

    // Elasticsearch provider
    ElasticsearchProviderInterface::class => static fn() => new NullElasticsearchProvider(),

    // Elasticsearch controller
    ElasticsearchController::class => static fn(
        JsonResponseFactoryInterface $jsonResponseFactory,
        ElasticsearchProviderInterface $elasticsearchProvider,
    ) => new ElasticsearchController($jsonResponseFactory, $elasticsearchProvider),

    // Collector repository
    CollectorRepositoryInterface::class => static fn(StorageInterface $storage) => new CollectorRepository($storage),

    // Middleware
    IpFilterMiddleware::class => static fn(
        ResponseFactoryInterface $responseFactory,
        StreamFactoryInterface $streamFactory,
    ) => new IpFilterMiddleware($responseFactory, $streamFactory, $allowedIps),

    TokenAuthMiddleware::class => static fn(
        ResponseFactoryInterface $responseFactory,
        StreamFactoryInterface $streamFactory,
    ) => new TokenAuthMiddleware($responseFactory, $streamFactory, $authToken),

    ResponseDataWrapper::class =>
        static fn(JsonResponseFactoryInterface $jsonResponseFactory) => new ResponseDataWrapper($jsonResponseFactory),

    InspectorProxyMiddleware::class => static fn(
        ServiceRegistryInterface $serviceRegistry,
        ClientInterface $httpClient,
        ResponseFactoryInterface $responseFactory,
        StreamFactoryInterface $streamFactory,
        UriFactoryInterface $uriFactory,
    ) => new InspectorProxyMiddleware($serviceRegistry, $httpClient, $responseFactory, $streamFactory, $uriFactory),

    // Panel
    PanelConfig::class => static function (ContainerInterface $container) use ($params): PanelConfig {
        $staticUrl = $params['app-dev-panel/yii3']['panel']['staticUrl'] ?? '';
        if ($staticUrl === '') {
            // Auto-detect: if built assets exist in adapter package, symlink them
            $adapterDist = \dirname(__DIR__) . '/resources/dist/bundle.js';
            if (file_exists($adapterDist)) {
                $aliases = $container->get(Aliases::class);
                $webroot = $aliases->get('@public');
                $targetDir = $webroot . '/app-dev-panel';
                if (!is_dir($targetDir)) {
                    @symlink(\dirname($adapterDist), $targetDir);
                }
                if (is_dir($targetDir)) {
                    $staticUrl = '/app-dev-panel';
                }
            }
        }
        return new PanelConfig($staticUrl !== '' ? $staticUrl : PanelConfig::DEFAULT_STATIC_URL);
    },
    PanelController::class => static fn(
        ResponseFactoryInterface $responseFactory,
        StreamFactoryInterface $streamFactory,
        PanelConfig $panelConfig,
    ) => new PanelController($responseFactory, $streamFactory, $panelConfig),

    // Toolbar
    ToolbarConfig::class => static function () use ($params): ToolbarConfig {
        $toolbarParams = $params['app-dev-panel/yii3']['toolbar'] ?? [];
        return new ToolbarConfig(
            enabled: $toolbarParams['enabled'] ?? true,
            staticUrl: $toolbarParams['staticUrl'] ?? '',
        );
    },
    ToolbarInjector::class => static fn(PanelConfig $panelConfig, ToolbarConfig $toolbarConfig) => new ToolbarInjector(
        $panelConfig,
        $toolbarConfig,
    ),
    ToolbarMiddleware::class => static fn(
        ToolbarInjector $toolbarInjector,
        DebuggerIdGenerator $idGenerator,
        StreamFactoryInterface $streamFactory,
    ) => new ToolbarMiddleware($toolbarInjector, $idGenerator, $streamFactory),

    // Controllers
    DebugController::class => static fn(
        JsonResponseFactoryInterface $jsonResponseFactory,
        CollectorRepositoryInterface $collectorRepository,
        StorageInterface $storage,
        ResponseFactoryInterface $responseFactory,
    ) => new DebugController($jsonResponseFactory, $collectorRepository, $storage, $responseFactory),

    IngestionController::class => static fn(
        JsonResponseFactoryInterface $jsonResponseFactory,
        StorageInterface $storage,
    ) => new IngestionController($jsonResponseFactory, $storage),

    ServiceController::class => static fn(
        JsonResponseFactoryInterface $jsonResponseFactory,
        ServiceRegistryInterface $serviceRegistry,
    ) => new ServiceController($jsonResponseFactory, $serviceRegistry),

    FileController::class => static fn(
        JsonResponseFactoryInterface $jsonResponseFactory,
        PathResolverInterface $pathResolver,
        PathMapperInterface $pathMapper,
    ) => new FileController($jsonResponseFactory, $pathResolver, $pathMapper),

    SettingsController::class => static fn(
        JsonResponseFactoryInterface $jsonResponseFactory,
        PathMapperInterface $pathMapper,
    ) => new SettingsController($jsonResponseFactory, $pathMapper),

    GitRepositoryProvider::class => static fn(PathResolverInterface $pathResolver) => new GitRepositoryProvider(
        $pathResolver,
    ),

    GitController::class => static fn(
        JsonResponseFactoryInterface $jsonResponseFactory,
        GitRepositoryProvider $gitRepositoryProvider,
    ) => new GitController($jsonResponseFactory, $gitRepositoryProvider),

    ComposerController::class => static fn(
        JsonResponseFactoryInterface $jsonResponseFactory,
        PathResolverInterface $pathResolver,
    ) => new ComposerController($jsonResponseFactory, $pathResolver),

    OpcacheController::class => static fn(JsonResponseFactoryInterface $jsonResponseFactory) => new OpcacheController(
        $jsonResponseFactory,
    ),

    InspectController::class => static fn(
        JsonResponseFactoryInterface $jsonResponseFactory,
        ContainerInterface $container,
    ) => new InspectController($jsonResponseFactory, $container, $params),

    CacheController::class => static fn(
        JsonResponseFactoryInterface $jsonResponseFactory,
        ContainerInterface $container,
    ) => new CacheController($jsonResponseFactory, $container),

    TranslationController::class => static fn(
        JsonResponseFactoryInterface $jsonResponseFactory,
        LoggerInterface $logger,
        ContainerInterface $container,
    ) => new TranslationController($jsonResponseFactory, $logger, $container, $params),

    CommandController::class => static function (
        JsonResponseFactoryInterface $jsonResponseFactory,
        PathResolverInterface $pathResolver,
        ContainerInterface $container,
    ) use ($params): CommandController {
        $commandMap = $params['app-dev-panel/yii3']['api']['commandMap'] ?? [];
        return new CommandController($jsonResponseFactory, $pathResolver, $container, $commandMap);
    },

    RoutingController::class => static function (
        JsonResponseFactoryInterface $jsonResponseFactory,
        ContainerInterface $container,
    ): RoutingController {
        $routeCollection = $container->has(\Yiisoft\Router\RouteCollectionInterface::class)
            ? $container->get(\Yiisoft\Router\RouteCollectionInterface::class)
            : null;
        $urlMatcher = $container->has(\Yiisoft\Router\UrlMatcherInterface::class)
            ? $container->get(\Yiisoft\Router\UrlMatcherInterface::class)
            : null;

        return new RoutingController($jsonResponseFactory, $routeCollection, $urlMatcher);
    },

    RequestController::class => static fn(
        JsonResponseFactoryInterface $jsonResponseFactory,
        CollectorRepositoryInterface $collectorRepository,
    ) => new RequestController($jsonResponseFactory, $collectorRepository),

    // MCP Server
    McpSettings::class =>
        static fn(ContainerInterface $container) => new McpSettings($container->get(Aliases::class)->get(
            $params['app-dev-panel/yii3']['path'] ?? '@runtime/debug',
        )),

    McpServer::class => static fn(StorageInterface $storage): McpServer => new McpServer(McpToolRegistryFactory::create(
        $storage,
        InspectorClient::fromOptionalUrl($inspectorUrl),
    )),

    McpController::class => static fn(
        JsonResponseFactoryInterface $jsonResponseFactory,
        McpServer $mcpServer,
        McpSettings $mcpSettings,
    ) => new McpController($jsonResponseFactory, $mcpServer, $mcpSettings),

    McpSettingsController::class => static fn(
        JsonResponseFactoryInterface $jsonResponseFactory,
        McpSettings $mcpSettings,
    ) => new McpSettingsController($jsonResponseFactory, $mcpSettings),

    // LLM settings
    LlmSettingsInterface::class =>
        static fn(ContainerInterface $container) => new FileLlmSettings($container->get(Aliases::class)->get(
            $params['app-dev-panel/yii3']['path'] ?? '@runtime/debug',
        )),

    // LLM history storage
    LlmHistoryStorageInterface::class =>
        static fn(ContainerInterface $container) => new FileLlmHistoryStorage($container->get(Aliases::class)->get(
            $params['app-dev-panel/yii3']['path'] ?? '@runtime/debug',
        )),

    // ACP daemon manager and command verifier
    AcpCommandVerifierInterface::class => static fn() => new AcpCommandVerifier(),
    AcpDaemonManagerInterface::class =>
        static fn(ContainerInterface $container) => new AcpDaemonManager($container->get(Aliases::class)->get(
            $params['app-dev-panel/yii3']['path'] ?? '@runtime/debug',
        )),

    // LLM provider service
    LlmProviderService::class => static fn(
        LlmSettingsInterface $llmSettings,
        ClientInterface $httpClient,
        RequestFactoryInterface $requestFactory,
        StreamFactoryInterface $streamFactory,
        AcpDaemonManagerInterface $acpDaemonManager,
    ) => new LlmProviderService($llmSettings, $httpClient, $requestFactory, $streamFactory, $acpDaemonManager),

    // LLM controller
    LlmController::class => static fn(
        JsonResponseFactoryInterface $jsonResponseFactory,
        LlmSettingsInterface $llmSettings,
        LlmProviderService $providerService,
        LlmHistoryStorageInterface $historyStorage,
        RequestFactoryInterface $requestFactory,
        StreamFactoryInterface $streamFactory,
        ClientInterface $httpClient,
        AcpCommandVerifierInterface $commandVerifier,
        AcpDaemonManagerInterface $acpDaemonManager,
    ) => new LlmController(
        $jsonResponseFactory,
        $llmSettings,
        $providerService,
        $historyStorage,
        $requestFactory,
        $streamFactory,
        $httpClient,
        $commandVerifier,
        $acpDaemonManager,
    ),

    // ApiApplication
    ApiApplication::class => static fn(
        ContainerInterface $container,
        ResponseFactoryInterface $responseFactory,
        StreamFactoryInterface $streamFactory,
    ) => new ApiApplication($container, $responseFactory, $streamFactory),

    // Bridge middleware
    YiiApiMiddleware::class => static fn(ApiApplication $apiApplication) => new YiiApiMiddleware($apiApplication),
];
