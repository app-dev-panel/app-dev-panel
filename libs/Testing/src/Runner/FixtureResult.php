<?php

declare(strict_types=1);

namespace AppDevPanel\Testing\Runner;

use AppDevPanel\Testing\Assertion\AssertionResult;
use AppDevPanel\Testing\Fixture\Fixture;

final readonly class FixtureResult
{
    /**
     * @param list<AssertionResult> $assertions
     */
    public function __construct(
        public Fixture $fixture,
        public bool $passed,
        public array $assertions,
        public ?string $error = null,
        public ?string $debugId = null,
    ) {}

    public static function skip(Fixture $fixture, string $reason): self
    {
        return new self($fixture, false, [], error: $reason);
    }

    public static function fromAssertions(Fixture $fixture, array $assertions, ?string $debugId): self
    {
        $allPassed = true;
        foreach ($assertions as $assertion) {
            if ($assertion->passed) {
                continue;
            }

            $allPassed = false;
            break;
        }

        return new self($fixture, $allPassed, $assertions, debugId: $debugId);
    }
}
