<?php

declare(strict_types=1);

namespace AppDevPanel\Adapter\Symfony\Tests\Unit\Collector;

use AppDevPanel\Adapter\Symfony\Collector\SymfonyRequestCollector;
use AppDevPanel\Kernel\Collector\CollectorInterface;
use AppDevPanel\Kernel\Collector\TimelineCollector;
use AppDevPanel\Kernel\Tests\Shared\AbstractCollectorTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

final class SymfonyRequestCollectorTest extends AbstractCollectorTestCase
{
    protected function getCollector(): CollectorInterface
    {
        return new SymfonyRequestCollector(new TimelineCollector());
    }

    /**
     * @param CollectorInterface|SymfonyRequestCollector $collector
     */
    protected function collectTestData(CollectorInterface $collector): void
    {
        $request = Request::create(
            '/api/users',
            'GET',
            [],
            [],
            [],
            [
                'REMOTE_ADDR' => '127.0.0.1',
                'HTTP_X_REQUESTED_WITH' => 'XMLHttpRequest',
            ],
        );
        $request->attributes->set('_route', 'api_users');
        $request->attributes->set('_controller', 'App\\Controller\\UserController::list');

        $collector->collectRequest($request);

        $response = new Response('{"users":[]}', 200, ['Content-Type' => 'application/json']);
        $collector->collectResponse($response);
    }

    protected function checkCollectedData(array $data): void
    {
        parent::checkCollectedData($data);

        $this->assertSame('/api/users', $data['requestPath']);
        $this->assertSame('GET', $data['requestMethod']);
        $this->assertTrue($data['requestIsAjax']);
        $this->assertSame('127.0.0.1', $data['userIp']);
        $this->assertSame('api_users', $data['routeName']);
        $this->assertSame('App\\Controller\\UserController::list', $data['controllerName']);
        $this->assertSame(200, $data['responseStatusCode']);
        $this->assertIsArray($data['requestHeaders']);
        $this->assertIsArray($data['responseHeaders']);
    }

    protected function checkSummaryData(array $data): void
    {
        parent::checkSummaryData($data);

        $this->assertArrayHasKey('request', $data);
        $this->assertArrayHasKey('response', $data);
        $this->assertSame('/api/users', $data['request']['path']);
        $this->assertSame('GET', $data['request']['method']);
        $this->assertSame('api_users', $data['request']['route']);
        $this->assertSame(200, $data['response']['statusCode']);
    }

    public function testLargeResponseContentIsNotCaptured(): void
    {
        $collector = new SymfonyRequestCollector(new TimelineCollector());
        $collector->startup();

        $request = Request::create('/test');
        $collector->collectRequest($request);

        $largeContent = str_repeat('x', 100_000);
        $response = new Response($largeContent, 200);
        $collector->collectResponse($response);

        $data = $collector->getCollected();
        $this->assertNull($data['responseContent']);
    }

    public function testSmallResponseContentIsCaptured(): void
    {
        $collector = new SymfonyRequestCollector(new TimelineCollector());
        $collector->startup();

        $request = Request::create('/test');
        $collector->collectRequest($request);

        $response = new Response('small body', 200);
        $collector->collectResponse($response);

        $data = $collector->getCollected();
        $this->assertSame('small body', $data['responseContent']);
    }
}
