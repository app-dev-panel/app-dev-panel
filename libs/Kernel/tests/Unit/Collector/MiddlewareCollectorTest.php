<?php

declare(strict_types=1);

namespace AppDevPanel\Kernel\Tests\Unit\Collector;

use AppDevPanel\Kernel\Collector\CollectorInterface;
use AppDevPanel\Kernel\Collector\MiddlewareCollector;
use AppDevPanel\Kernel\Collector\TimelineCollector;
use AppDevPanel\Kernel\Tests\Shared\AbstractCollectorTestCase;

final class MiddlewareCollectorTest extends AbstractCollectorTestCase
{
    protected function getCollector(): CollectorInterface
    {
        return new MiddlewareCollector(new TimelineCollector());
    }

    protected function collectTestData(CollectorInterface $collector): void
    {
        assert($collector instanceof MiddlewareCollector, 'Expected MiddlewareCollector instance');
        $collector->collectBefore('App\\Middleware\\AuthMiddleware', 1000.0, 1024);
        $collector->collectBefore('App\\Middleware\\CsrfMiddleware', 1000.1, 2048);
        $collector->collectBefore('App\\Controller\\HomeController', 1000.2, 3072);
        $collector->collectAfter('App\\Controller\\HomeController', 1000.3, 4096);
        $collector->collectAfter('App\\Middleware\\CsrfMiddleware', 1000.4, 5120);
        $collector->collectAfter('App\\Middleware\\AuthMiddleware', 1000.5, 6144);
    }

    protected function checkCollectedData(array $data): void
    {
        parent::checkCollectedData($data);

        $this->assertArrayHasKey('beforeStack', $data);
        $this->assertArrayHasKey('afterStack', $data);
        $this->assertArrayHasKey('actionHandler', $data);

        // beforeStack should NOT include the action handler (last entry popped)
        $this->assertCount(2, $data['beforeStack']);
        $this->assertSame('App\\Middleware\\AuthMiddleware', $data['beforeStack'][0]['name']);
        $this->assertSame('App\\Middleware\\CsrfMiddleware', $data['beforeStack'][1]['name']);

        // afterStack should NOT include the action handler (first entry shifted)
        $this->assertCount(2, $data['afterStack']);

        // Action handler extracted from innermost before/after
        $this->assertSame('App\\Controller\\HomeController', $data['actionHandler']['name']);
    }

    protected function checkSummaryData(array $data): void
    {
        parent::checkSummaryData($data);

        $this->assertArrayHasKey('middleware', $data);
        $this->assertSame(2, $data['middleware']['total']);
    }
}
