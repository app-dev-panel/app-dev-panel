<?php

declare(strict_types=1);

namespace AppDevPanel\Kernel\Tests\Unit\Support;

use AppDevPanel\Kernel\Collector\CollectorInterface;
use stdClass;

final class DummyCollector implements CollectorInterface
{
    private bool $isActive = false;

    public function getId(): string
    {
        return self::class;
    }

    public function getName(): string
    {
        return 'Dummy';
    }

    public function startup(): void
    {
        $this->isActive = true;
    }

    public function shutdown(): void
    {
        $this->isActive = false;
    }

    public function getCollected(): array
    {
        // No `isActive` guard on read methods — storage flush calls
        // `getCollected()` AFTER `shutdown()` (see {@see CollectorInterface}).
        return [
            'int' => 123,
            'str' => 'asdas',
            'object' => new stdClass(),
        ];
    }
}
