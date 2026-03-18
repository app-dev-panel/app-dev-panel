<?php

declare(strict_types=1);

namespace AppDevPanel\Testing\Tests\E2E;

use AppDevPanel\Testing\Scenario\ScenarioRegistry;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;

/**
 * E2E tests for web context scenarios (request collector, app info).
 *
 * Run: PLAYGROUND_URL=http://127.0.0.1:8102 php vendor/bin/phpunit --testsuite Scenarios --group web
 */
#[Group('scenarios')]
#[Group('web')]
final class WebScenariosTest extends ScenarioTestCase
{
    public static function webScenarioProvider(): \Generator
    {
        foreach (ScenarioRegistry::byTag('web') as $scenario) {
            yield $scenario->name => [$scenario];
        }
    }

    #[DataProvider('webScenarioProvider')]
    public function testWebScenario(\AppDevPanel\Testing\Scenario\Scenario $scenario): void
    {
        $this->runScenario($scenario);
    }
}
