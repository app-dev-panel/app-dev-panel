<?php

declare(strict_types=1);

namespace AppDevPanel\Testing\Tests\E2E;

use AppDevPanel\Testing\Fixture\FixtureRegistry;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;

/**
 * E2E tests for advanced fixtures (multi-collector, heavy load, HTTP client, filesystem).
 *
 * Run: PLAYGROUND_URL=http://127.0.0.1:8102 php vendor/bin/phpunit --testsuite Scenarios --group advanced
 */
#[Group('fixtures')]
#[Group('advanced')]
final class AdvancedFixturesTest extends FixtureTestCase
{
    public static function advancedScenarioProvider(): \Generator
    {
        foreach (FixtureRegistry::byTag('advanced') as $fixture) {
            yield $fixture->name => [$fixture];
        }
    }

    #[DataProvider('advancedScenarioProvider')]
    public function testAdvancedFixture(\AppDevPanel\Testing\Fixture\Fixture $fixture): void
    {
        $this->runFixture($fixture);
    }
}
