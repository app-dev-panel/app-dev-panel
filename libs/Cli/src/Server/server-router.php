<?php

declare(strict_types=1);

/**
 * PHP built-in server router for standalone ADP API.
 *
 * This script:
 * 1. Converts PHP superglobals to a PSR-7 ServerRequest
 * 2. Routes API requests through ApiApplication
 * 3. Serves frontend static files if configured
 * 4. Emits the PSR-7 response back to the client
 */

// Find autoloader
$autoloadPaths = [
    __DIR__ . '/../../../../vendor/autoload.php', // From libs/Cli/src/Server/
    __DIR__ . '/../../../vendor/autoload.php', // Alternative
    __DIR__ . '/../../vendor/autoload.php', // Alternative
];

$autoloaded = false;
foreach ($autoloadPaths as $autoloadPath) {
    if (!file_exists($autoloadPath)) {
        continue;
    }
    require $autoloadPath;
    $autoloaded = true;
    break;
}

if (!$autoloaded) {
    http_response_code(500);
    echo json_encode(['error' => 'Autoloader not found. Run composer install.']);
    return;
}

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
use AppDevPanel\Api\Inspector\Controller\DatabaseController;
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
use AppDevPanel\Api\Mcp\Controller\McpController;
use AppDevPanel\Api\Mcp\Controller\McpSettingsController;
use AppDevPanel\Api\Mcp\McpSettings;
use AppDevPanel\Api\Middleware\IpFilterMiddleware;
use AppDevPanel\Api\PathResolver;
use AppDevPanel\Api\PathResolverInterface;
use AppDevPanel\Kernel\DebuggerIdGenerator;
use AppDevPanel\Kernel\Service\FileServiceRegistry;
use AppDevPanel\Kernel\Service\ServiceRegistryInterface;
use AppDevPanel\Kernel\Storage\SqliteStorage;
use AppDevPanel\Kernel\Storage\StorageInterface;
use AppDevPanel\McpServer\McpServer;
use AppDevPanel\McpServer\McpToolRegistryFactory;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\HttpFactory;
use GuzzleHttp\Psr7\ServerRequest;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Message\UriFactoryInterface;

// Configuration from environment
$storagePath = getenv('ADP_STORAGE_PATH') ?: sys_get_temp_dir() . '/adp';
$rootPath = getenv('ADP_ROOT_PATH') ?: getcwd();
$runtimePath = getenv('ADP_RUNTIME_PATH') ?: $storagePath;
$frontendPath = getenv('ADP_FRONTEND_PATH') ?: null;

$requestUri = $_SERVER['REQUEST_URI'] ?? '/';
$requestPath = parse_url($requestUri, PHP_URL_PATH);

// Serve frontend static files if configured and path doesn't match API
if (
    $frontendPath !== null
    && !str_starts_with($requestPath, '/debug/api')
    && !str_starts_with($requestPath, '/inspect/api')
) {
    $filePath = $frontendPath . $requestPath;
    if ($requestPath === '/' || $requestPath === '') {
        $filePath = $frontendPath . '/index.html';
    }
    if (is_file($filePath)) {
        return false; // Let PHP built-in server handle static files
    }
    // SPA fallback: serve index.html for non-file paths
    $indexPath = $frontendPath . '/index.html';
    if (is_file($indexPath)) {
        header('Content-Type: text/html');
        readfile($indexPath);
        return;
    }
}

// Only handle API requests
if (!str_starts_with($requestPath, '/debug/api') && !str_starts_with($requestPath, '/inspect/api')) {
    http_response_code(404);
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Not found. API is available at /debug/api and /inspect/api.']);
    return;
}

// --- Build the container and application ---

$httpFactory = new HttpFactory();
$pathResolver = new PathResolver($rootPath, $runtimePath);
$idGenerator = new DebuggerIdGenerator();
$storage = new SqliteStorage($storagePath . '/debug.db', $idGenerator, []);
$jsonResponseFactory = new JsonResponseFactory($httpFactory, $httpFactory);
$collectorRepository = new CollectorRepository($storage);
$serviceRegistry = new FileServiceRegistry($storagePath . '/services');
$httpClient = new Client(['timeout' => 10]);

// Simple PSR-11 container
$services = [
    ResponseFactoryInterface::class => $httpFactory,
    StreamFactoryInterface::class => $httpFactory,
    UriFactoryInterface::class => $httpFactory,
    JsonResponseFactoryInterface::class => $jsonResponseFactory,
    PathResolverInterface::class => $pathResolver,
    StorageInterface::class => $storage,
    CollectorRepositoryInterface::class => $collectorRepository,
    ServiceRegistryInterface::class => $serviceRegistry,
    ClientInterface::class => $httpClient,
    DebuggerIdGenerator::class => $idGenerator,

    // Middleware
    IpFilterMiddleware::class => new IpFilterMiddleware($httpFactory, $httpFactory, []),
    TokenAuthMiddleware::class => new TokenAuthMiddleware($httpFactory, $httpFactory, ''),
    ResponseDataWrapper::class => new ResponseDataWrapper($jsonResponseFactory),
    InspectorProxyMiddleware::class => new InspectorProxyMiddleware(
        $serviceRegistry,
        $httpClient,
        $httpFactory,
        $httpFactory,
        $httpFactory,
    ),

    // Controllers
    DebugController::class => new DebugController($jsonResponseFactory, $collectorRepository, $storage, $httpFactory),
    IngestionController::class => new IngestionController($jsonResponseFactory, $storage),
    ServiceController::class => new ServiceController($jsonResponseFactory, $serviceRegistry),
    FileController::class => new FileController($jsonResponseFactory, $pathResolver),
    GitRepositoryProvider::class => new GitRepositoryProvider($pathResolver),
    OpcacheController::class => new OpcacheController($jsonResponseFactory),
    ComposerController::class => new ComposerController($jsonResponseFactory, $pathResolver),
    RoutingController::class => new RoutingController($jsonResponseFactory),
    RequestController::class => new RequestController($jsonResponseFactory, $collectorRepository),
    McpSettings::class => new McpSettings($storagePath),
    McpController::class => new McpController(
        $jsonResponseFactory,
        new McpServer(McpToolRegistryFactory::create($storage)),
        new McpSettings($storagePath),
    ),
    McpSettingsController::class => new McpSettingsController($jsonResponseFactory, new McpSettings($storagePath)),
];

// Lazy-loaded controllers that need container
$container = new class($services) implements \Psr\Container\ContainerInterface {
    private array $resolved = [];

    public function __construct(
        private array $services,
    ) {}

    public function get(string $id): mixed
    {
        if (array_key_exists($id, $this->resolved)) {
            return $this->resolved[$id];
        }
        if (array_key_exists($id, $this->services)) {
            $service = $this->services[$id];
            $this->resolved[$id] = $service;
            return $service;
        }
        throw new \RuntimeException(sprintf('Service "%s" not found.', $id));
    }

    public function has(string $id): bool
    {
        return array_key_exists($id, $this->services) || array_key_exists($id, $this->resolved);
    }

    public function set(string $id, mixed $service): void
    {
        $this->services[$id] = $service;
    }
};

// Register controllers that need the container
$container->set(InspectController::class, new InspectController($jsonResponseFactory, $container));
$container->set(CacheController::class, new CacheController($jsonResponseFactory, $container));
$container->set(
    TranslationController::class,
    new TranslationController($jsonResponseFactory, new \Psr\Log\NullLogger(), $container),
);
$container->set(CommandController::class, new CommandController($jsonResponseFactory, $pathResolver, $container));
$container->set(
    GitController::class,
    new GitController($jsonResponseFactory, $container->get(GitRepositoryProvider::class)),
);
$container->set(DatabaseController::class, new class($jsonResponseFactory) {
    public function __construct(
        private readonly JsonResponseFactoryInterface $responseFactory,
    ) {}

    public function getTables(): \Psr\Http\Message\ResponseInterface
    {
        return $this->responseFactory->createJsonResponse([
            'error' => 'Database inspection requires framework integration.',
        ], 501);
    }

    public function getTable(): \Psr\Http\Message\ResponseInterface
    {
        return $this->responseFactory->createJsonResponse([
            'error' => 'Database inspection requires framework integration.',
        ], 501);
    }
});

$app = new ApiApplication($container, $httpFactory, $httpFactory);

// Convert superglobals to PSR-7 request
$psrRequest = ServerRequest::fromGlobals();

// Handle the request
$response = $app->handle($psrRequest);

// Emit response
http_response_code($response->getStatusCode());
foreach ($response->getHeaders() as $name => $values) {
    foreach ($values as $value) {
        header(sprintf('%s: %s', $name, $value), false);
    }
}

$body = $response->getBody();
if ($body instanceof \AppDevPanel\Api\ServerSentEventsStream) {
    // SSE: stream output
    while (!$body->eof()) {
        echo $body->read(8192);
        if (ob_get_level() > 0) {
            ob_flush();
        }
        flush();
    }
} else {
    echo $body;
}
