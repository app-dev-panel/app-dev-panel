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
use Psr\Log\LoggerInterface;

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
        $this->assertSame('application/json', $response->getHeaderLine('Content-Type'));

        $body = json_decode((string) $response->getBody(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertFalse($body['success']);
        $this->assertStringContainsString('authentication token', $body['error']);
    }

    public function testHandlerIsNotCalledWhenTokenInvalid(): void
    {
        $middleware = new TokenAuthMiddleware(new HttpFactory(), new HttpFactory(), 'secret-token');

        $handler = $this->createMock(RequestHandlerInterface::class);
        $handler->expects($this->never())->method('handle');

        $request = new ServerRequest('GET', '/test');
        $request = $request->withHeader('X-Debug-Token', 'bad-token');

        $middleware->process($request, $handler);
    }

    public function testHandlerIsNotCalledWhenTokenMissing(): void
    {
        $middleware = new TokenAuthMiddleware(new HttpFactory(), new HttpFactory(), 'my-token');

        $handler = $this->createMock(RequestHandlerInterface::class);
        $handler->expects($this->never())->method('handle');

        $middleware->process(new ServerRequest('GET', '/test'), $handler);
    }

    public function testHandlerIsCalledWhenTokenValid(): void
    {
        $middleware = new TokenAuthMiddleware(new HttpFactory(), new HttpFactory(), 'my-token');

        $handler = $this->createMock(RequestHandlerInterface::class);
        $handler->expects($this->once())->method('handle')->willReturn(new Response());

        $request = new ServerRequest('GET', '/test');
        $request = $request->withHeader('X-Debug-Token', 'my-token');

        $middleware->process($request, $handler);
    }

    public function testHandlerIsCalledWhenAuthDisabled(): void
    {
        $middleware = new TokenAuthMiddleware(new HttpFactory(), new HttpFactory(), '');

        $handler = $this->createMock(RequestHandlerInterface::class);
        $handler->expects($this->once())->method('handle')->willReturn(new Response());

        $middleware->process(new ServerRequest('POST', '/anything'), $handler);
    }

    public function testTimingSafeComparison(): void
    {
        // Test that near-match tokens still fail (timing-safe comparison via hash_equals)
        $middleware = new TokenAuthMiddleware(new HttpFactory(), new HttpFactory(), 'secret-token');
        $handler = $this->createPassthroughHandler();

        $request = new ServerRequest('GET', '/test');
        $request = $request->withHeader('X-Debug-Token', 'secret-tokes'); // one char different

        $response = $middleware->process($request, $handler);
        $this->assertSame(401, $response->getStatusCode());
    }

    public function testEmptyHeaderValueWhenTokenIsSet(): void
    {
        $middleware = new TokenAuthMiddleware(new HttpFactory(), new HttpFactory(), 'secret');
        $handler = $this->createPassthroughHandler();

        $request = new ServerRequest('GET', '/test');
        // X-Debug-Token header is present but empty
        $request = $request->withHeader('X-Debug-Token', '');

        $response = $middleware->process($request, $handler);
        $this->assertSame(401, $response->getStatusCode());
    }

    public function testResponseBodyIsValidJson(): void
    {
        $middleware = new TokenAuthMiddleware(new HttpFactory(), new HttpFactory(), 'token');
        $handler = $this->createPassthroughHandler();

        $response = $middleware->process(new ServerRequest('GET', '/'), $handler);

        $body = (string) $response->getBody();
        $decoded = json_decode($body, true, 512, JSON_THROW_ON_ERROR);

        $this->assertArrayHasKey('error', $decoded);
        $this->assertArrayHasKey('success', $decoded);
        $this->assertIsString($decoded['error']);
        $this->assertFalse($decoded['success']);
    }

    public function testEmptyTokenLogsInsecureWarningOnce(): void
    {
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())->method('warning')->with($this->stringContains('empty token'));

        $middleware = new TokenAuthMiddleware(new HttpFactory(), new HttpFactory(), '', $logger);
        $handler = $this->createPassthroughHandler();

        $middleware->process(new ServerRequest('GET', '/first'), $handler);
        $middleware->process(new ServerRequest('GET', '/second'), $handler);
    }

    public function testNonEmptyTokenDoesNotLogInsecureWarning(): void
    {
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->never())->method('warning');

        $middleware = new TokenAuthMiddleware(new HttpFactory(), new HttpFactory(), 'secret', $logger);
        $handler = $this->createPassthroughHandler();

        $request = new ServerRequest('GET', '/test')->withHeader('X-Debug-Token', 'secret');
        $middleware->process($request, $handler);
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
