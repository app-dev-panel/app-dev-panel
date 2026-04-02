<?php

declare(strict_types=1);

namespace AppDevPanel\McpServer\Tests\Unit\Transport;

use AppDevPanel\McpServer\Transport\StdioTransport;
use PHPUnit\Framework\TestCase;

final class StdioTransportTest extends TestCase
{
    public function testSendWritesJsonLine(): void
    {
        $output = fopen('php://memory', 'rw');
        $transport = new StdioTransport(STDIN, $output);

        $transport->send(['jsonrpc' => '2.0', 'id' => 1, 'result' => []]);

        rewind($output);
        $line = fgets($output);

        $decoded = json_decode($line, true, 512, JSON_THROW_ON_ERROR);
        $this->assertSame('2.0', $decoded['jsonrpc']);
        $this->assertSame(1, $decoded['id']);
    }

    public function testReceiveReadsJsonLine(): void
    {
        $input = fopen('php://memory', 'rw');
        fwrite($input, '{"jsonrpc":"2.0","id":1,"method":"ping"}' . "\n");
        rewind($input);

        $transport = new StdioTransport($input, STDOUT);
        $message = $transport->receive();

        $this->assertSame('2.0', $message['jsonrpc']);
        $this->assertSame(1, $message['id']);
        $this->assertSame('ping', $message['method']);
    }

    public function testReceiveReturnsNullOnEof(): void
    {
        $input = fopen('php://memory', 'r');
        $transport = new StdioTransport($input, STDOUT);

        $this->assertNull($transport->receive());
    }

    public function testReceiveReturnsNullOnEmptyLine(): void
    {
        $input = fopen('php://memory', 'rw');
        fwrite($input, "\n");
        rewind($input);

        $transport = new StdioTransport($input, STDOUT);

        $this->assertNull($transport->receive());
    }

    public function testReceiveThrowsOnInvalidJson(): void
    {
        $input = fopen('php://memory', 'rw');
        fwrite($input, "not-json\n");
        rewind($input);

        $transport = new StdioTransport($input, STDOUT);

        $this->expectException(\JsonException::class);
        $transport->receive();
    }

    public function testReceiveThrowsOnNonObjectJson(): void
    {
        $input = fopen('php://memory', 'rw');
        fwrite($input, "\"just a string\"\n");
        rewind($input);

        $transport = new StdioTransport($input, STDOUT);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Invalid JSON-RPC message: expected object');
        $transport->receive();
    }
}
