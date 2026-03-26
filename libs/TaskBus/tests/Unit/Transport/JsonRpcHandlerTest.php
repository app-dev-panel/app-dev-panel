<?php

declare(strict_types=1);

namespace AppDevPanel\TaskBus\Tests\Unit\Transport;

use AppDevPanel\TaskBus\Process\ProcessResult;
use AppDevPanel\TaskBus\Process\ProcessRunnerInterface;
use AppDevPanel\TaskBus\Storage\PdoFactory;
use AppDevPanel\TaskBus\Storage\SqliteTaskRepository;
use AppDevPanel\TaskBus\TaskBus;
use AppDevPanel\TaskBus\TaskBusConfig;
use AppDevPanel\TaskBus\TaskBusFactory;
use AppDevPanel\TaskBus\Transport\JsonRpcHandler;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(JsonRpcHandler::class)]
final class JsonRpcHandlerTest extends TestCase
{
    private JsonRpcHandler $handler;

    protected function setUp(): void
    {
        $runner = $this->createMock(ProcessRunnerInterface::class);
        $runner->method('run')->willReturn(new ProcessResult(0, 'output', '', 0.1));

        $taskBus = TaskBusFactory::createInMemory(processRunner: $runner);
        $this->handler = new JsonRpcHandler($taskBus);
    }

    public function testTaskSubmitAndStatus(): void
    {
        // Submit
        $submitResponse = $this->call(
            'task.submit',
            [
                'type' => 'run_command',
                'command' => 'echo hello',
            ],
            1,
        );

        $this->assertArrayHasKey('result', $submitResponse);
        $taskId = $submitResponse['result']['task_id'];
        $this->assertNotEmpty($taskId);

        // Status
        $statusResponse = $this->call('task.status', ['task_id' => $taskId], 2);
        $this->assertSame('run_command', $statusResponse['result']['type']);
    }

    public function testTaskList(): void
    {
        $this->call('task.submit', ['type' => 'run_command', 'command' => 'echo 1'], 1);
        $this->call('task.submit', ['type' => 'run_command', 'command' => 'echo 2'], 2);

        $listResponse = $this->call('task.list', [], 3);
        $this->assertArrayHasKey('tasks', $listResponse['result']);
        $this->assertSame(2, $listResponse['result']['total']);
    }

    public function testTaskCancel(): void
    {
        // In sync mode, submitted tasks complete immediately, so cancel returns false
        $submit = $this->call('task.submit', ['type' => 'run_command', 'command' => 'echo done'], 1);
        $taskId = $submit['result']['task_id'];

        $cancel = $this->call('task.cancel', ['task_id' => $taskId], 2);
        // Task already completed — cancel should return false
        $this->assertFalse($cancel['result']['success']);
    }

    public function testTaskLogs(): void
    {
        $submit = $this->call('task.submit', ['type' => 'run_command', 'command' => 'echo hi'], 1);
        $taskId = $submit['result']['task_id'];

        $logsResponse = $this->call('task.logs', ['task_id' => $taskId], 2);
        $this->assertArrayHasKey('logs', $logsResponse['result']);
    }

    public function testMethodNotFound(): void
    {
        $response = $this->call('nonexistent.method', [], 1);
        $this->assertArrayHasKey('error', $response);
        $this->assertSame(-32601, $response['error']['code']);
    }

    public function testParseError(): void
    {
        $response = json_decode($this->handler->handle('invalid json{{{'), true);
        $this->assertArrayHasKey('error', $response);
        $this->assertSame(-32700, $response['error']['code']);
    }

    public function testBatchRequest(): void
    {
        $batch = json_encode([
            [
                'jsonrpc' => '2.0',
                'method' => 'task.submit',
                'params' => ['type' => 'run_command', 'command' => 'echo 1'],
                'id' => 1,
            ],
            [
                'jsonrpc' => '2.0',
                'method' => 'task.submit',
                'params' => ['type' => 'run_command', 'command' => 'echo 2'],
                'id' => 2,
            ],
        ]);

        $response = json_decode($this->handler->handle($batch), true);
        $this->assertCount(2, $response);
        $this->assertArrayHasKey('result', $response[0]);
        $this->assertArrayHasKey('result', $response[1]);
    }

    public function testNotificationReturnsNull(): void
    {
        $request = json_encode([
            'jsonrpc' => '2.0',
            'method' => 'task.submit',
            'params' => ['type' => 'run_command', 'command' => 'echo fire-and-forget'],
        ]);

        $response = $this->handler->handle($request);
        $this->assertNull($response);
    }

    public function testEmptyBatchReturnsError(): void
    {
        $response = json_decode($this->handler->handle('[]'), true);
        $this->assertArrayHasKey('error', $response);
    }

    public function testSubmitRunTests(): void
    {
        $response = $this->call(
            'task.submit',
            [
                'type' => 'run_tests',
                'runner' => 'vendor/bin/phpunit',
                'args' => ['--testsuite', 'Kernel'],
            ],
            1,
        );

        $this->assertArrayHasKey('task_id', $response['result']);
    }

    public function testSubmitRunAnalyzer(): void
    {
        $response = $this->call(
            'task.submit',
            [
                'type' => 'run_analyzer',
                'analyzer' => 'vendor/bin/mago',
                'args' => ['analyze'],
            ],
            1,
        );

        $this->assertArrayHasKey('task_id', $response['result']);
    }

    public function testSubmitUnknownType(): void
    {
        $response = $this->call('task.submit', ['type' => 'unknown'], 1);
        $this->assertArrayHasKey('error', $response);
    }

    public function testTaskStatusNotFound(): void
    {
        $response = $this->call('task.status', ['task_id' => 'missing'], 1);
        $this->assertArrayHasKey('error', $response);
    }

    private function call(string $method, array $params, int $id): array
    {
        $request = json_encode([
            'jsonrpc' => '2.0',
            'method' => $method,
            'params' => $params,
            'id' => $id,
        ]);

        return json_decode($this->handler->handle($request), true);
    }
}
