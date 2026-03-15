<?php

declare(strict_types=1);

namespace AppDevPanel\Kernel\Tests\Unit\Storage;

use AppDevPanel\Kernel\Collector\CollectorInterface;
use AppDevPanel\Kernel\Collector\SummaryCollectorInterface;
use AppDevPanel\Kernel\DebuggerIdGenerator;
use AppDevPanel\Kernel\Dumper;
use AppDevPanel\Kernel\Storage\MemoryStorage;
use AppDevPanel\Kernel\Storage\StorageInterface;
use PHPUnit\Framework\TestCase;
use stdClass;

use function json_decode;

abstract class AbstractStorageTestCase extends TestCase
{
    /**
     * @dataProvider dataProvider()
     */
    public function testAddAndGet(array $data): void
    {
        $idGenerator = new DebuggerIdGenerator();
        $storage = $this->getStorage($idGenerator);
        $collector = $this->createFakeCollector($data);

        $this->assertEquals([], $storage->getData());
        $storage->addCollector($collector);
        $this->assertEquals([$collector->getId() => $data], $storage->getData());
    }

    /**
     * @dataProvider dataProvider()
     */
    public function testRead(array $data): void
    {
        $idGenerator = new DebuggerIdGenerator();
        $storage = $this->getStorage($idGenerator);

        $storage->addCollector($this->createFakeCollector($data));
        $storage->addCollector($this->createFakeSummaryCollector($data));
        $expectedData = $storage->getData();
        $encodedExpectedData = json_decode(Dumper::create($expectedData)->asJson(), true, 512, JSON_THROW_ON_ERROR);

        if (!$storage instanceof MemoryStorage) {
            $storage->flush();
        }

        $result = $storage->read(StorageInterface::TYPE_DATA);
        $dumper = Dumper::create($result);
        $encodedResult = json_decode($dumper->asJson(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertEquals([$idGenerator->getId() => $encodedExpectedData], $encodedResult);
    }

    /**
     * @dataProvider dataProvider()
     */
    public function testFlush(array $data): void
    {
        $idGenerator = new DebuggerIdGenerator();
        $storage = $this->getStorage($idGenerator);
        $collector = $this->createFakeCollector($data);

        $storage->addCollector($collector);
        $storage->flush();
        $this->assertEquals([], $storage->getData());
    }

    abstract public function getStorage(DebuggerIdGenerator $idGenerator): StorageInterface;

    public static function dataProvider(): iterable
    {
        yield 'integers' => [[1, 2, 3]];
        yield 'string' => [['string']];
        yield 'empty values' => [[[['', 0, false]]]];
        yield 'false' => [[false]];
        yield 'null' => [[null]];
        yield 'zero' => [[0]];
        yield 'stdClass' => [[new stdClass()]];
    }

    protected function createFakeCollector(array $data): CollectorInterface
    {
        $collector = $this->getMockBuilder(CollectorInterface::class)->getMock();
        $collector->method('getCollected')->willReturn($data);
        $collector->method('getId')->willReturn('Mock_Collector');
        $collector->method('getName')->willReturn('Mock');

        return $collector;
    }

    protected function createFakeSummaryCollector(array $data): SummaryCollectorInterface
    {
        $collector = $this->getMockBuilder(SummaryCollectorInterface::class)->getMock();
        $collector->method('getCollected')->willReturn($data);
        $collector->method('getId')->willReturn('SummaryMock_Collector');
        $collector->method('getName')->willReturn('Summary Mock');

        $collector->method('getSummary')->willReturn(['summary' => 'summary data']);

        return $collector;
    }
}
