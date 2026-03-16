<?php

declare(strict_types=1);

namespace AppDevPanel\Api\Tests\Unit\Inspector\Middleware;

use AppDevPanel\Api\Inspector\Middleware\InspectorProxyMiddleware;
use AppDevPanel\Kernel\Service\ServiceDescriptor;
use AppDevPanel\Kernel\Service\ServiceRegistryInterface;
use Nyholm\Psr7\Factory\Psr17Factory;
use Nyholm\Psr7\Response;
use Nyholm\Psr7\ServerRequest;
use PHPUnit\Framework\TestCase;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

final class InspectorProxyMiddlewareTest extends TestCase
{
    private function createMiddleware(
        ?ServiceRegistryInterface $registry = null,
        ?ClientInterface $httpClient = null,
    ): InspectorProxyMiddleware {
        $psr17 = new Psr17Factory();

        return new InspectorProxyMiddleware(
            $registry ?? $this->emptyRegistry(),
            $httpClient ?? $this->mockHttpClient(new Response(200)),
            $psr17,
            $psr17,
            $psr17,
        );
    }

    private function emptyRegistry(): ServiceRegistryInterface
    {
        $registry = $this->createMock(ServiceRegistryInterface::class);
        $registry->method('resolve')->willReturn(null);

        return $registry;
    }

    private function registryWith(ServiceDescriptor $descriptor): ServiceRegistryInterface
    {
        $registry = $this->createMock(ServiceRegistryInterface::class);
        $registry->method('resolve')->willReturnCallback(static fn(string $service) => $service === $descriptor->service
            ? $descriptor
            : null);

        return $registry;
    }

    private function onlineService(
        string $service = 'test-svc',
        array $capabilities = ['config', 'routes'],
    ): ServiceDescriptor {
        $now = microtime(true);

        return new ServiceDescriptor($service, 'python', 'http://python-app:9090', $capabilities, $now, $now);
    }

    private function offlineService(): ServiceDescriptor
    {
        $old = microtime(true) - 300; // 5 minutes ago

        return new ServiceDescriptor('offline-svc', 'python', 'http://dead:9090', ['config'], $old, $old);
    }

    private function mockHttpClient(ResponseInterface $response): ClientInterface
    {
        $client = $this->createMock(ClientInterface::class);
        $client->method('sendRequest')->willReturn($response);

        return $client;
    }

    private function capturingHttpClient(ResponseInterface $response, ?RequestInterface &$captured): ClientInterface
    {
        $client = $this->createMock(ClientInterface::class);
        $client
            ->method('sendRequest')
            ->willReturnCallback(static function (RequestInterface $request) use ($response, &$captured) {
                $captured = $request;

                return $response;
            });

        return $client;
    }

    private function failingHttpClient(string $message): ClientInterface
    {
        $client = $this->createMock(ClientInterface::class);
        $client->method('sendRequest')->willThrowException(new \RuntimeException($message));

        return $client;
    }

    private function localHandler(): RequestHandlerInterface
    {
        $handler = $this->createMock(RequestHandlerInterface::class);
        $handler->method('handle')->willReturn(new Response(200, [], '{"local":true}'));

        return $handler;
    }

    private function neverCalledHandler(): RequestHandlerInterface
    {
        $handler = $this->createMock(RequestHandlerInterface::class);
        $handler->expects($this->never())->method('handle');

        return $handler;
    }

    public function testPassThroughWhenNoServiceParam(): void
    {
        $middleware = $this->createMiddleware();
        $request = new ServerRequest('GET', '/inspect/api/config');
        $handler = $this->localHandler();

        $response = $middleware->process($request, $handler);

        $this->assertSame(200, $response->getStatusCode());
    }

    public function testPassThroughWhenServiceIsLocal(): void
    {
        $middleware = $this->createMiddleware();
        $request = new ServerRequest('GET', '/inspect/api/config?service=local')->withQueryParams([
            'service' => 'local',
        ]);
        $handler = $this->localHandler();

        $response = $middleware->process($request, $handler);

        $this->assertSame(200, $response->getStatusCode());
    }

    public function testPassThroughWhenServiceIsEmpty(): void
    {
        $middleware = $this->createMiddleware();
        $request = new ServerRequest('GET', '/inspect/api/config?service=')->withQueryParams(['service' => '']);
        $handler = $this->localHandler();

        $response = $middleware->process($request, $handler);

        $this->assertSame(200, $response->getStatusCode());
    }

    public function testReturns404WhenServiceNotFound(): void
    {
        $middleware = $this->createMiddleware();
        $request = new ServerRequest('GET', '/inspect/api/config?service=unknown')->withQueryParams([
            'service' => 'unknown',
        ]);

        $response = $middleware->process($request, $this->neverCalledHandler());

        $this->assertSame(404, $response->getStatusCode());
        $body = json_decode((string) $response->getBody(), true);
        $this->assertStringContainsString('unknown', $body['error']);
    }

    public function testReturns503WhenServiceOffline(): void
    {
        $middleware = $this->createMiddleware($this->registryWith($this->offlineService()));
        $request = new ServerRequest('GET', '/inspect/api/config?service=offline-svc')->withQueryParams([
            'service' => 'offline-svc',
        ]);

        $response = $middleware->process($request, $this->neverCalledHandler());

        $this->assertSame(503, $response->getStatusCode());
    }

    public function testReturns501WhenCapabilityNotSupported(): void
    {
        $middleware = $this->createMiddleware($this->registryWith($this->onlineService('svc', ['routes']))); // no 'database' capability
        $request = new ServerRequest('GET', '/inspect/api/table?service=svc')->withQueryParams(['service' => 'svc']);

        $response = $middleware->process($request, $this->neverCalledHandler());

        $this->assertSame(501, $response->getStatusCode());
        $body = json_decode((string) $response->getBody(), true);
        $this->assertStringContainsString('database', $body['error']);
    }

    public function testProxiesRequestToExternalService(): void
    {
        /** @var RequestInterface|null $captured */
        $captured = null;
        $proxyResponse = new Response(200, ['Content-Type' => 'application/json'], '{"routes":[]}');
        $httpClient = $this->capturingHttpClient($proxyResponse, $captured);

        $middleware = $this->createMiddleware($this->registryWith($this->onlineService()), $httpClient);

        $request = new ServerRequest('GET', '/inspect/api/routes?service=test-svc&extra=1')->withQueryParams([
            'service' => 'test-svc',
            'extra' => '1',
        ]);

        $response = $middleware->process($request, $this->neverCalledHandler());

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('{"routes":[]}', (string) $response->getBody());

        // Verify the proxied request
        $this->assertNotNull($captured);
        $uri = (string) $captured->getUri();
        $this->assertStringContainsString('http://python-app:9090/inspect/api/routes', $uri);
        $this->assertStringContainsString('extra=1', $uri);
        $this->assertStringNotContainsString('service=', $uri);
    }

    public function testProxiesPostWithBody(): void
    {
        /** @var RequestInterface|null $captured */
        $captured = null;
        $httpClient = $this->capturingHttpClient(new Response(200), $captured);

        $middleware = $this->createMiddleware($this->registryWith($this->onlineService('svc', ['git'])), $httpClient);

        $request = new ServerRequest('POST', '/inspect/api/git/checkout?service=svc')
            ->withQueryParams(['service' => 'svc'])
            ->withHeader('Content-Type', 'application/json')
            ->withBody(\Nyholm\Psr7\Stream::create('{"branch":"main"}'));

        $response = $middleware->process($request, $this->neverCalledHandler());

        $this->assertSame(200, $response->getStatusCode());
        $this->assertNotNull($captured);
        $this->assertSame('POST', $captured->getMethod());
        $this->assertSame('{"branch":"main"}', (string) $captured->getBody());
    }

    public function testReturns502OnConnectionRefused(): void
    {
        $middleware = $this->createMiddleware(
            $this->registryWith($this->onlineService()),
            $this->failingHttpClient('Connection refused'),
        );

        $request = new ServerRequest('GET', '/inspect/api/config?service=test-svc')->withQueryParams([
            'service' => 'test-svc',
        ]);

        $response = $middleware->process($request, $this->neverCalledHandler());

        $this->assertSame(502, $response->getStatusCode());
    }

    public function testReturns504OnTimeout(): void
    {
        $middleware = $this->createMiddleware(
            $this->registryWith($this->onlineService()),
            $this->failingHttpClient('Operation timed out'),
        );

        $request = new ServerRequest('GET', '/inspect/api/config?service=test-svc')->withQueryParams([
            'service' => 'test-svc',
        ]);

        $response = $middleware->process($request, $this->neverCalledHandler());

        $this->assertSame(504, $response->getStatusCode());
    }

    public function testReturns502OnGenericError(): void
    {
        $middleware = $this->createMiddleware(
            $this->registryWith($this->onlineService()),
            $this->failingHttpClient('Some weird error'),
        );

        $request = new ServerRequest('GET', '/inspect/api/config?service=test-svc')->withQueryParams([
            'service' => 'test-svc',
        ]);

        $response = $middleware->process($request, $this->neverCalledHandler());

        $this->assertSame(502, $response->getStatusCode());
    }

    public function testWildcardCapabilityAllowsAnything(): void
    {
        /** @var RequestInterface|null $captured */
        $captured = null;
        $httpClient = $this->capturingHttpClient(new Response(200), $captured);

        $now = microtime(true);
        $descriptor = new ServiceDescriptor('full-svc', 'python', 'http://full:9090', ['*'], $now, $now);

        $middleware = $this->createMiddleware($this->registryWith($descriptor), $httpClient);

        $request = new ServerRequest('GET', '/inspect/api/phpinfo?service=full-svc')->withQueryParams([
            'service' => 'full-svc',
        ]);

        $response = $middleware->process($request, $this->neverCalledHandler());

        $this->assertSame(200, $response->getStatusCode());
    }

    public function testServiceWithNullInspectorUrl(): void
    {
        $now = microtime(true);
        $descriptor = new ServiceDescriptor('no-url', 'python', null, ['config'], $now, $now);

        $middleware = $this->createMiddleware($this->registryWith($descriptor));

        $request = new ServerRequest('GET', '/inspect/api/config?service=no-url')->withQueryParams([
            'service' => 'no-url',
        ]);

        $response = $middleware->process($request, $this->neverCalledHandler());

        $this->assertSame(502, $response->getStatusCode());
    }
}
