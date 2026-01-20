<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Debug\Tests\Unit\Collector;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\UriInterface;
use Yiisoft\Yii\Debug\Collector\CollectorInterface;
use Yiisoft\Yii\Debug\Collector\TimelineCollector;
use Yiisoft\Yii\Debug\Collector\Web\RequestCollector;
use Yiisoft\Yii\Debug\Tests\Shared\AbstractCollectorTestCase;
use Yiisoft\Yii\Http\Event\AfterRequest;
use Yiisoft\Yii\Http\Event\BeforeRequest;

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
        $bodyMock->method('tell')
            ->willReturn(1);

        $uriMock->method('getPath')
            ->willReturn('url');
        $uriMock->method('getQuery')
            ->willReturn('');
        $uriMock->method('__toString')
            ->willReturn('http://test.site/url');

        $requestMock->method('getMethod')
            ->willReturn('GET');
        $requestMock->method('getHeaders')
            ->willReturn([]);
        $requestMock->method('getHeaderLine')
            ->willReturn('');
        $requestMock->method('getUri')
            ->willReturn($uriMock);
        $requestMock->method('getBody')
            ->willReturn($bodyMock);

        $responseMock->method('getStatusCode')
            ->willReturn(200);
        $responseMock->method('getHeaders')
            ->willReturn([]);
        $responseMock->method('getBody')
            ->willReturn($bodyMock);

        $collector->collect(new BeforeRequest($requestMock));
        $collector->collect(new AfterRequest($responseMock));
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
}
