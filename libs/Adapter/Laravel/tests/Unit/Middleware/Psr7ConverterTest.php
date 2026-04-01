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

    public function testConvertRequestWithEmptyBody(): void
    {
        $request = Request::create('https://example.com/api/data', 'POST', content: '');

        $converter = new Psr7Converter();
        $psrRequest = $converter->convertRequest($request);

        $this->assertSame('POST', $psrRequest->getMethod());
        // Empty body should not be set
        $this->assertSame('', (string) $psrRequest->getBody());
    }

    public function testConvertRequestPutMethod(): void
    {
        $request = Request::create('https://example.com/api/users/1', 'PUT', content: '{"name":"Jane"}');
        $request->headers->set('Content-Type', 'application/json');

        $converter = new Psr7Converter();
        $psrRequest = $converter->convertRequest($request);

        $this->assertSame('PUT', $psrRequest->getMethod());
        $this->assertSame('{"name":"Jane"}', (string) $psrRequest->getBody());
    }

    public function testConvertRequestDeleteMethod(): void
    {
        $request = Request::create('https://example.com/api/users/1', 'DELETE');

        $converter = new Psr7Converter();
        $psrRequest = $converter->convertRequest($request);

        $this->assertSame('DELETE', $psrRequest->getMethod());
    }

    public function testConvertRequestPatchMethod(): void
    {
        $request = Request::create('https://example.com/api/users/1', 'PATCH', content: '{"status":"active"}');

        $converter = new Psr7Converter();
        $psrRequest = $converter->convertRequest($request);

        $this->assertSame('PATCH', $psrRequest->getMethod());
        $this->assertSame('{"status":"active"}', (string) $psrRequest->getBody());
    }

    public function testConvertRequestPreservesMultipleQueryParams(): void
    {
        $request = Request::create('https://example.com/api/search?q=test&page=2&limit=10');

        $converter = new Psr7Converter();
        $psrRequest = $converter->convertRequest($request);

        $queryParams = $psrRequest->getQueryParams();
        $this->assertSame('test', $queryParams['q']);
        $this->assertSame('2', $queryParams['page']);
        $this->assertSame('10', $queryParams['limit']);
    }

    public function testConvertRequestPreservesUri(): void
    {
        $request = Request::create('https://example.com/api/v2/users?active=1');

        $converter = new Psr7Converter();
        $psrRequest = $converter->convertRequest($request);

        $uri = $psrRequest->getUri();
        $this->assertSame('/api/v2/users', $uri->getPath());
        $this->assertSame('active=1', $uri->getQuery());
    }

    public function testConvertResponseWithEmptyContent(): void
    {
        $response = new Response('', 204);

        $converter = new Psr7Converter();
        $psrResponse = $converter->convertResponse($response);

        $this->assertSame(204, $psrResponse->getStatusCode());
        $this->assertSame('', (string) $psrResponse->getBody());
    }

    public function testConvertResponseWithServerError(): void
    {
        $response = new Response('Internal Server Error', 500, [
            'Content-Type' => 'text/plain',
        ]);

        $converter = new Psr7Converter();
        $psrResponse = $converter->convertResponse($response);

        $this->assertSame(500, $psrResponse->getStatusCode());
        $this->assertSame('Internal Server Error', (string) $psrResponse->getBody());
    }

    public function testConvertResponseRedirect(): void
    {
        $response = new Response('', 302, [
            'Location' => 'https://example.com/login',
        ]);

        $converter = new Psr7Converter();
        $psrResponse = $converter->convertResponse($response);

        $this->assertSame(302, $psrResponse->getStatusCode());
        $this->assertSame('https://example.com/login', $psrResponse->getHeaderLine('location'));
    }
}
