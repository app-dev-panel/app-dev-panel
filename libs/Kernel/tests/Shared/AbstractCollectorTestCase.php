<?php

declare(strict_types=1);

namespace AppDevPanel\Kernel\Tests\Shared;

use AppDevPanel\Kernel\Collector\CollectorInterface;
use AppDevPanel\Kernel\Collector\SummaryCollectorInterface;
use PHPUnit\Framework\TestCase;

abstract class AbstractCollectorTestCase extends TestCase
{
    public function testCollect(): void
    {
        $summaryData = null;
        $collector = $this->getCollector();

        $collector->startup();
        $this->collectTestData($collector);
        // Buffer must be readable AFTER shutdown — that's how `Debugger::shutdown()`
        // works (collector->shutdown() detaches observers, then storage flush calls
        // getCollected() / getSummary() to serialize the snapshot).
        $collector->shutdown();
        $data = $collector->getCollected();
        if ($collector instanceof SummaryCollectorInterface) {
            $summaryData = $collector->getSummary();
        }

        $this->assertSame($collector::class, $collector->getId());
        $this->assertNotEmpty($collector->getName());
        $this->checkCollectedData($data);
        if ($collector instanceof SummaryCollectorInterface) {
            $this->checkSummaryData($summaryData);
        }
    }

    // NOTE: the legacy `testEmptyCollector` / `testInactiveCollector` tests asserted
    // that `getCollected()` returned `[]` outside of startup/shutdown. The new lifecycle
    // ({@see CollectorTrait}) gates only `collect*()` on `isActive`; readers always return
    // the buffer (so storage flush, called AFTER `shutdown()`, can serialize the snapshot).
    // Each collector test asserts its own no-data shape where it matters.

    abstract protected function getCollector(): CollectorInterface;

    abstract protected function collectTestData(CollectorInterface $collector): void;

    protected function checkCollectedData(array $data): void
    {
        $this->assertNotEmpty($data);
    }

    protected function checkSummaryData(array $data): void
    {
        $this->assertNotEmpty($data);
    }
}
