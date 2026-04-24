<?php

declare(strict_types=1);

namespace App\Controller\TestFixtures;

/**
 * Test event dispatched by {@see EventsAction} to exercise the ADP EventCollector.
 */
final class TestFixtureEvent
{
    public bool $handled = false;

    public function __construct(
        public readonly string $message,
    ) {}
}
