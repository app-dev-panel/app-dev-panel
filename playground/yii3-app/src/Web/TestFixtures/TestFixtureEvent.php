<?php

declare(strict_types=1);

namespace App\Web\TestFixtures;

/**
 * Simple event class for ADP test scenarios.
 */
final readonly class TestFixtureEvent
{
    public function __construct(
        public string $scenario,
    ) {}
}
