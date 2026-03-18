<?php

declare(strict_types=1);

namespace AppDevPanel\Testing\Tests\E2E;

use AppDevPanel\Testing\Scenario\ScenarioRegistry;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;

/**
 * E2E tests for core ADP scenarios (logs, events, var-dumper, timeline).
 *
 * Run: PLAYGROUND_URL=http://127.0.0.1:8102 php vendor/bin/phpunit --testsuite Scenarios --group core
 */
#[Group('scenarios')]
#[Group('core')]
final class CoreScenariosTest extends ScenarioTestCase
{
    public static function coreScenarioProvider(): \Generator
    {
        foreach (ScenarioRegistry::byTag('core') as $scenario) {
            yield $scenario->name => [$scenario];
        }
    }

    #[DataProvider('coreScenarioProvider')]
    public function testCoreScenario(\AppDevPanel\Testing\Scenario\Scenario $scenario): void
    {
        $this->runScenario($scenario);
    }
}
