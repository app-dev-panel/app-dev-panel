<?php

declare(strict_types=1);

namespace AppDevPanel\Api\Tests\Unit\Llm\Acp;

use AppDevPanel\Api\Llm\Acp\AcpTransport;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class AcpTransportTest extends TestCase
{
    public function testSendThrowsWhenNotConnected(): void
    {
        $transport = new AcpTransport();

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('ACP transport is not connected.');
        $transport->send(['jsonrpc' => '2.0', 'method' => 'test']);
    }

    public function testReceiveThrowsWhenNotConnected(): void
    {
        $transport = new AcpTransport();

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('ACP transport is not connected.');
        $transport->receive(0.1);
    }

    public function testIsAliveReturnsFalseWhenNoProcess(): void
    {
        $transport = new AcpTransport();

        $this->assertFalse($transport->isAlive());
    }

    public function testReadStderrReturnsEmptyWhenNoProcess(): void
    {
        $transport = new AcpTransport();

        $this->assertSame('', $transport->readStderr());
    }

    public function testSpawnThrowsWhenAlreadyRunning(): void
    {
        $transport = new AcpTransport();
        // Use PHP as a long-running process that reads stdin.
        $transport->spawn('php', ['-r', 'while(true){$l=fgets(STDIN);if($l===false)break;echo $l;}']);

        try {
            $this->expectException(RuntimeException::class);
            $this->expectExceptionMessage('ACP transport already has a running process.');
            $transport->spawn('php', ['-r', 'echo "hi";']);
        } finally {
            $transport->close();
        }
    }

    public function testSpawnAndCommunicate(): void
    {
        $transport = new AcpTransport();
        // PHP echo script: reads a line from stdin and writes it back.
        $transport->spawn('php', ['-r', 'while(true){$l=fgets(STDIN);if($l===false)break;echo $l;fflush(STDOUT);}']);

        $this->assertTrue($transport->isAlive());

        $message = ['jsonrpc' => '2.0', 'id' => 1, 'method' => 'test'];
        $transport->send($message);

        $received = $transport->receive(5.0);
        $this->assertIsArray($received);
        $this->assertSame('2.0', $received['jsonrpc']);
        $this->assertSame(1, $received['id']);
        $this->assertSame('test', $received['method']);

        $transport->close();
        $this->assertFalse($transport->isAlive());
    }

    public function testReceiveReturnsNullOnTimeout(): void
    {
        $transport = new AcpTransport();
        // Use PHP that produces no output.
        $transport->spawn('php', ['-r', 'sleep(60);']);

        $result = $transport->receive(0.1);
        $this->assertNull($result);

        $transport->close();
    }

    public function testCloseIsIdempotent(): void
    {
        $transport = new AcpTransport();
        $transport->spawn('php', ['-r', 'sleep(60);']);
        $transport->close();
        $transport->close(); // Should not throw.

        $this->assertFalse($transport->isAlive());
    }

    public function testProcessExitDetected(): void
    {
        $transport = new AcpTransport();
        // Process that exits immediately.
        $transport->spawn('php', ['-r', 'exit(0);']);

        // Give it a moment to exit.
        usleep(50_000);

        $this->assertFalse($transport->isAlive());
        $transport->close();
    }
}
