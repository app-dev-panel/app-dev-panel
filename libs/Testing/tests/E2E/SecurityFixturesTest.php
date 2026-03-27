<?php

declare(strict_types=1);

namespace AppDevPanel\Testing\Tests\E2E;

use AppDevPanel\Testing\Fixture\FixtureRegistry;
use PHPUnit\Framework\Attributes\Group;

/**
 * E2E tests for security collector fixtures.
 *
 * Run: PLAYGROUND_URL=http://127.0.0.1:8102 php vendor/bin/phpunit --testsuite Fixtures --group security
 */
#[Group('fixtures')]
#[Group('security')]
#[Group('advanced')]
final class SecurityFixturesTest extends FixtureTestCase
{
    public function testSecurityBasicFixture(): void
    {
        $fixtures = FixtureRegistry::byTag('advanced');
        $securityFixture = null;
        foreach ($fixtures as $fixture) {
            if ($fixture->name === 'security:basic') {
                $securityFixture = $fixture;
                break;
            }
        }

        self::assertNotNull($securityFixture, 'security:basic fixture must exist in registry');
        $this->runFixture($securityFixture);
    }
}
