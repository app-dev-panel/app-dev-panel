<?php

declare(strict_types=1);

namespace AppDevPanel\McpServer\Tests\Unit\Tool\Debug;

use AppDevPanel\Kernel\Collector\ExceptionCollector;
use AppDevPanel\Kernel\Collector\LogCollector;
use AppDevPanel\Kernel\Collector\Web\RequestCollector;
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

    public function testGetName(): void
    {
        $storage = new MemoryStorage(new DebuggerIdGenerator());
        $tool = new AnalyzeExceptionTool($storage);

        $this->assertSame('analyze_exception', $tool->getName());
    }

    public function testGetDescription(): void
    {
        $storage = new MemoryStorage(new DebuggerIdGenerator());
        $tool = new AnalyzeExceptionTool($storage);

        $this->assertNotEmpty($tool->getDescription());
        $this->assertStringContainsString('exception', strtolower($tool->getDescription()));
    }

    public function testGetInputSchema(): void
    {
        $storage = new MemoryStorage(new DebuggerIdGenerator());
        $tool = new AnalyzeExceptionTool($storage);

        $schema = $tool->getInputSchema();

        $this->assertSame('object', $schema['type']);
        $this->assertArrayHasKey('id', $schema['properties']);
        $this->assertArrayNotHasKey('required', $schema);
    }

    public function testAnalyzeExceptionWithPreviousExceptions(): void
    {
        $storage = new MemoryStorage(new DebuggerIdGenerator());
        $storage->write(
            'entry-1',
            [
                'id' => 'entry-1',
                'exception' => ['class' => 'RuntimeException', 'message' => 'Outer'],
            ],
            [
                ExceptionCollector::class => [
                    [
                        'class' => 'RuntimeException',
                        'message' => 'Outer exception',
                        'file' => '/app/src/app.php',
                        'line' => 42,
                        'code' => 0,
                        'trace' => [],
                        'traceAsString' => '#0 app.php(42)',
                    ],
                    [
                        'class' => 'InvalidArgumentException',
                        'message' => 'Inner cause',
                        'file' => '/app/src/service.php',
                        'line' => 10,
                        'code' => 1,
                        'trace' => [],
                        'traceAsString' => '#0 service.php(10)',
                    ],
                ],
            ],
            [],
        );
        $tool = new AnalyzeExceptionTool($storage);

        $result = $tool->execute(['id' => 'entry-1']);

        $text = $result['content'][0]['text'];
        $this->assertStringContainsString('## Exception', $text);
        $this->assertStringContainsString('## Previous Exception', $text);
        $this->assertStringContainsString('Outer exception', $text);
        $this->assertStringContainsString('Inner cause', $text);
        $this->assertStringContainsString('InvalidArgumentException', $text);
    }

    public function testAnalyzeExceptionWithRequestContext(): void
    {
        $storage = new MemoryStorage(new DebuggerIdGenerator());
        $storage->write(
            'entry-1',
            [
                'id' => 'entry-1',
                'exception' => ['class' => 'RuntimeException', 'message' => 'err'],
            ],
            [
                ExceptionCollector::class => [
                    [
                        'class' => 'RuntimeException',
                        'message' => 'Error with request',
                        'file' => '/app/app.php',
                        'line' => 1,
                        'code' => 0,
                        'trace' => [],
                        'traceAsString' => '',
                    ],
                ],
                RequestCollector::class => [
                    'method' => 'POST',
                    'url' => '/api/test',
                    'statusCode' => 500,
                ],
            ],
            [],
        );
        $tool = new AnalyzeExceptionTool($storage);

        $result = $tool->execute(['id' => 'entry-1']);

        $text = $result['content'][0]['text'];
        $this->assertStringContainsString('Request Context', $text);
        $this->assertStringContainsString('POST', $text);
        $this->assertStringContainsString('/api/test', $text);
    }

    public function testAnalyzeExceptionWithLongStackTrace(): void
    {
        $storage = new MemoryStorage(new DebuggerIdGenerator());
        $longTrace = str_repeat('#0 very/long/trace/line.php(42): doSomething() ', 200);
        $storage->write(
            'entry-1',
            [
                'id' => 'entry-1',
                'exception' => ['class' => 'Error', 'message' => 'err'],
            ],
            [
                ExceptionCollector::class => [
                    [
                        'class' => 'Error',
                        'message' => 'Stack overflow',
                        'file' => '/app/app.php',
                        'line' => 1,
                        'code' => 0,
                        'trace' => [],
                        'traceAsString' => $longTrace,
                    ],
                ],
            ],
            [],
        );
        $tool = new AnalyzeExceptionTool($storage);

        $result = $tool->execute(['id' => 'entry-1']);

        $text = $result['content'][0]['text'];
        $this->assertStringContainsString('truncated', $text);
    }

    public function testAnalyzeExceptionWithMoreThan20Logs(): void
    {
        $storage = new MemoryStorage(new DebuggerIdGenerator());
        $logs = [];
        for ($i = 0; $i < 25; $i++) {
            $logs[] = [
                'time' => 1000.0 + $i,
                'level' => 'info',
                'message' => "Log message #{$i}",
                'context' => [],
                'line' => '',
            ];
        }
        $storage->write(
            'entry-1',
            [
                'id' => 'entry-1',
                'exception' => ['class' => 'Exception', 'message' => 'err'],
            ],
            [
                ExceptionCollector::class => [
                    [
                        'class' => 'Exception',
                        'message' => 'With many logs',
                        'file' => '/app/app.php',
                        'line' => 1,
                        'code' => 0,
                        'trace' => [],
                        'traceAsString' => '',
                    ],
                ],
                LogCollector::class => $logs,
            ],
            [],
        );
        $tool = new AnalyzeExceptionTool($storage);

        $result = $tool->execute(['id' => 'entry-1']);

        $text = $result['content'][0]['text'];
        $this->assertStringContainsString('Related Log Messages', $text);
        $this->assertStringContainsString('and 5 more', $text);
        $this->assertStringContainsString('Log message #0', $text);
        $this->assertStringContainsString('Log message #19', $text);
        $this->assertStringNotContainsString('Log message #20', $text);
    }

    public function testAnalyzeExceptionWithEmptyTraceAsString(): void
    {
        $storage = new MemoryStorage(new DebuggerIdGenerator());
        $storage->write(
            'entry-1',
            [
                'id' => 'entry-1',
                'exception' => ['class' => 'Exception', 'message' => 'err'],
            ],
            [
                ExceptionCollector::class => [
                    [
                        'class' => 'Exception',
                        'message' => 'No trace',
                        'file' => '/app/app.php',
                        'line' => 1,
                        'code' => 0,
                        'trace' => [],
                        'traceAsString' => '',
                    ],
                ],
            ],
            [],
        );
        $tool = new AnalyzeExceptionTool($storage);

        $result = $tool->execute(['id' => 'entry-1']);

        $text = $result['content'][0]['text'];
        $this->assertStringNotContainsString('Stack Trace', $text);
    }

    public function testAnalyzeExceptionWithEmptyId(): void
    {
        $storage = $this->createStorageWithException();
        $tool = new AnalyzeExceptionTool($storage);

        $result = $tool->execute(['id' => '']);

        $this->assertArrayNotHasKey('isError', $result);
        $this->assertStringContainsString('RuntimeException', $result['content'][0]['text']);
    }

    public function testAnalyzeExceptionAutoFindSkipsEntriesWithoutException(): void
    {
        $storage = new MemoryStorage(new DebuggerIdGenerator());
        $storage->write('entry-1', ['id' => 'entry-1'], [], []);
        $storage->write('entry-2', ['id' => 'entry-2', 'exception' => []], [], []);
        $tool = new AnalyzeExceptionTool($storage);

        $result = $tool->execute([]);

        $this->assertStringContainsString('No debug entries with exceptions', $result['content'][0]['text']);
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
