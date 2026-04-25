<?php

declare(strict_types=1);

namespace AppDevPanel\Api\Tests\Unit\Middleware;

use AppDevPanel\Api\Middleware\CorsMiddleware;
use GuzzleHttp\Psr7\HttpFactory;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Psr7\ServerRequest;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\RequestHandlerInterface;

final class CorsMiddlewareTest extends TestCase
{
    public function testOptionsRequestReturns204(): void
    {
        $middleware = new CorsMiddleware(new HttpFactory());
        $handler = $this->createHandler();

        $response = $middleware->process(new ServerRequest('OPTIONS', '/test'), $handler);

        $this->assertSame(204, $response->getStatusCode());
    }

    public function testOptionsRequestDoesNotCallHandler(): void
    {
        $middleware = new CorsMiddleware(new HttpFactory());
        $called = false;
        $handler = new class($called) implements RequestHandlerInterface {
            public function __construct(
                private bool &$called,
            ) {}

            public function handle($request): ResponseInterface
            {
                $this->called = true;
                return new Response();
            }
        };

        $middleware->process(new ServerRequest('OPTIONS', '/test'), $handler);

        $this->assertFalse($called);
    }

    public function testGetRequestPassesThroughWithCorsHeaders(): void
    {
        $middleware = new CorsMiddleware(new HttpFactory());
        $handler = $this->createHandler();

        $response = $middleware->process(new ServerRequest('GET', '/test'), $handler);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('*', $response->getHeaderLine('Access-Control-Allow-Origin'));
        $this->assertStringContainsString('GET', $response->getHeaderLine('Access-Control-Allow-Methods'));
        $this->assertStringContainsString('POST', $response->getHeaderLine('Access-Control-Allow-Methods'));
        $this->assertStringContainsString('Content-Type', $response->getHeaderLine('Access-Control-Allow-Headers'));
        $this->assertStringContainsString('X-Debug-Token', $response->getHeaderLine('Access-Control-Allow-Headers'));
        $this->assertSame('86400', $response->getHeaderLine('Access-Control-Max-Age'));
    }

    public function testPostRequestGetsCorsHeaders(): void
    {
        $middleware = new CorsMiddleware(new HttpFactory());
        $handler = $this->createHandler();

        $response = $middleware->process(new ServerRequest('POST', '/test'), $handler);

        $this->assertSame('*', $response->getHeaderLine('Access-Control-Allow-Origin'));
    }

    public function testAllowlistedOriginIsEchoedBack(): void
    {
        $middleware = new CorsMiddleware(new HttpFactory(), ['http://allowed.example']);
        $request = new ServerRequest('GET', '/test')->withHeader('Origin', 'http://allowed.example');

        $response = $middleware->process($request, $this->createHandler());

        $this->assertSame('http://allowed.example', $response->getHeaderLine('Access-Control-Allow-Origin'));
        $this->assertSame('Origin', $response->getHeaderLine('Vary'));
    }

    public function testNonAllowlistedOriginGetsNoCorsHeader(): void
    {
        $middleware = new CorsMiddleware(new HttpFactory(), ['http://allowed.example']);
        $request = new ServerRequest('GET', '/test')->withHeader('Origin', 'http://attacker.example');

        $response = $middleware->process($request, $this->createHandler());

        $this->assertFalse($response->hasHeader('Access-Control-Allow-Origin'));
    }

    public function testEmptyAllowlistDisablesCorsEntirely(): void
    {
        $middleware = new CorsMiddleware(new HttpFactory(), []);
        $request = new ServerRequest('GET', '/test')->withHeader('Origin', 'http://any.example');

        $response = $middleware->process($request, $this->createHandler());

        $this->assertFalse($response->hasHeader('Access-Control-Allow-Origin'));
        $this->assertFalse($response->hasHeader('Access-Control-Allow-Methods'));
    }

    public function testWildcardConstantAllowsAnyOrigin(): void
    {
        $middleware = new CorsMiddleware(new HttpFactory(), [CorsMiddleware::WILDCARD]);
        $request = new ServerRequest('GET', '/test')->withHeader('Origin', 'http://any.example');

        $response = $middleware->process($request, $this->createHandler());

        $this->assertSame('*', $response->getHeaderLine('Access-Control-Allow-Origin'));
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
