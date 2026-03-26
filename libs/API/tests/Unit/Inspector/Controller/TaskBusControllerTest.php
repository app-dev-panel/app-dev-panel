<?php

declare(strict_types=1);

namespace AppDevPanel\Api\Tests\Unit\Inspector\Controller;

use AppDevPanel\Api\Inspector\Controller\TaskBusController;
use AppDevPanel\TaskBus\Process\ProcessResult;
use AppDevPanel\TaskBus\Process\ProcessRunnerInterface;
use AppDevPanel\TaskBus\TaskBusFactory;
use AppDevPanel\TaskBus\Transport\JsonRpcHandler;
use Nyholm\Psr7\ServerRequest;
use Nyholm\Psr7\Stream;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(TaskBusController::class)]
final class TaskBusControllerTest extends ControllerTestCase
{
    private TaskBusController $controller;

    protected function setUp(): void
    {
        $runner = $this->createMock(ProcessRunnerInterface::class);
        $runner->method('run')->willReturn(new ProcessResult(0, 'ok', '', 0.1));

        $bus = TaskBusFactory::createInMemory(processRunner: $runner);
        $rpcHandler = new JsonRpcHandler($bus);

        $this->controller = new TaskBusController($this->createResponseFactory(), $rpcHandler);
    }

    public function testHandleSubmitTask(): void
    {
        $request = $this->rpcRequest([
            'jsonrpc' => '2.0',
            'method' => 'task.submit',
            'params' => ['type' => 'run_command', 'command' => 'echo hello'],
            'id' => 1,
        ]);

        $response = $this->controller->handle($request);
        $data = $this->responseData($response);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('2.0', $data['jsonrpc']);
        $this->assertArrayHasKey('task_id', $data['result']);
    }

    public function testHandleTaskList(): void
    {
        // Submit a task first
        $this->controller->handle($this->rpcRequest([
            'jsonrpc' => '2.0',
            'method' => 'task.submit',
            'params' => ['type' => 'run_command', 'command' => 'echo 1'],
            'id' => 1,
        ]));

        // List tasks
        $response = $this->controller->handle($this->rpcRequest([
            'jsonrpc' => '2.0',
            'method' => 'task.list',
            'params' => [],
            'id' => 2,
        ]));

        $data = $this->responseData($response);
        $this->assertSame(1, $data['result']['total']);
    }

    public function testHandleEmptyBody(): void
    {
        $request = new ServerRequest('POST', '/inspect/api/taskbus');

        $response = $this->controller->handle($request);
        $data = $this->responseData($response);

        $this->assertSame(400, $response->getStatusCode());
        $this->assertSame(-32700, $data['error']['code']);
    }

    public function testHandleNotification(): void
    {
        $request = $this->rpcRequest([
            'jsonrpc' => '2.0',
            'method' => 'task.submit',
            'params' => ['type' => 'run_command', 'command' => 'echo fire'],
            // No 'id' → notification
        ]);

        $response = $this->controller->handle($request);
        $this->assertSame(204, $response->getStatusCode());
    }

    public function testHandleMethodNotFound(): void
    {
        $response = $this->controller->handle($this->rpcRequest([
            'jsonrpc' => '2.0',
            'method' => 'nonexistent.method',
            'params' => [],
            'id' => 1,
        ]));

        $data = $this->responseData($response);
        $this->assertSame(-32601, $data['error']['code']);
    }

    public function testHandleBatch(): void
    {
        $request = new ServerRequest('POST', '/inspect/api/taskbus');
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
        $request = $request->withBody(Stream::create($batch));

        $response = $this->controller->handle($request);
        $data = $this->responseData($response);

        $this->assertCount(2, $data);
    }

    public function testStatus(): void
    {
        $response = $this->controller->status();
        $data = $this->responseData($response);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertTrue($data['active']);
        $this->assertSame(0, $data['total']);
    }

    public function testStatusAfterSubmit(): void
    {
        // Submit a task
        $this->controller->handle($this->rpcRequest([
            'jsonrpc' => '2.0',
            'method' => 'task.submit',
            'params' => ['type' => 'run_command', 'command' => 'echo hello'],
            'id' => 1,
        ]));

        $response = $this->controller->status();
        $data = $this->responseData($response);

        $this->assertSame(1, $data['total']);
    }

    private function rpcRequest(array $body): ServerRequest
    {
        $request = new ServerRequest('POST', '/inspect/api/taskbus');
        return $request->withBody(Stream::create(json_encode($body, JSON_THROW_ON_ERROR)));
    }
}
