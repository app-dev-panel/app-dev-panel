<?php

declare(strict_types=1);

namespace App\Controller\TestFixtures;

/**
 * Simple message class for the Messenger fixture.
 */
final readonly class TestMessage
{
    public function __construct(
        public int $userId,
        public string $action,
        public array $payload = [],
    ) {}
}
