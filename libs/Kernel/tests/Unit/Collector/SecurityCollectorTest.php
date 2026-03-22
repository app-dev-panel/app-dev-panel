<?php

declare(strict_types=1);

namespace AppDevPanel\Kernel\Tests\Unit\Collector;

use AppDevPanel\Kernel\Collector\CollectorInterface;
use AppDevPanel\Kernel\Collector\SecurityCollector;
use AppDevPanel\Kernel\Tests\Shared\AbstractCollectorTestCase;

final class SecurityCollectorTest extends AbstractCollectorTestCase
{
    protected function getCollector(): CollectorInterface
    {
        return new SecurityCollector();
    }

    protected function collectTestData(CollectorInterface $collector): void
    {
        assert($collector instanceof SecurityCollector, 'Expected SecurityCollector instance');
        $collector->collectUser('admin@example.com', ['ROLE_ADMIN', 'ROLE_USER'], true);
        $collector->collectFirewall('main');
        $collector->logAccessDecision('ROLE_ADMIN', 'App\\Entity\\User', 'ACCESS_GRANTED', [
            ['voter' => 'RoleVoter', 'result' => 'ACCESS_GRANTED'],
        ]);
    }

    protected function checkCollectedData(array $data): void
    {
        parent::checkCollectedData($data);

        $this->assertSame('admin@example.com', $data['username']);
        $this->assertSame(['ROLE_ADMIN', 'ROLE_USER'], $data['roles']);
        $this->assertTrue($data['authenticated']);
        $this->assertSame('main', $data['firewallName']);
        $this->assertCount(1, $data['accessDecisions']);

        $decision = $data['accessDecisions'][0];
        $this->assertSame('ROLE_ADMIN', $decision['attribute']);
        $this->assertSame('ACCESS_GRANTED', $decision['result']);
    }

    protected function checkSummaryData(array $data): void
    {
        parent::checkSummaryData($data);

        $this->assertArrayHasKey('security', $data);
        $this->assertSame('admin@example.com', $data['security']['username']);
        $this->assertTrue($data['security']['authenticated']);
        $this->assertSame(['ROLE_ADMIN', 'ROLE_USER'], $data['security']['roles']);
    }
}
