<?php

declare(strict_types=1);

namespace AppDevPanel\Api\Tests\Unit\Debug\Middleware;

use AppDevPanel\Api\Debug\Middleware\TokenAuthMiddleware;
use GuzzleHttp\Psr7\HttpFactory;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Psr7\ServerRequest;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\RequestHandlerInterface;

final class TokenAuthMiddlewareTest extends TestCase
{
    public function testEmptyTokenPassesThrough(): void
    {
        $middleware = new TokenAuthMiddleware(new HttpFactory(), new HttpFactory(), '');
        $handler = $this->createPassthroughHandler();

        $response = $middleware->process(new ServerRequest('GET', '/test'), $handler);

        $this->assertSame(200, $response->getStatusCode());
    }

    public function testValidTokenPassesThrough(): void
    {
        $middleware = new TokenAuthMiddleware(new HttpFactory(), new HttpFactory(), 'secret-token');
        $handler = $this->createPassthroughHandler();

        $request = new ServerRequest('GET', '/test');
        $request = $request->withHeader('X-Debug-Token', 'secret-token');

        $response = $middleware->process($request, $handler);

        $this->assertSame(200, $response->getStatusCode());
    }

    public function testInvalidTokenReturns401(): void
    {
        $middleware = new TokenAuthMiddleware(new HttpFactory(), new HttpFactory(), 'secret-token');
        $handler = $this->createPassthroughHandler();

        $request = new ServerRequest('GET', '/test');
        $request = $request->withHeader('X-Debug-Token', 'wrong-token');

        $response = $middleware->process($request, $handler);

        $this->assertSame(401, $response->getStatusCode());
        $this->assertSame('application/json', $response->getHeaderLine('Content-Type'));

        $body = json_decode((string) $response->getBody(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertFalse($body['success']);
        $this->assertStringContainsString('authentication token', $body['error']);
    }

    public function testMissingTokenReturns401(): void
    {
        $middleware = new TokenAuthMiddleware(new HttpFactory(), new HttpFactory(), 'secret-token');
        $handler = $this->createPassthroughHandler();

        $response = $middleware->process(new ServerRequest('GET', '/test'), $handler);

        $this->assertSame(401, $response->getStatusCode());
    }

    private function createPassthroughHandler(): RequestHandlerInterface
    {
        return new class() implements RequestHandlerInterface {
            public function handle($request): ResponseInterface
            {
                return new Response();
            }
        };
    }
}
