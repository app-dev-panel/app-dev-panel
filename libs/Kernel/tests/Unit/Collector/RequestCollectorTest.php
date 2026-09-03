<?php

declare(strict_types=1);

namespace AppDevPanel\Kernel\Tests\Unit\Collector;

use AppDevPanel\Kernel\Collector\CollectorInterface;
use AppDevPanel\Kernel\Collector\TimelineCollector;
use AppDevPanel\Kernel\Collector\Web\RequestCollector;
use AppDevPanel\Kernel\Tests\Shared\AbstractCollectorTestCase;
use GuzzleHttp\Psr7\NoSeekStream;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Psr7\ServerRequest;
use GuzzleHttp\Psr7\Utils;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\UriInterface;

final class RequestCollectorTest extends AbstractCollectorTestCase
{
    /**
     * @param CollectorInterface|RequestCollector $collector
     */
    protected function collectTestData(CollectorInterface $collector): void
    {
        $requestMock = $this->createMock(ServerRequestInterface::class);
        $responseMock = $this->createMock(ResponseInterface::class);
        $uriMock = $this->createMock(UriInterface::class);
        $bodyMock = $this->createMock(StreamInterface::class);
        $bodyMock->method('tell')->willReturn(1);

        $uriMock->method('getPath')->willReturn('url');
        $uriMock->method('getQuery')->willReturn('');
        $uriMock->method('__toString')->willReturn('http://test.site/url');

        $requestMock->method('getMethod')->willReturn('GET');
        $requestMock->method('getHeaders')->willReturn([]);
        $requestMock->method('getHeaderLine')->willReturn('');
        $requestMock->method('getUri')->willReturn($uriMock);
        $requestMock->method('getBody')->willReturn($bodyMock);
        $requestMock->method('getServerParams')->willReturn([]);

        $responseMock->method('getStatusCode')->willReturn(200);
        $responseMock->method('getHeaders')->willReturn([]);
        $responseMock->method('getBody')->willReturn($bodyMock);

        $collector->collectRequest($requestMock);
        $collector->collectResponse($responseMock);
    }

    protected function getCollector(): CollectorInterface
    {
        return new RequestCollector(new TimelineCollector());
    }

    protected function checkCollectedData(array $data): void
    {
        parent::checkCollectedData($data);
        $this->assertInstanceOf(ServerRequestInterface::class, $data['request']);
        $this->assertInstanceOf(ResponseInterface::class, $data['response']);
    }

    protected function checkSummaryData(array $data): void
    {
        parent::checkSummaryData($data);
        $this->assertEquals('http://test.site/url', $data['request']['url']);
        $this->assertEquals('GET', $data['request']['method']);
        $this->assertEquals(200, $data['response']['statusCode']);
    }

    public function testTextualBodiesAreCapturedAndStreamPositionRestored(): void
    {
        $collector = $this->startedCollector();
        $request = new ServerRequest('POST', 'http://test.site/api', ['Content-Type' => 'application/json'], '{"a":1}');
        $response = new Response(201, ['Content-Type' => 'text/html; charset=utf-8'], '<p>ok</p>');
        $response->getBody()->read(3); // simulate the app having consumed part of the stream

        $collector->collectRequest($request);
        $collector->collectResponse($response);
        $data = $collector->getCollected();

        $this->assertStringStartsWith('POST /api HTTP/1.1', $data['requestRaw']);
        $this->assertStringEndsWith("\r\n\r\n{\"a\":1}", $data['requestRaw']);
        $this->assertStringStartsWith('HTTP/1.1 201 Created', $data['responseRaw']);
        $this->assertStringEndsWith("\r\n\r\n<p>ok</p>", $data['responseRaw']);
        $this->assertSame(3, $response->getBody()->tell(), 'Stream position must be restored, not rewound');
    }

    public function testBinaryResponseBodyIsNotMaterialised(): void
    {
        $collector = $this->startedCollector();
        $png = "\x89PNG\r\n\x1a\n" . random_bytes(512);
        $response = new Response(200, ['Content-Type' => 'image/png', 'Content-Length' => '520'], $png);

        $collector->collectResponse($response);
        $raw = $collector->getCollected()['responseRaw'];

        $this->assertStringContainsString('[binary body omitted: image/png, 520 bytes]', $raw);
        $this->assertStringNotContainsString('PNG', substr($raw, (int) strpos($raw, "\r\n\r\n")));
    }

    public function testOversizedBodyIsNotMaterialised(): void
    {
        $collector = new RequestCollector(new TimelineCollector(), maxBodySize: 16);
        $collector->startup();
        $response = new Response(200, ['Content-Type' => 'text/plain'], str_repeat('x', 17));

        $collector->collectResponse($response);

        $this->assertStringEndsWith(
            '[body omitted: 17 bytes exceeds 16 byte limit]',
            $collector->getCollected()['responseRaw'],
        );
    }

    public function testUnknownSizeBodyIsCappedWhileReading(): void
    {
        $collector = new RequestCollector(new TimelineCollector(), maxBodySize: 8);
        $collector->startup();

        $stream = $this->createMock(StreamInterface::class);
        $stream->method('getSize')->willReturn(null);
        $stream->method('isSeekable')->willReturn(true);
        $stream->method('isReadable')->willReturn(true);
        $stream->method('tell')->willReturn(0);
        $stream->method('eof')->willReturn(false);
        $stream->method('read')->willReturnCallback(static fn(int $length): string => str_repeat('y', $length));

        $response = $this->createMock(ResponseInterface::class);
        $response->method('getStatusCode')->willReturn(200);
        $response->method('getProtocolVersion')->willReturn('1.1');
        $response->method('getReasonPhrase')->willReturn('OK');
        $response->method('getHeaders')->willReturn([]);
        $response->method('getHeaderLine')->willReturn('');
        $response->method('getBody')->willReturn($stream);

        $collector->collectResponse($response);

        $this->assertStringEndsWith('[body omitted: exceeds 8 byte limit]', $collector->getCollected()['responseRaw']);
    }

    public function testNonSeekableBodyDoesNotThrow(): void
    {
        $collector = $this->startedCollector();
        $handle = fopen('php://temp', 'r+');
        fwrite($handle, 'streamed');
        rewind($handle);
        $response = new Response(200, ['Content-Type' => 'text/plain'], new NoSeekStream(Utils::streamFor($handle)));

        $collector->collectResponse($response);
        $raw = $collector->getCollected()['responseRaw'];

        $this->assertStringContainsString('[body omitted: stream is not seekable, 8 bytes]', $raw);
        fclose($handle);
    }

    public function testThrowingBodyYieldsPlaceholderInsteadOfException(): void
    {
        $collector = $this->startedCollector();

        $stream = $this->createMock(StreamInterface::class);
        $stream->method('getSize')->willThrowException(new \RuntimeException('stream detached'));

        $response = $this->createMock(ResponseInterface::class);
        $response->method('getStatusCode')->willReturn(200);
        $response->method('getProtocolVersion')->willReturn('1.1');
        $response->method('getReasonPhrase')->willReturn('OK');
        $response->method('getHeaders')->willReturn(['X-Test' => ['1']]);
        $response->method('getHeaderLine')->willReturn('');
        $response->method('getBody')->willReturn($stream);

        $collector->collectResponse($response);
        $raw = $collector->getCollected()['responseRaw'];

        $this->assertStringStartsWith("HTTP/1.1 200 OK\r\nX-Test: 1\r\n\r\n", $raw);
        $this->assertStringEndsWith('[body unavailable: stream detached]', $raw);
    }

    private function startedCollector(): RequestCollector
    {
        $collector = new RequestCollector(new TimelineCollector());
        $collector->startup();

        return $collector;
    }
}
