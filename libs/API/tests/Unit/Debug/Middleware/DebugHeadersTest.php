<?php

declare(strict_types=1);

namespace AppDevPanel\Api\Tests\Unit\Debug\Middleware;

use AppDevPanel\Api\Debug\Middleware\DebugHeaders;
use AppDevPanel\Kernel\DebuggerIdGenerator;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Psr7\ServerRequest;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\RequestHandlerInterface;

final class DebugHeadersTest extends TestCase
{
    public function testHeaders(): void
    {
        $idGenerator = new DebuggerIdGenerator();
        $expectedId = $idGenerator->getId();

        $middleware = new DebugHeaders($idGenerator);
        $response = $middleware->process(new ServerRequest('GET', '/test'), $this->createRequestHandler());

        $this->assertSame($expectedId, $response->getHeaderLine('X-Debug-Id'));
        $this->assertSame('/debug/api/view/' . $expectedId, $response->getHeaderLine('X-Debug-Link'));
    }

    protected function createRequestHandler(): RequestHandlerInterface
    {
        return new class() implements RequestHandlerInterface {
            public function handle($request): ResponseInterface
            {
                return new Response();
            }
        };
    }
}
