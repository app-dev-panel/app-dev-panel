<?php

declare(strict_types=1);

namespace AppDevPanel\Testing\Tests\E2E;

use AppDevPanel\Testing\Fixture\FixtureRegistry;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;

/**
 * E2E tests for core ADP fixtures (logs, events, var-dumper, timeline).
 *
 * Run: PLAYGROUND_URL=http://127.0.0.1:8102 php vendor/bin/phpunit --testsuite Scenarios --group core
 */
#[Group('fixtures')]
#[Group('core')]
final class CoreFixturesTest extends FixtureTestCase
{
    public static function coreScenarioProvider(): \Generator
    {
        foreach (FixtureRegistry::byTag('core') as $fixture) {
            yield $fixture->name => [$fixture];
        }
    }

    #[DataProvider('coreScenarioProvider')]
    public function testCoreFixture(\AppDevPanel\Testing\Fixture\Fixture $fixture): void
    {
        $this->runFixture($fixture);
    }
}
