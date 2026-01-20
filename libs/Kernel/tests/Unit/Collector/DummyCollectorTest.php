<?php

declare(strict_types=1);

namespace AppDevPanel\Kernel\Tests\Unit\Collector;

use AppDevPanel\Kernel\Collector\CollectorInterface;
use AppDevPanel\Kernel\Tests\Shared\AbstractCollectorTestCase;
use AppDevPanel\Kernel\Tests\Unit\Support\DummyCollector;

final class DummyCollectorTest extends AbstractCollectorTestCase
{
    protected function getCollector(): CollectorInterface
    {
        return new DummyCollector();
    }

    protected function collectTestData(CollectorInterface $collector): void
    {
        // pass
    }
}
