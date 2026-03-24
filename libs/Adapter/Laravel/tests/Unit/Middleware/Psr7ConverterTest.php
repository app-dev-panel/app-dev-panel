<?php

declare(strict_types=1);

namespace AppDevPanel\Adapter\Laravel\Tests\Unit\Middleware;

use AppDevPanel\Adapter\Laravel\Middleware\Psr7Converter;
use Illuminate\Http\Request;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Response;

final class Psr7ConverterTest extends TestCase
{
    public function testConvertRequest(): void
    {
        $request = Request::create('https://example.com/api/users?page=1', 'GET', server: [
            'HTTP_ACCEPT' => 'application/json',
        ]);

        $converter = new Psr7Converter();
        $psrRequest = $converter->convertRequest($request);

        $this->assertSame('GET', $psrRequest->getMethod());
        $this->assertSame(['page' => '1'], $psrRequest->getQueryParams());
    }

    public function testConvertRequestWithBody(): void
    {
        $request = Request::create('https://example.com/api/users', 'POST', content: '{"name":"John"}');
        $request->headers->set('Content-Type', 'application/json');

        $converter = new Psr7Converter();
        $psrRequest = $converter->convertRequest($request);

        $this->assertSame('POST', $psrRequest->getMethod());
        $this->assertSame('{"name":"John"}', (string) $psrRequest->getBody());
    }

    public function testConvertRequestPreservesHeaders(): void
    {
        $request = Request::create('https://example.com/');
        $request->headers->set('X-Custom', 'test-value');

        $converter = new Psr7Converter();
        $psrRequest = $converter->convertRequest($request);

        $this->assertSame('test-value', $psrRequest->getHeaderLine('x-custom'));
    }

    public function testConvertResponse(): void
    {
        $response = new Response('{"result":"ok"}', 200, ['Content-Type' => 'application/json']);

        $converter = new Psr7Converter();
        $psrResponse = $converter->convertResponse($response);

        $this->assertSame(200, $psrResponse->getStatusCode());
        $this->assertSame('{"result":"ok"}', (string) $psrResponse->getBody());
        $this->assertStringContainsString('application/json', $psrResponse->getHeaderLine('content-type'));
    }

    public function testConvertResponsePreservesStatusCode(): void
    {
        $response = new Response('', 404);

        $converter = new Psr7Converter();
        $psrResponse = $converter->convertResponse($response);

        $this->assertSame(404, $psrResponse->getStatusCode());
    }

    public function testConvertResponseWithMultipleHeaders(): void
    {
        $response = new Response('body', 200, [
            'X-Request-Id' => 'abc123',
            'X-Debug-Id' => 'debug-456',
        ]);

        $converter = new Psr7Converter();
        $psrResponse = $converter->convertResponse($response);

        $this->assertSame('abc123', $psrResponse->getHeaderLine('x-request-id'));
        $this->assertSame('debug-456', $psrResponse->getHeaderLine('x-debug-id'));
    }
}
