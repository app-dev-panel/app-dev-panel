<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Debug\Tests\Unit\Collector;

use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use stdClass;
use Yiisoft\Yii\Debug\Collector\HttpClientCollector;
use Yiisoft\Yii\Debug\Collector\HttpClientInterfaceProxy;
use Yiisoft\Yii\Debug\Collector\TimelineCollector;

final class HttpClientInterfaceProxyTest extends TestCase
{
    public function testSendRequest(): void
    {
        $request = new Request('GET', 'http://example.com');
        $response = new Response(200, [], 'test');

        $client = $this->createMock(ClientInterface::class);
        $client->expects($this->once())
            ->method('sendRequest')
            ->with($request)
            ->willReturn($response);
        $collector = new HttpClientCollector(new TimelineCollector());
        $collector->startup();

        $proxy = new HttpClientInterfaceProxy($client, $collector);

        $newResponse = $proxy->sendRequest($request);

        $this->assertSame($newResponse, $response);
        $this->assertCount(1, $collector->getCollected());
    }

    public function testProxyDecoratedCall(): void
    {
        $httpClient = new class () implements ClientInterface {
            public $var = null;

            public function getProxiedCall(): string
            {
                return 'ok';
            }

            public function setProxiedCall($args): mixed
            {
                return $args;
            }

            public function sendRequest(RequestInterface $request): ResponseInterface
            {
            }
        };
        $collector = new HttpClientCollector(new TimelineCollector());
        $proxy = new HttpClientInterfaceProxy($httpClient, $collector);

        $this->assertEquals('ok', $proxy->getProxiedCall());
        $this->assertEquals($args = [1, new stdClass(), 'string'], $proxy->setProxiedCall($args));
        $proxy->var = '123';
        $this->assertEquals('123', $proxy->var);
    }
}
