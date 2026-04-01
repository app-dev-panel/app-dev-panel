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

    public function testGetName(): void
    {
        $storage = new MemoryStorage(new DebuggerIdGenerator());
        $tool = new ViewTimelineTool($storage);

        $this->assertSame('view_timeline', $tool->getName());
    }

    public function testGetDescription(): void
    {
        $storage = new MemoryStorage(new DebuggerIdGenerator());
        $tool = new ViewTimelineTool($storage);

        $this->assertNotEmpty($tool->getDescription());
        $this->assertStringContainsString('timeline', strtolower($tool->getDescription()));
    }

    public function testGetInputSchema(): void
    {
        $storage = new MemoryStorage(new DebuggerIdGenerator());
        $tool = new ViewTimelineTool($storage);

        $schema = $tool->getInputSchema();

        $this->assertSame('object', $schema['type']);
        $this->assertArrayHasKey('id', $schema['properties']);
        $this->assertArrayNotHasKey('required', $schema);
    }

    public function testViewTimelineTruncatesAfter100Events(): void
    {
        $storage = new MemoryStorage(new DebuggerIdGenerator());
        $events = [];
        for ($i = 0; $i < 110; $i++) {
            $events[] = [1000.0 + $i * 0.001, "event-{$i}", LogCollector::class, []];
        }
        $storage->write(
            'entry-1',
            ['id' => 'entry-1'],
            [TimelineCollector::class => $events],
            [],
        );
        $tool = new ViewTimelineTool($storage);

        $result = $tool->execute(['id' => 'entry-1']);

        $text = $result['content'][0]['text'];
        $this->assertStringContainsString('Total events: 110', $text);
        $this->assertStringContainsString('and 10 more events', $text);
    }

    public function testViewTimelineWithNullTime(): void
    {
        $storage = new MemoryStorage(new DebuggerIdGenerator());
        $storage->write(
            'entry-1',
            ['id' => 'entry-1'],
            [
                TimelineCollector::class => [
                    [null, 'event-no-time', LogCollector::class, []],
                    [1000.050, 'event-with-time', LogCollector::class, []],
                ],
            ],
            [],
        );
        $tool = new ViewTimelineTool($storage);

        $result = $tool->execute(['id' => 'entry-1']);

        $text = $result['content'][0]['text'];
        $this->assertStringContainsString('?', $text);
        $this->assertStringContainsString('event-no-time', $text);
        $this->assertStringContainsString('event-with-time', $text);
    }

    public function testViewTimelineWithExtraData(): void
    {
        $storage = new MemoryStorage(new DebuggerIdGenerator());
        $storage->write(
            'entry-1',
            ['id' => 'entry-1'],
            [
                TimelineCollector::class => [
                    [1000.000, 'event-with-extra', LogCollector::class, ['key' => 'value']],
                ],
            ],
            [],
        );
        $tool = new ViewTimelineTool($storage);

        $result = $tool->execute(['id' => 'entry-1']);

        $text = $result['content'][0]['text'];
        $this->assertStringContainsString('"key"', $text);
        $this->assertStringContainsString('"value"', $text);
    }

    public function testViewTimelineAutoSelectsLatestWithNoTimelineData(): void
    {
        $storage = new MemoryStorage(new DebuggerIdGenerator());
        $storage->write('entry-no-timeline', ['id' => 'entry-no-timeline'], [], []);
        $tool = new ViewTimelineTool($storage);

        $result = $tool->execute([]);

        $this->assertStringContainsString('No timeline data', $result['content'][0]['text']);
    }

    public function testViewTimelineWithEmptyId(): void
    {
        $storage = $this->createStorageWithTimeline();
        $tool = new ViewTimelineTool($storage);

        $result = $tool->execute(['id' => '']);

        $this->assertArrayNotHasKey('isError', $result);
    }

    public function testViewTimelineWithNonArrayData(): void
    {
        $storage = new MemoryStorage(new DebuggerIdGenerator());
        $storage->write(
            'entry-1',
            ['id' => 'entry-1'],
            [TimelineCollector::class => 'not-an-array'],
            [],
        );
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
