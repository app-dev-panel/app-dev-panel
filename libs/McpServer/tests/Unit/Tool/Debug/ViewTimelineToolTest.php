<?php

declare(strict_types=1);

namespace AppDevPanel\McpServer\Tests\Unit\Tool\Debug;

use AppDevPanel\Kernel\Collector\LogCollector;
use AppDevPanel\Kernel\Collector\TimelineCollector;
use AppDevPanel\Kernel\DebuggerIdGenerator;
use AppDevPanel\Kernel\Storage\MemoryStorage;
use AppDevPanel\McpServer\Tool\Debug\ViewTimelineTool;
use PHPUnit\Framework\TestCase;

final class ViewTimelineToolTest extends TestCase
{
    public function testViewTimelineShowsEvents(): void
    {
        $storage = $this->createStorageWithTimeline();
        $tool = new ViewTimelineTool($storage);

        $result = $tool->execute(['id' => 'entry-1']);

        $this->assertArrayNotHasKey('isError', $result);
        $text = $result['content'][0]['text'];
        $this->assertStringContainsString('entry-1', $text);
        $this->assertStringContainsString('LogCollector', $text);
        $this->assertStringContainsString('Total events: 2', $text);
    }

    public function testViewTimelineAutoSelectsLatest(): void
    {
        $storage = $this->createStorageWithTimeline();
        $tool = new ViewTimelineTool($storage);

        $result = $tool->execute([]);

        $this->assertArrayNotHasKey('isError', $result);
    }

    public function testViewTimelineEntryNotFound(): void
    {
        $storage = new MemoryStorage(new DebuggerIdGenerator());
        $tool = new ViewTimelineTool($storage);

        $result = $tool->execute(['id' => 'nonexistent']);

        $this->assertTrue($result['isError']);
    }

    public function testViewTimelineNoData(): void
    {
        $storage = new MemoryStorage(new DebuggerIdGenerator());
        $storage->write('entry-1', ['id' => 'entry-1'], [], []);
        $tool = new ViewTimelineTool($storage);

        $result = $tool->execute(['id' => 'entry-1']);

        $this->assertStringContainsString('No timeline data', $result['content'][0]['text']);
    }

    private function createStorageWithTimeline(): MemoryStorage
    {
        $storage = new MemoryStorage(new DebuggerIdGenerator());

        $storage->write(
            'entry-1',
            ['id' => 'entry-1'],
            [
                TimelineCollector::class => [
                    [1000.000, 1, LogCollector::class, []],
                    [1000.050, 2, LogCollector::class, ['extra' => 'data']],
                ],
            ],
            [],
        );

        return $storage;
    }
}
