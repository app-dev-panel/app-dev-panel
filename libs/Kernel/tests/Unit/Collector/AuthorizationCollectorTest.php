<?php

declare(strict_types=1);

namespace AppDevPanel\Kernel\Tests\Unit\Collector;

use AppDevPanel\Kernel\Collector\AuthorizationCollector;
use AppDevPanel\Kernel\Collector\CollectorInterface;
use AppDevPanel\Kernel\Tests\Shared\AbstractCollectorTestCase;

final class AuthorizationCollectorTest extends AbstractCollectorTestCase
{
    protected function getCollector(): CollectorInterface
    {
        return new AuthorizationCollector();
    }

    protected function collectTestData(CollectorInterface $collector): void
    {
        assert($collector instanceof AuthorizationCollector, 'Expected AuthorizationCollector instance');
        $collector->collectUser('admin@example.com', ['ROLE_ADMIN', 'ROLE_USER'], true);
        $collector->collectFirewall('main');
        $collector->collectToken('jwt', ['sub' => '123', 'iss' => 'app'], '2026-12-31T23:59:59Z');
        $collector->collectGuard('web', 'users', ['driver' => 'session']);
        $collector->collectRoleHierarchy(['ROLE_ADMIN' => ['ROLE_USER', 'ROLE_EDITOR']]);
        $collector->collectEffectiveRoles(['ROLE_ADMIN', 'ROLE_USER', 'ROLE_EDITOR']);
        $collector->collectAuthenticationEvent('login', 'form_login', 'success', ['ip' => '127.0.0.1']);
        $collector->logAccessDecision(
            'ROLE_ADMIN',
            'App\\Entity\\User',
            'ACCESS_GRANTED',
            [
                ['voter' => 'RoleVoter', 'result' => 'ACCESS_GRANTED'],
            ],
            0.002,
            ['route' => '/admin'],
        );
    }

    protected function checkCollectedData(array $data): void
    {
        parent::checkCollectedData($data);

        $this->assertSame('admin@example.com', $data['username']);
        $this->assertSame(['ROLE_ADMIN', 'ROLE_USER'], $data['roles']);
        $this->assertTrue($data['authenticated']);
        $this->assertSame('main', $data['firewallName']);

        // Token
        $this->assertNotNull($data['token']);
        $this->assertSame('jwt', $data['token']['type']);
        $this->assertSame(['sub' => '123', 'iss' => 'app'], $data['token']['attributes']);
        $this->assertSame('2026-12-31T23:59:59Z', $data['token']['expiresAt']);

        // Guards
        $this->assertCount(1, $data['guards']);
        $this->assertSame('web', $data['guards'][0]['name']);
        $this->assertSame('users', $data['guards'][0]['provider']);

        // Role hierarchy
        $this->assertSame(['ROLE_ADMIN' => ['ROLE_USER', 'ROLE_EDITOR']], $data['roleHierarchy']);
        $this->assertSame(['ROLE_ADMIN', 'ROLE_USER', 'ROLE_EDITOR'], $data['effectiveRoles']);

        // Authentication events
        $this->assertCount(1, $data['authenticationEvents']);
        $this->assertSame('login', $data['authenticationEvents'][0]['type']);
        $this->assertSame('form_login', $data['authenticationEvents'][0]['provider']);
        $this->assertSame('success', $data['authenticationEvents'][0]['result']);
        $this->assertIsFloat($data['authenticationEvents'][0]['time']);
        $this->assertSame(['ip' => '127.0.0.1'], $data['authenticationEvents'][0]['details']);

        // Access decisions (expanded)
        $this->assertCount(1, $data['accessDecisions']);
        $decision = $data['accessDecisions'][0];
        $this->assertSame('ROLE_ADMIN', $decision['attribute']);
        $this->assertSame('ACCESS_GRANTED', $decision['result']);
        $this->assertSame(0.002, $decision['duration']);
        $this->assertSame(['route' => '/admin'], $decision['context']);

        // Impersonation (not set)
        $this->assertNull($data['impersonation']);
    }

    protected function checkSummaryData(array $data): void
    {
        parent::checkSummaryData($data);

        $this->assertArrayHasKey('authorization', $data);
        $this->assertSame('admin@example.com', $data['authorization']['username']);
        $this->assertTrue($data['authorization']['authenticated']);
        $this->assertSame(['ROLE_ADMIN', 'ROLE_USER'], $data['authorization']['roles']);

        // Summary access decisions
        $this->assertSame(1, $data['authorization']['accessDecisions']['total']);
        $this->assertSame(1, $data['authorization']['accessDecisions']['granted']);
        $this->assertSame(0, $data['authorization']['accessDecisions']['denied']);

        // Auth events count
        $this->assertSame(1, $data['authorization']['authEvents']);
    }

    public function testImpersonation(): void
    {
        $collector = new AuthorizationCollector();
        $collector->startup();
        $collector->collectUser('impersonated@example.com', ['ROLE_USER'], true);
        $collector->collectImpersonation('admin@example.com', 'impersonated@example.com');

        $data = $collector->getCollected();

        $this->assertNotNull($data['impersonation']);
        $this->assertSame('admin@example.com', $data['impersonation']['originalUser']);
        $this->assertSame('impersonated@example.com', $data['impersonation']['impersonatedUser']);
    }

    public function testMultipleAuthenticationEvents(): void
    {
        $collector = new AuthorizationCollector();
        $collector->startup();
        $collector->collectAuthenticationEvent('failure', 'form_login', 'failure', ['reason' => 'bad_credentials']);
        $collector->collectAuthenticationEvent('login', 'form_login', 'success');

        $data = $collector->getCollected();

        $this->assertCount(2, $data['authenticationEvents']);
        $this->assertSame('failure', $data['authenticationEvents'][0]['type']);
        $this->assertSame('login', $data['authenticationEvents'][1]['type']);
    }

    public function testMultipleGuards(): void
    {
        $collector = new AuthorizationCollector();
        $collector->startup();
        $collector->collectGuard('web', 'users', ['driver' => 'session']);
        $collector->collectGuard('api', 'tokens', ['driver' => 'token']);

        $data = $collector->getCollected();

        $this->assertCount(2, $data['guards']);
        $this->assertSame('web', $data['guards'][0]['name']);
        $this->assertSame('api', $data['guards'][1]['name']);
    }

    public function testAccessDecisionSummaryCounts(): void
    {
        $collector = new AuthorizationCollector();
        $collector->startup();
        $collector->logAccessDecision('VIEW', 'Post', 'ACCESS_GRANTED');
        $collector->logAccessDecision('EDIT', 'Post', 'ACCESS_DENIED');
        $collector->logAccessDecision('DELETE', 'Post', 'ACCESS_DENIED');
        $collector->logAccessDecision('LIST', 'Post', 'ACCESS_GRANTED');

        $summary = $collector->getSummary();

        $this->assertSame(4, $summary['authorization']['accessDecisions']['total']);
        $this->assertSame(2, $summary['authorization']['accessDecisions']['granted']);
        $this->assertSame(2, $summary['authorization']['accessDecisions']['denied']);
    }

    public function testTokenWithoutExpiry(): void
    {
        $collector = new AuthorizationCollector();
        $collector->startup();
        $collector->collectToken('api_key', ['key_id' => 'abc']);

        $data = $collector->getCollected();

        $this->assertSame('api_key', $data['token']['type']);
        $this->assertNull($data['token']['expiresAt']);
    }

    public function testCollectImpersonationWhenInactive(): void
    {
        $collector = new AuthorizationCollector();
        $baselineCollected = $collector->getCollected();
        $baselineSummary = method_exists($collector, 'getSummary') ? $collector->getSummary() : null;
        // Not started
        $collector->collectImpersonation('admin', 'user');

        $this->assertSame($baselineCollected, $collector->getCollected());
    }
}
