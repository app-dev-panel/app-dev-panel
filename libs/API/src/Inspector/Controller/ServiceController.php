<?php

declare(strict_types=1);

namespace AppDevPanel\Api\Inspector\Controller;

use AppDevPanel\Kernel\Service\ServiceDescriptor;
use AppDevPanel\Kernel\Service\ServiceRegistryInterface;
use InvalidArgumentException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Yiisoft\DataResponse\DataResponseFactoryInterface;
use Yiisoft\Json\Json;
use Yiisoft\Router\CurrentRoute;

final class ServiceController
{
    public function __construct(
        private DataResponseFactoryInterface $responseFactory,
        private ServiceRegistryInterface $registry,
    ) {}

    public function register(ServerRequestInterface $request): ResponseInterface
    {
        /** @var array<string, mixed> $body */
        $body = Json::decode($request->getBody()->getContents());

        if (!isset($body['service']) || !is_string($body['service']) || $body['service'] === '') {
            throw new InvalidArgumentException('Field "service" is required and must be a non-empty string.');
        }

        if ($body['service'] === 'local') {
            throw new InvalidArgumentException('Service name "local" is reserved.');
        }

        if (!isset($body['inspectorUrl']) || !is_string($body['inspectorUrl'])) {
            throw new InvalidArgumentException('Field "inspectorUrl" is required and must be a string.');
        }

        $now = microtime(true);
        /** @var string[] $capabilities */
        $capabilities = (array) ($body['capabilities'] ?? []);
        $descriptor = new ServiceDescriptor(
            service: $body['service'],
            language: (string) ($body['language'] ?? 'unknown'),
            inspectorUrl: $body['inspectorUrl'],
            capabilities: $capabilities,
            registeredAt: $now,
            lastSeenAt: $now,
        );

        $this->registry->register($descriptor);

        return $this->responseFactory->createResponse([
            'service' => $descriptor->service,
            'registered' => true,
        ]);
    }

    public function heartbeat(ServerRequestInterface $request): ResponseInterface
    {
        /** @var array<string, mixed> $body */
        $body = Json::decode($request->getBody()->getContents());

        if (!isset($body['service']) || !is_string($body['service'])) {
            throw new InvalidArgumentException('Field "service" is required.');
        }

        $descriptor = $this->registry->resolve($body['service']);
        if ($descriptor === null) {
            throw new InvalidArgumentException(sprintf('Service "%s" is not registered.', $body['service']));
        }

        $this->registry->heartbeat($body['service']);

        return $this->responseFactory->createResponse([
            'service' => $body['service'],
            'acknowledged' => true,
        ]);
    }

    public function list(): ResponseInterface
    {
        $services = $this->registry->all();

        $result = array_map(static function (ServiceDescriptor $descriptor): array {
            return [
                ...$descriptor->toArray(),
                'status' => $descriptor->isOnline() ? 'online' : 'offline',
            ];
        }, $services);

        return $this->responseFactory->createResponse([
            'services' => array_values($result),
        ]);
    }

    public function deregister(ServerRequestInterface $request, CurrentRoute $route): ResponseInterface
    {
        /** @var string $service */
        $service = $route->getArgument('service', '');

        if ($service === '') {
            throw new InvalidArgumentException('Service name is required.');
        }

        if ($service === 'local') {
            throw new InvalidArgumentException('Cannot deregister the local service.');
        }

        $this->registry->deregister($service);

        return $this->responseFactory->createResponse([
            'service' => $service,
            'deregistered' => true,
        ]);
    }
}
