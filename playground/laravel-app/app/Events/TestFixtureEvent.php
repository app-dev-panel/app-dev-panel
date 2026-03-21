<?php

declare(strict_types=1);

namespace App\Events;

final readonly class TestFixtureEvent
{
    public function __construct(
        public string $scenario,
    ) {}
}
