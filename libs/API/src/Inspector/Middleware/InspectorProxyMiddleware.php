<?php

declare(strict_types=1);

namespace AppDevPanel\Api\Inspector\Middleware;

use AppDevPanel\Kernel\Service\ServiceRegistryInterface;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Message\UriFactoryInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Yiisoft\Json\Json;

final class InspectorProxyMiddleware implements MiddlewareInterface
{
    /**
     * Maps inspector path prefixes to capability names.
     */
    private const CAPABILITY_MAP = [
        '/config' => 'config',
        '/params' => 'config',
        '/routes' => 'routes',
        '/route/check' => 'routes',
        '/files' => 'files',
        '/cache' => 'cache',
        '/table' => 'database',
        '/translations' => 'translations',
        '/events' => 'events',
        '/command' => 'commands',
        '/git/' => 'git',
        '/git' => 'git',
        '/composer/' => 'composer',
        '/composer' => 'composer',
        '/classes' => 'classes',
        '/object' => 'object',
        '/phpinfo' => 'phpinfo',
        '/opcache' => 'opcache',
        '/request' => 'request',
        '/curl/build' => 'request',
    ];

    public function __construct(
        private ServiceRegistryInterface $registry,
        private ClientInterface $httpClient,
        private ResponseFactoryInterface $responseFactory,
        private StreamFactoryInterface $streamFactory,
        private UriFactoryInterface $uriFactory,
    ) {}

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $queryParams = $request->getQueryParams();
        /** @var string|null $service */
        $service = $queryParams['service'] ?? null;

        // No service param or "local" → handle locally
        if ($service === null || $service === '' || $service === 'local') {
            return $handler->handle($request);
        }

        $descriptor = $this->registry->resolve($service);
        if ($descriptor === null) {
            return $this->errorResponse(404, sprintf('Service "%s" not found.', $service));
        }

        if (!$descriptor->isOnline()) {
            return $this->errorResponse(503, sprintf('Service "%s" is offline.', $service));
        }

        // Check capability
        $path = $this->extractInspectorPath($request);
        $capability = $this->resolveCapability($path);

        if ($capability !== null && !$descriptor->supports($capability)) {
            return $this->errorResponse(501, sprintf(
                'Service "%s" does not support capability "%s".',
                $service,
                $capability,
            ));
        }

        return $this->proxy($request, $descriptor->inspectorUrl, $path);
    }

    private function extractInspectorPath(ServerRequestInterface $request): string
    {
        $fullPath = $request->getUri()->getPath();

        // Strip /inspect/api prefix to get the inspector-relative path
        $prefix = '/inspect/api';
        if (str_starts_with($fullPath, $prefix)) {
            return substr($fullPath, strlen($prefix)) ?: '/';
        }

        return $fullPath;
    }

    private function resolveCapability(string $path): ?string
    {
        // Try exact match first, then prefix match
        foreach (self::CAPABILITY_MAP as $pattern => $capability) {
            if (
                $path === $pattern
                || str_starts_with($path, $pattern . '/')
                || str_starts_with($path, $pattern . '?')
            ) {
                return $capability;
            }
        }

        return null;
    }

    private function proxy(ServerRequestInterface $request, ?string $inspectorUrl, string $path): ResponseInterface
    {
        if ($inspectorUrl === null) {
            return $this->errorResponse(502, 'Service has no inspector URL configured.');
        }

        $targetUrl = rtrim($inspectorUrl, '/') . '/inspect/api' . $path;

        // Strip the "service" query param before proxying
        $queryParams = $request->getQueryParams();
        unset($queryParams['service']);
        if ($queryParams !== []) {
            $targetUrl .= '?' . http_build_query($queryParams);
        }

        try {
            $proxyRequest = $request->withUri($this->uriFactory->createUri($targetUrl))->withoutHeader('Host');

            return $this->httpClient->sendRequest($proxyRequest);
        } catch (\Throwable $e) {
            $message = $e->getMessage();

            if (str_contains($message, 'Connection refused') || str_contains($message, 'Could not resolve host')) {
                return $this->errorResponse(502, sprintf('Cannot connect to service: %s', $message));
            }

            if (str_contains($message, 'timed out') || str_contains($message, 'Timeout')) {
                return $this->errorResponse(504, sprintf('Service request timed out: %s', $message));
            }

            return $this->errorResponse(502, sprintf('Proxy error: %s', $message));
        }
    }

    private function errorResponse(int $status, string $message): ResponseInterface
    {
        $body = Json::encode([
            'error' => $message,
            'success' => false,
        ]);

        $response = $this->responseFactory->createResponse($status);

        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withBody($this->streamFactory->createStream($body));
    }
}
