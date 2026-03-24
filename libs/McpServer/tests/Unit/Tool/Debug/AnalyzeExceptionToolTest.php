<?php

declare(strict_types=1);

namespace AppDevPanel\McpServer\Tests\Unit\Tool\Debug;

use AppDevPanel\Kernel\Collector\ExceptionCollector;
use AppDevPanel\Kernel\Collector\LogCollector;
use AppDevPanel\Kernel\DebuggerIdGenerator;
use AppDevPanel\Kernel\Storage\MemoryStorage;
use AppDevPanel\McpServer\Tool\Debug\AnalyzeExceptionTool;
use PHPUnit\Framework\TestCase;

final class AnalyzeExceptionToolTest extends TestCase
{
    public function testAnalyzeExceptionByExplicitId(): void
    {
        $storage = $this->createStorageWithException();
        $tool = new AnalyzeExceptionTool($storage);

        $result = $tool->execute(['id' => 'entry-1']);

        $this->assertArrayNotHasKey('isError', $result);
        $text = $result['content'][0]['text'];
        $this->assertStringContainsString('RuntimeException', $text);
        $this->assertStringContainsString('Something went wrong', $text);
        $this->assertStringContainsString('app.php', $text);
    }

    public function testAnalyzeExceptionAutoFindsLatest(): void
    {
        $storage = $this->createStorageWithException();
        $tool = new AnalyzeExceptionTool($storage);

        $result = $tool->execute([]);

        $this->assertArrayNotHasKey('isError', $result);
        $this->assertStringContainsString('RuntimeException', $result['content'][0]['text']);
    }

    public function testAnalyzeExceptionIncludesRelatedLogs(): void
    {
        $storage = $this->createStorageWithException();
        $tool = new AnalyzeExceptionTool($storage);

        $result = $tool->execute(['id' => 'entry-1']);

        $this->assertStringContainsString('Before crash', $result['content'][0]['text']);
    }

    public function testAnalyzeExceptionNoExceptions(): void
    {
        $storage = new MemoryStorage(new DebuggerIdGenerator());
        $storage->write('entry-1', ['id' => 'entry-1'], [], []);
        $tool = new AnalyzeExceptionTool($storage);

        $result = $tool->execute(['id' => 'entry-1']);

        $this->assertStringContainsString('No exception', $result['content'][0]['text']);
    }

    public function testAnalyzeExceptionNoEntriesWithExceptions(): void
    {
        $storage = new MemoryStorage(new DebuggerIdGenerator());
        $tool = new AnalyzeExceptionTool($storage);

        $result = $tool->execute([]);

        $this->assertStringContainsString('No debug entries with exceptions', $result['content'][0]['text']);
    }

    public function testAnalyzeExceptionEntryNotFound(): void
    {
        $storage = new MemoryStorage(new DebuggerIdGenerator());
        $tool = new AnalyzeExceptionTool($storage);

        $result = $tool->execute(['id' => 'nonexistent']);

        $this->assertTrue($result['isError']);
    }

    private function createStorageWithException(): MemoryStorage
    {
        $storage = new MemoryStorage(new DebuggerIdGenerator());

        $storage->write(
            'entry-1',
            [
                'id' => 'entry-1',
                'exception' => ['class' => 'RuntimeException', 'message' => 'Something went wrong'],
            ],
            [
                ExceptionCollector::class => [
                    [
                        'class' => 'RuntimeException',
                        'message' => 'Something went wrong',
                        'file' => '/app/src/app.php',
                        'line' => 42,
                        'code' => 0,
                        'trace' => [],
                        'traceAsString' => '#0 app.php(42): throw new RuntimeException',
                    ],
                ],
                LogCollector::class => [
                    [
                        'time' => 999.0,
                        'level' => 'info',
                        'message' => 'Before crash',
                        'context' => [],
                        'line' => 'app.php:40',
                    ],
                ],
            ],
            [],
        );

        return $storage;
    }
}
