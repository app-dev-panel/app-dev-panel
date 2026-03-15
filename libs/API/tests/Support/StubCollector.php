<?php

declare(strict_types=1);

namespace AppDevPanel\Api\Tests\Support;

use AppDevPanel\Kernel\Collector\CollectorInterface;

final class StubCollector implements CollectorInterface
{
    public function __construct(
        private array $data = [],
    ) {}

    public function getId(): string
    {
        return 'stub';
    }

    public function getName(): string
    {
        return 'Stub';
    }

    public function startup(): void {}

    public function shutdown(): void {}

    public function getCollected(): array
    {
        return $this->data;
    }
}
