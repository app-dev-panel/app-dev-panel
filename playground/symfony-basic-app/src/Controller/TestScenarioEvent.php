<?php

declare(strict_types=1);

namespace App\Controller;

/**
 * Simple event class for ADP test scenarios.
 */
final readonly class TestScenarioEvent
{
    public function __construct(
        public string $scenario,
    ) {}
}
