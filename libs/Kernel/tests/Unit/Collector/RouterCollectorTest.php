<?php

declare(strict_types=1);

namespace AppDevPanel\Kernel\Tests\Unit\Collector;

use AppDevPanel\Kernel\Collector\CollectorInterface;
use AppDevPanel\Kernel\Collector\RouterCollector;
use AppDevPanel\Kernel\Tests\Shared\AbstractCollectorTestCase;

final class RouterCollectorTest extends AbstractCollectorTestCase
{
    protected function getCollector(): CollectorInterface
    {
        return new RouterCollector();
    }

    protected function collectTestData(CollectorInterface $collector): void
    {
        assert($collector instanceof RouterCollector, 'Expected RouterCollector instance');
        $collector->collectMatchedRoute([
            'matchTime' => 0.005,
            'name' => 'home',
            'pattern' => '/',
            'arguments' => [],
            'host' => 'localhost',
            'uri' => 'http://localhost/',
            'action' => 'App\\Controller\\HomeController::index',
            'middlewares' => ['App\\Middleware\\Auth'],
        ]);
        $collector->collectRoutes([['name' => 'home', 'pattern' => '/'], ['name' => 'about', 'pattern' => '/about']], [[
            '/' => 'home',
            '/about' => 'about',
        ]]);
    }

    protected function checkCollectedData(array $data): void
    {
        parent::checkCollectedData($data);

        $this->assertArrayHasKey('currentRoute', $data);
        $this->assertSame('home', $data['currentRoute']['name']);
        $this->assertSame('/', $data['currentRoute']['pattern']);
        $this->assertSame(0.005, $data['currentRoute']['matchTime']);

        $this->assertArrayHasKey('routes', $data);
        $this->assertCount(2, $data['routes']);
        $this->assertArrayHasKey('routesTree', $data);
    }

    protected function checkSummaryData(array $data): void
    {
        parent::checkSummaryData($data);

        $this->assertArrayHasKey('router', $data);
        $this->assertSame('home', $data['router']['name']);
        $this->assertSame('/', $data['router']['pattern']);
    }

    public function testCollectMatchTime(): void
    {
        $collector = new RouterCollector();
        $collector->startup();

        $collector->collectMatchTime(0.123);

        $data = $collector->getCollected();
        $this->assertNull($data['currentRoute']);
        // routes not set, so routeTime not in result
        $this->assertArrayNotHasKey('routeTime', $data);
    }

    public function testCollectMatchTimeWhenInactive(): void
    {
        $collector = new RouterCollector();
        $baselineCollected = $collector->getCollected();
        $baselineSummary = method_exists($collector, 'getSummary') ? $collector->getSummary() : null;
        // Not started
        $collector->collectMatchTime(0.5);

        $this->assertSame($baselineCollected, $collector->getCollected());
    }

    public function testGetSummaryWithNoMatchedRoute(): void
    {
        $collector = new RouterCollector();
        $collector->startup();

        // No route collected — summary should be empty
        $summary = $collector->getSummary();
        $this->assertSame([], $summary);
    }

    public function testCollectMatchTimeWithRoutes(): void
    {
        $collector = new RouterCollector();
        $collector->startup();

        $collector->collectMatchTime(0.042);
        $collector->collectRoutes([['name' => 'home', 'pattern' => '/']], null);

        $data = $collector->getCollected();
        $this->assertSame(0.042, $data['routeTime']);
        $this->assertNull($data['routesTree']);
    }
}
