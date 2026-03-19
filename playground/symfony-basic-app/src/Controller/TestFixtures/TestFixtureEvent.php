<?php

declare(strict_types=1);

namespace App\Controller\TestFixtures;

final readonly class TestFixtureEvent
{
    public function __construct(
        public string $scenario,
    ) {}
}
