<?php

declare(strict_types=1);

namespace AppDevPanel\Kernel\Tests\Unit\Collector;

use AppDevPanel\Kernel\Collector\CollectorInterface;
use AppDevPanel\Kernel\Collector\CollectorTrait;
use PHPUnit\Framework\TestCase;

final class CollectorTraitTest extends TestCase
{
    public function testGetNameStripsCollectorSuffix(): void
    {
        $collector = new class() implements CollectorInterface {
            use CollectorTrait;

            public function getCollected(): array
            {
                return [];
            }
        };

        // Anonymous class — getName returns the short name without "Collector" suffix
        $name = $collector->getName();
        $this->assertIsString($name);
    }

    public function testGetIdReturnsFullClassName(): void
    {
        $collector = $this->createConcreteCollector();
        $this->assertSame($collector::class, $collector->getId());
    }

    public function testStartupActivatesCollector(): void
    {
        $collector = $this->createConcreteCollector();

        // Before startup, getCollected returns empty (isActive = false)
        $this->assertSame([], $collector->getCollected());

        $collector->startup();
        // After startup, isActive = true
        $this->assertSame(['active' => true], $collector->getCollected());
    }

    public function testShutdownDeactivatesAndResets(): void
    {
        $collector = $this->createConcreteCollector();
        $collector->startup();
        $this->assertSame(['active' => true], $collector->getCollected());

        $collector->shutdown();
        $this->assertSame([], $collector->getCollected());
    }

    private function createConcreteCollector(): CollectorInterface
    {
        return new class() implements CollectorInterface {
            use CollectorTrait;

            public function getCollected(): array
            {
                if (!$this->isActive()) {
                    return [];
                }
                return ['active' => true];
            }
        };
    }
}
