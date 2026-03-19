<?php

declare(strict_types=1);

namespace App\Controller\TestScenarios;

final readonly class TestScenarioEvent
{
    public function __construct(
        public string $scenario,
    ) {}
}
