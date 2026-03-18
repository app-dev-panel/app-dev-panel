<?php

declare(strict_types=1);

namespace AppDevPanel\Testing\Tests\E2E;

use AppDevPanel\Testing\Scenario\ScenarioRegistry;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;

/**
 * E2E tests for advanced scenarios (multi-collector, heavy load, HTTP client, filesystem).
 *
 * Run: PLAYGROUND_URL=http://127.0.0.1:8102 php vendor/bin/phpunit --testsuite Scenarios --group advanced
 */
#[Group('scenarios')]
#[Group('advanced')]
final class AdvancedScenariosTest extends ScenarioTestCase
{
    public static function advancedScenarioProvider(): \Generator
    {
        foreach (ScenarioRegistry::byTag('advanced') as $scenario) {
            yield $scenario->name => [$scenario];
        }
    }

    #[DataProvider('advancedScenarioProvider')]
    public function testAdvancedScenario(\AppDevPanel\Testing\Scenario\Scenario $scenario): void
    {
        $this->runScenario($scenario);
    }
}
