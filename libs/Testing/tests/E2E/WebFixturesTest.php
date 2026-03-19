<?php

declare(strict_types=1);

namespace AppDevPanel\Testing\Tests\E2E;

use AppDevPanel\Testing\Fixture\FixtureRegistry;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;

/**
 * E2E tests for web context fixtures (request collector, app info).
 *
 * Run: PLAYGROUND_URL=http://127.0.0.1:8102 php vendor/bin/phpunit --testsuite Scenarios --group web
 */
#[Group('fixtures')]
#[Group('web')]
final class WebFixturesTest extends FixtureTestCase
{
    public static function webScenarioProvider(): \Generator
    {
        foreach (FixtureRegistry::byTag('web') as $fixture) {
            yield $fixture->name => [$fixture];
        }
    }

    #[DataProvider('webScenarioProvider')]
    public function testWebFixture(\AppDevPanel\Testing\Fixture\Fixture $fixture): void
    {
        $this->runFixture($fixture);
    }
}
