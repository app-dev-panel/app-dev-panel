<?php

declare(strict_types=1);

namespace AppDevPanel\Testing\Tests\E2E;

use AppDevPanel\Testing\Fixture\FixtureRegistry;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;

/**
 * E2E tests for error/exception fixtures.
 *
 * Run: PLAYGROUND_URL=http://127.0.0.1:8102 php vendor/bin/phpunit --testsuite Scenarios --group error
 */
#[Group('fixtures')]
#[Group('error')]
final class ErrorFixturesTest extends FixtureTestCase
{
    public static function errorScenarioProvider(): \Generator
    {
        foreach (FixtureRegistry::byTag('error') as $fixture) {
            yield $fixture->name => [$fixture];
        }
    }

    #[DataProvider('errorScenarioProvider')]
    public function testErrorFixture(\AppDevPanel\Testing\Fixture\Fixture $fixture): void
    {
        $this->runFixture($fixture);
    }
}
