<?php

declare(strict_types=1);

namespace AppDevPanel\Testing\Runner;

use AppDevPanel\Testing\Assertion\AssertionResult;
use AppDevPanel\Testing\Scenario\Scenario;

final readonly class ScenarioResult
{
    /**
     * @param list<AssertionResult> $assertions
     */
    public function __construct(
        public Scenario $scenario,
        public bool $passed,
        public array $assertions,
        public ?string $error = null,
        public ?string $debugId = null,
    ) {}

    public static function skip(Scenario $scenario, string $reason): self
    {
        return new self($scenario, false, [], error: $reason);
    }

    public static function fromAssertions(Scenario $scenario, array $assertions, ?string $debugId): self
    {
        $allPassed = true;
        foreach ($assertions as $assertion) {
            if (!$assertion->passed) {
                $allPassed = false;
                break;
            }
        }

        return new self($scenario, $allPassed, $assertions, debugId: $debugId);
    }
}
