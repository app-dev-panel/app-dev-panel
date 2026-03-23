<?php

declare(strict_types=1);

namespace AppDevPanel\Api\Tests\Unit\Middleware;

use AppDevPanel\Api\Middleware\IpFilterMiddleware;
use GuzzleHttp\Psr7\HttpFactory;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Psr7\ServerRequest;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\RequestHandlerInterface;

final class IpFilterMiddlewareTest extends TestCase
{
    public function testEmptyAllowedIpsPassesThrough(): void
    {
        $middleware = new IpFilterMiddleware(new HttpFactory(), new HttpFactory(), []);
        $handler = $this->createHandler();

        $response = $middleware->process(new ServerRequest('GET', '/test'), $handler);

        $this->assertSame(200, $response->getStatusCode());
    }

    public function testAllowedIpPassesThrough(): void
    {
        $middleware = new IpFilterMiddleware(new HttpFactory(), new HttpFactory(), ['127.0.0.1']);
        $handler = $this->createHandler();

        $request = new ServerRequest('GET', '/test', [], null, '1.1', ['REMOTE_ADDR' => '127.0.0.1']);

        $response = $middleware->process($request, $handler);

        $this->assertSame(200, $response->getStatusCode());
    }

    public function testDeniedIpReturns403(): void
    {
        $middleware = new IpFilterMiddleware(new HttpFactory(), new HttpFactory(), ['127.0.0.1']);
        $handler = $this->createHandler();

        $request = new ServerRequest('GET', '/test', [], null, '1.1', ['REMOTE_ADDR' => '192.168.1.100']);

        $response = $middleware->process($request, $handler);

        $this->assertSame(403, $response->getStatusCode());
        $this->assertSame('application/json', $response->getHeaderLine('Content-Type'));

        $body = json_decode((string) $response->getBody(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertFalse($body['success']);
        $this->assertStringContainsString('Access denied', $body['error']);
    }

    public function testMultipleAllowedIps(): void
    {
        $middleware = new IpFilterMiddleware(new HttpFactory(), new HttpFactory(), ['127.0.0.1', '::1', '10.0.0.1']);
        $handler = $this->createHandler();

        $request = new ServerRequest('GET', '/test', [], null, '1.1', ['REMOTE_ADDR' => '10.0.0.1']);

        $response = $middleware->process($request, $handler);

        $this->assertSame(200, $response->getStatusCode());
    }

    public function testMissingRemoteAddrDenied(): void
    {
        $middleware = new IpFilterMiddleware(new HttpFactory(), new HttpFactory(), ['127.0.0.1']);
        $handler = $this->createHandler();

        $response = $middleware->process(new ServerRequest('GET', '/test'), $handler);

        $this->assertSame(403, $response->getStatusCode());
    }

    private function createHandler(): RequestHandlerInterface
    {
        return new class() implements RequestHandlerInterface {
            public function handle($request): ResponseInterface
            {
                return new Response();
            }
        };
    }
}
