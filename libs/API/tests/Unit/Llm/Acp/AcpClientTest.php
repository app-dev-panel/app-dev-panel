<?php

declare(strict_types=1);

namespace AppDevPanel\Api\Tests\Unit\Llm\Acp;

use AppDevPanel\Api\Llm\Acp\AcpClient;
use AppDevPanel\Api\Llm\Acp\AcpTransportInterface;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class AcpClientTest extends TestCase
{
    private function createMockTransport(array $responses): AcpTransportInterface
    {
        $transport = $this->createMock(AcpTransportInterface::class);

        $callIndex = 0;
        $transport
            ->method('receive')
            ->willReturnCallback(function () use (&$callIndex, $responses): ?array {
                if ($callIndex >= count($responses)) {
                    return null;
                }
                return $responses[$callIndex++];
            });

        $transport->method('isAlive')->willReturn(true);

        return $transport;
    }

    public function testSuccessfulChat(): void
    {
        $responses = [
            // initialize response
            [
                'jsonrpc' => '2.0',
                'id' => 1,
                'result' => [
                    'protocolVersion' => 1,
                    'capabilities' => [],
                    'agentInfo' => ['name' => 'TestAgent', 'version' => '1.0'],
                ],
            ],
            // session/new response
            [
                'jsonrpc' => '2.0',
                'id' => 2,
                'result' => ['sessionId' => 'test-session-123'],
            ],
            // session/update notification (text chunk)
            [
                'jsonrpc' => '2.0',
                'method' => 'session/update',
                'params' => [
                    'sessionId' => 'test-session-123',
                    'update' => [
                        'type' => 'message_chunk',
                        'content' => [['type' => 'text', 'text' => 'Hello ']],
                    ],
                ],
            ],
            // session/update notification (text chunk)
            [
                'jsonrpc' => '2.0',
                'method' => 'session/update',
                'params' => [
                    'sessionId' => 'test-session-123',
                    'update' => [
                        'type' => 'message_chunk',
                        'content' => [['type' => 'text', 'text' => 'World!']],
                    ],
                ],
            ],
            // session/prompt response
            [
                'jsonrpc' => '2.0',
                'id' => 3,
                'result' => ['stopReason' => 'end_turn'],
            ],
        ];

        $transport = $this->createMockTransport($responses);
        $transport->expects($this->once())->method('spawn');
        $transport->expects($this->once())->method('close');

        $client = new AcpClient($transport);
        $result = $client->chat('test-agent', [['role' => 'user', 'content' => 'Hi']], timeout: 10.0);

        $this->assertSame('Hello World!', $result->text);
        $this->assertSame('end_turn', $result->stopReason);
        $this->assertSame('TestAgent', $result->agentName);
        $this->assertSame('1.0', $result->agentVersion);
    }

    public function testChatWithToolCalls(): void
    {
        $responses = [
            // initialize
            [
                'jsonrpc' => '2.0',
                'id' => 1,
                'result' => ['protocolVersion' => 1, 'agentInfo' => ['name' => 'Agent', 'version' => '1']],
            ],
            // session/new
            ['jsonrpc' => '2.0', 'id' => 2, 'result' => ['sessionId' => 'sess-1']],
            // tool call start
            [
                'jsonrpc' => '2.0',
                'method' => 'session/update',
                'params' => [
                    'sessionId' => 'sess-1',
                    'update' => ['type' => 'tool_call_start', 'toolCall' => ['name' => 'search_logs']],
                ],
            ],
            // text chunk
            [
                'jsonrpc' => '2.0',
                'method' => 'session/update',
                'params' => [
                    'sessionId' => 'sess-1',
                    'update' => [
                        'type' => 'message_chunk',
                        'content' => [['type' => 'text', 'text' => 'Found 3 errors.']],
                    ],
                ],
            ],
            // response
            ['jsonrpc' => '2.0', 'id' => 3, 'result' => ['stopReason' => 'end_turn']],
        ];

        $transport = $this->createMockTransport($responses);
        $client = new AcpClient($transport);

        $result = $client->chat('agent', [['role' => 'user', 'content' => 'Find errors']], timeout: 10.0);

        $this->assertSame('Found 3 errors.', $result->text);
        $this->assertCount(1, $result->toolCalls);
        $this->assertSame('search_logs', $result->toolCalls[0]['content']);
    }

    public function testInitializeError(): void
    {
        $responses = [
            ['jsonrpc' => '2.0', 'id' => 1, 'error' => ['code' => -32600, 'message' => 'Bad version']],
        ];

        $transport = $this->createMockTransport($responses);
        $client = new AcpClient($transport);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('ACP initialize failed: Bad version');
        $client->chat('agent', [['role' => 'user', 'content' => 'Hi']], timeout: 5.0);
    }

    public function testSessionNewError(): void
    {
        $responses = [
            ['jsonrpc' => '2.0', 'id' => 1, 'result' => ['protocolVersion' => 1, 'agentInfo' => []]],
            ['jsonrpc' => '2.0', 'id' => 2, 'error' => ['code' => -32000, 'message' => 'Auth required']],
        ];

        $transport = $this->createMockTransport($responses);
        $client = new AcpClient($transport);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('ACP session/new failed: Auth required');
        $client->chat('agent', [['role' => 'user', 'content' => 'Hi']], timeout: 5.0);
    }

    public function testPromptError(): void
    {
        $responses = [
            ['jsonrpc' => '2.0', 'id' => 1, 'result' => ['protocolVersion' => 1, 'agentInfo' => []]],
            ['jsonrpc' => '2.0', 'id' => 2, 'result' => ['sessionId' => 'sess-1']],
            ['jsonrpc' => '2.0', 'id' => 3, 'error' => ['code' => -32000, 'message' => 'Rate limited']],
        ];

        $transport = $this->createMockTransport($responses);
        $client = new AcpClient($transport);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('ACP session/prompt failed: Rate limited');
        $client->chat('agent', [['role' => 'user', 'content' => 'Hi']], timeout: 5.0);
    }

    public function testAgentRequestGetsRejected(): void
    {
        $sentMessages = [];
        $responses = [
            ['jsonrpc' => '2.0', 'id' => 1, 'result' => ['protocolVersion' => 1, 'agentInfo' => []]],
            ['jsonrpc' => '2.0', 'id' => 2, 'result' => ['sessionId' => 'sess-1']],
            // Agent requests a file read (has both id and method)
            ['jsonrpc' => '2.0', 'id' => 99, 'method' => 'fs/read_text_file', 'params' => ['path' => '/etc/passwd']],
            // Then sends response
            [
                'jsonrpc' => '2.0',
                'method' => 'session/update',
                'params' => [
                    'sessionId' => 'sess-1',
                    'update' => ['type' => 'message_chunk', 'content' => [['type' => 'text', 'text' => 'OK']]],
                ],
            ],
            ['jsonrpc' => '2.0', 'id' => 3, 'result' => ['stopReason' => 'end_turn']],
        ];

        $transport = $this->createMockTransport($responses);
        $transport
            ->method('send')
            ->willReturnCallback(function (array $msg) use (&$sentMessages): void {
                $sentMessages[] = $msg;
            });

        $client = new AcpClient($transport);
        $result = $client->chat('agent', [['role' => 'user', 'content' => 'Hi']], timeout: 10.0);

        $this->assertSame('OK', $result->text);

        // Verify agent request was rejected
        $rejections = array_filter($sentMessages, fn($m) => isset($m['error']) && $m['id'] === 99);
        $this->assertCount(1, $rejections);

        $rejection = array_values($rejections)[0];
        $this->assertSame(-32601, $rejection['error']['code']);
    }

    public function testCustomPromptIncluded(): void
    {
        $sentMessages = [];
        $responses = [
            ['jsonrpc' => '2.0', 'id' => 1, 'result' => ['protocolVersion' => 1, 'agentInfo' => []]],
            ['jsonrpc' => '2.0', 'id' => 2, 'result' => ['sessionId' => 'sess-1']],
            ['jsonrpc' => '2.0', 'id' => 3, 'result' => ['stopReason' => 'end_turn']],
        ];

        $transport = $this->createMockTransport($responses);
        $transport
            ->method('send')
            ->willReturnCallback(function (array $msg) use (&$sentMessages): void {
                $sentMessages[] = $msg;
            });

        $client = new AcpClient($transport);
        $client->chat(
            'agent',
            [['role' => 'system', 'content' => 'Be brief'], ['role' => 'user', 'content' => 'Hi']],
            customPrompt: 'Reply in Russian',
            timeout: 5.0,
        );

        // Find the session/prompt message
        $promptMsg = array_values(array_filter($sentMessages, fn($m) => ($m['method'] ?? '') === 'session/prompt'));
        $this->assertCount(1, $promptMsg);

        $text = $promptMsg[0]['params']['prompt']['content'][0]['text'];
        $this->assertStringContainsString('Reply in Russian', $text);
        $this->assertStringContainsString('Be brief', $text);
        $this->assertStringContainsString('Hi', $text);
    }

    public function testMultipleMessagesAggregated(): void
    {
        $sentMessages = [];
        $responses = [
            ['jsonrpc' => '2.0', 'id' => 1, 'result' => ['protocolVersion' => 1, 'agentInfo' => []]],
            ['jsonrpc' => '2.0', 'id' => 2, 'result' => ['sessionId' => 'sess-1']],
            ['jsonrpc' => '2.0', 'id' => 3, 'result' => ['stopReason' => 'end_turn']],
        ];

        $transport = $this->createMockTransport($responses);
        $transport
            ->method('send')
            ->willReturnCallback(function (array $msg) use (&$sentMessages): void {
                $sentMessages[] = $msg;
            });

        $client = new AcpClient($transport);
        $client->chat(
            'agent',
            [
                ['role' => 'user', 'content' => 'First message'],
                ['role' => 'assistant', 'content' => 'First reply'],
                ['role' => 'user', 'content' => 'Second message'],
            ],
            timeout: 5.0,
        );

        $promptMsg = array_values(array_filter($sentMessages, fn($m) => ($m['method'] ?? '') === 'session/prompt'));
        $text = $promptMsg[0]['params']['prompt']['content'][0]['text'];

        $this->assertStringContainsString('First message', $text);
        $this->assertStringContainsString('First reply', $text);
        $this->assertStringContainsString('Second message', $text);
    }

    public function testIsCommandAvailableWithValidCommand(): void
    {
        // 'php' should be available in test environment
        $this->assertTrue(AcpClient::isCommandAvailable('php'));
    }

    public function testIsCommandAvailableWithInvalidCommand(): void
    {
        $this->assertFalse(AcpClient::isCommandAvailable('nonexistent-command-that-does-not-exist-99999'));
    }
}
