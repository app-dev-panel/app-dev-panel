<?php

declare(strict_types=1);

namespace AppDevPanel\Adapter\Spiral\Tests\Unit\Container;

use AppDevPanel\Adapter\Spiral\Container\HttpClientProxyInjector;
use AppDevPanel\Kernel\Collector\HttpClientCollector;
use AppDevPanel\Kernel\Collector\HttpClientInterfaceProxy;
use AppDevPanel\Kernel\Collector\TimelineCollector;
use Nyholm\Psr7\Request;
use Nyholm\Psr7\Response;
use PHPUnit\Framework\TestCase;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use ReflectionProperty;
use Spiral\Core\Container;

final class HttpClientProxyInjectorTest extends TestCase
{
    public function testProxyDecoratesUnderlyingService(): void
    {
        $container = new Container();
        $binder = $container->getBinder();
        $collector = new HttpClientCollector(new TimelineCollector());

        $fake = new class implements ClientInterface {
            public ?RequestInterface $sent = null;

            public function sendRequest(RequestInterface $request): ResponseInterface
            {
                $this->sent = $request;
                return new Response(200, [], 'ok');
            }
        };

        $container->bindSingleton(ClientInterface::class, $fake);

        $injector = new HttpClientProxyInjector($container, $binder, $collector);
        $injector->setUnderlying($fake);
        $container->bindSingleton(HttpClientProxyInjector::class, $injector);

        $binder->bindInjector(ClientInterface::class, HttpClientProxyInjector::class);

        $resolved = $container->get(ClientInterface::class);

        self::assertInstanceOf(HttpClientInterfaceProxy::class, $resolved);
        $reflection = new ReflectionProperty(HttpClientInterfaceProxy::class, 'decorated');
        self::assertSame($fake, $reflection->getValue($resolved));
    }

    public function testFallsBackToDefaultWhenNothingBound(): void
    {
        $container = new Container();
        $binder = $container->getBinder();
        $collector = new HttpClientCollector(new TimelineCollector());

        $injector = new HttpClientProxyInjector($container, $binder, $collector);
        $container->bindSingleton(HttpClientProxyInjector::class, $injector);

        $binder->bindInjector(ClientInterface::class, HttpClientProxyInjector::class);

        $resolved = $container->get(ClientInterface::class);

        self::assertInstanceOf(HttpClientInterfaceProxy::class, $resolved);
        $reflection = new ReflectionProperty(HttpClientInterfaceProxy::class, 'decorated');
        $inner = $reflection->getValue($resolved);
        self::assertInstanceOf(ClientInterface::class, $inner);

        // The fallback signals "no upstream PSR-18 client" via 503.
        $response = $inner->sendRequest(new Request('GET', 'http://example.test/'));
        self::assertSame(503, $response->getStatusCode());
    }

    public function testCollectorReceivesIntercept(): void
    {
        $container = new Container();
        $binder = $container->getBinder();
        $collector = new HttpClientCollector(new TimelineCollector());
        $collector->startup();

        $fake = new class implements ClientInterface {
            public function sendRequest(RequestInterface $request): ResponseInterface
            {
                return new Response(200, [], 'ok');
            }
        };

        $injector = new HttpClientProxyInjector($container, $binder, $collector);
        $injector->setUnderlying($fake);
        $container->bindSingleton(HttpClientProxyInjector::class, $injector);

        $binder->bindInjector(ClientInterface::class, HttpClientProxyInjector::class);

        /** @var ClientInterface $client */
        $client = $container->get(ClientInterface::class);
        $client->sendRequest(new Request('POST', 'https://example.test/api'));

        $entries = $collector->getCollected();
        self::assertCount(1, $entries);
        self::assertSame('POST', $entries[0]['method']);
        self::assertSame('https://example.test/api', $entries[0]['uri']);
        self::assertSame(200, $entries[0]['responseStatus']);
    }
}
