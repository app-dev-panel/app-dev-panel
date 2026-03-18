<?php

declare(strict_types=1);

namespace AppDevPanel\Testing\Tests\E2E;

use AppDevPanel\Testing\Scenario\ScenarioRegistry;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;

/**
 * E2E tests for error/exception scenarios.
 *
 * Run: PLAYGROUND_URL=http://127.0.0.1:8102 php vendor/bin/phpunit --testsuite Scenarios --group error
 */
#[Group('scenarios')]
#[Group('error')]
final class ErrorScenariosTest extends ScenarioTestCase
{
    public static function errorScenarioProvider(): \Generator
    {
        foreach (ScenarioRegistry::byTag('error') as $scenario) {
            yield $scenario->name => [$scenario];
        }
    }

    #[DataProvider('errorScenarioProvider')]
    public function testErrorScenario(\AppDevPanel\Testing\Scenario\Scenario $scenario): void
    {
        $this->runScenario($scenario);
    }
}
