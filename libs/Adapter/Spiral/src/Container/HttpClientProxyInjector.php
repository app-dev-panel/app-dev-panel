<?php

declare(strict_types=1);

namespace AppDevPanel\Adapter\Spiral\Container;

use AppDevPanel\Kernel\Collector\HttpClientCollector;
use AppDevPanel\Kernel\Collector\HttpClientInterfaceProxy;
use Nyholm\Psr7\Response;
use Psr\Container\ContainerInterface;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use ReflectionClass;
use Spiral\Core\BinderInterface;
use Spiral\Core\Container\InjectorInterface;

/**
 * Spiral container injector that wraps any `Psr\Http\Client\ClientInterface` resolution
 * with {@see HttpClientInterfaceProxy} so outbound HTTP traffic is forwarded to
 * {@see HttpClientCollector}.
 *
 * If the application has not bound a PSR-18 client, falls back to a stub that returns
 * `503 Service Unavailable`. This mirrors the historical adapter behaviour: rather than
 * exploding on resolve when nothing is bound, ADP returns a usable proxy whose inner
 * client cleanly signals "no upstream available".
 *
 * @implements InjectorInterface<ClientInterface>
 */
final class HttpClientProxyInjector implements InjectorInterface
{
    use InjectorTrait;

    public function __construct(
        private readonly ContainerInterface $container,
        private readonly BinderInterface $binder,
        private readonly HttpClientCollector $collector,
    ) {}

    public function createInjection(ReflectionClass $class, ?string $context = null): ClientInterface
    {
        /** @var ClientInterface $original */
        $original = $this->resolveUnderlying(
            $this->container,
            $this->binder,
            ClientInterface::class,
            self::nullClient(),
        );

        return new HttpClientInterfaceProxy($original, $this->collector);
    }

    private static function nullClient(): ClientInterface
    {
        return new class implements ClientInterface {
            public function sendRequest(RequestInterface $request): ResponseInterface
            {
                return new Response(503, [], 'No PSR-18 client bound to the container.');
            }
        };
    }
}
