<?php

declare(strict_types=1);

namespace AppDevPanel\Api\Inspector\Middleware;

use AppDevPanel\Api\Security\UrlPolicy;
use AppDevPanel\Kernel\Service\ServiceRegistryInterface;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Message\UriFactoryInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

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
        '/authorization' => 'authorization',
    ];

    /**
     * @param UrlPolicy $urlPolicy Re-validated right before every proxied request so a
     *                             descriptor that was registered earlier (or whose host
     *                             now resolves elsewhere) cannot be used for SSRF; build it
     *                             from `ApiSecurityConfig::$restrictInspectorUrlsToPublicHosts`.
     */
    // @mago-expect lint:excessive-parameter-list
    public function __construct(
        private readonly ServiceRegistryInterface $registry,
        private readonly ClientInterface $httpClient,
        private readonly ResponseFactoryInterface $responseFactory,
        private readonly StreamFactoryInterface $streamFactory,
        private readonly UriFactoryInterface $uriFactory,
        private readonly UrlPolicy $urlPolicy = new UrlPolicy(),
    ) {}

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $service = $request->getQueryParams()['service'] ?? null;

        if (in_array($service, [null, '', 'local'], true)) {
            return $handler->handle($request);
        }

        $descriptor = $this->registry->resolve($service);
        if ($descriptor === null) {
            return $this->errorResponse(404, sprintf('Service "%s" not found.', $service));
        }
        if (!$descriptor->isOnline()) {
            return $this->errorResponse(503, sprintf('Service "%s" is offline.', $service));
        }

        $fullPath = $request->getUri()->getPath();
        $prefix = '/inspect/api';
        $path = str_starts_with($fullPath, $prefix) ? (substr($fullPath, strlen($prefix)) ?: '/') : $fullPath;

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

    private function resolveCapability(string $path): ?string
    {
        foreach (self::CAPABILITY_MAP as $prefix => $capability) {
            if (str_starts_with($path, $prefix)) {
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

        try {
            $this->urlPolicy->assertAllowed($inspectorUrl);
        } catch (\InvalidArgumentException $e) {
            return $this->errorResponse(502, sprintf('Service inspector URL is rejected: %s', $e->getMessage()));
        }

        $targetUrl = rtrim($inspectorUrl, '/') . '/inspect/api' . $path;

        $queryParams = $request->getQueryParams();
        unset($queryParams['service']);
        if ($queryParams !== []) {
            $targetUrl .= '?' . http_build_query($queryParams);
        }

        try {
            $proxyRequest = $request->withUri($this->uriFactory->createUri($targetUrl))->withoutHeader('Host');

            return $this->httpClient->sendRequest($proxyRequest);
        } catch (\Throwable $e) {
            return $this->handleProxyError($e);
        }
    }

    private function handleProxyError(\Throwable $e): ResponseInterface
    {
        $message = $e->getMessage();

        $patternMap = [
            'Connection refused' => [502, 'Cannot connect to service'],
            'Could not resolve host' => [502, 'Cannot connect to service'],
            'timed out' => [504, 'Service request timed out'],
            'Timeout' => [504, 'Service request timed out'],
        ];

        $matched = array_find_key($patternMap, static fn(array $_, string $pattern): bool => str_contains(
            $message,
            $pattern,
        ));

        if ($matched === null) {
            return $this->errorResponse(502, sprintf('Proxy error: %s', $message));
        }

        [$status, $prefix] = $patternMap[$matched];

        return $this->errorResponse($status, sprintf('%s: %s', $prefix, $message));
    }

    private function errorResponse(int $status, string $message): ResponseInterface
    {
        $body = json_encode([
            'error' => $message,
            'success' => false,
        ], JSON_THROW_ON_ERROR);

        $response = $this->responseFactory->createResponse($status);

        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withBody($this->streamFactory->createStream($body));
    }
}
