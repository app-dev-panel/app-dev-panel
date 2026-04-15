<?php

declare(strict_types=1);

namespace AppDevPanel\Kernel\Tests\Unit\Mail;

use AppDevPanel\Kernel\Mail\MimeParser;
use AppDevPanel\Kernel\Mail\SmtpServer;
use AppDevPanel\Kernel\Mail\StandaloneMailerIngestion;
use PHPUnit\Framework\TestCase;
use Psr\Log\AbstractLogger;
use Stringable;

final class RecordingLogger extends AbstractLogger
{
    /** @var list<string> */
    public array $messages = [];

    /** @var list<string> */
    public array $levels = [];

    public function log(mixed $level, string|Stringable $message, array $context = []): void
    {
        $this->levels[] = (string) $level;
        $this->messages[] = (string) $message;
    }
}

final class SmtpServerTest extends TestCase
{
    public function testAcceptsConnectionAndIngestsMessage(): void
    {
        $storage = new RecordingStorage();
        $ingestion = new StandaloneMailerIngestion($storage, new MimeParser());

        $server = new SmtpServer(host: '127.0.0.1', port: 0, ingestion: $ingestion, hostname: 'srv-test');
        $server->start();

        try {
            $port = $server->port();
            $client = stream_socket_client("tcp://127.0.0.1:{$port}", $errno, $errstr, 2.0);
            $this->assertNotFalse($client, "Failed to connect: {$errstr}");
            stream_set_timeout($client, 2);

            // Drive server so it accepts and writes greeting, then read it.
            $this->drive($server, 0.1);
            $this->expectLine($client, '220');

            fwrite($client, "EHLO client\r\n");
            $this->drive($server, 0.1);
            $this->readResponse($client);

            fwrite($client, "MAIL FROM:<alice@test>\r\n");
            $this->drive($server, 0.1);
            $this->expectLine($client, '250');

            fwrite($client, "RCPT TO:<bob@test>\r\n");
            $this->drive($server, 0.1);
            $this->expectLine($client, '250');

            fwrite($client, "DATA\r\n");
            $this->drive($server, 0.1);
            $this->expectLine($client, '354');

            fwrite($client, "Subject: Hi\r\nContent-Type: text/plain\r\n\r\nHello from test\r\n.\r\n");
            $this->drive($server, 0.2);
            $this->expectLine($client, '250');

            fwrite($client, "QUIT\r\n");
            $this->drive($server, 0.1);
            fclose($client);
        } finally {
            $server->stop();
        }

        $this->assertCount(1, $storage->entries);
        $messages = $storage->entries[0]['data']['mailer']['messages'];
        $this->assertSame('Hi', $messages[0]['subject']);
        $this->assertSame('Hello from test', $messages[0]['textBody']);
    }

    public function testReportsBoundPortAfterStart(): void
    {
        $storage = new RecordingStorage();
        $server = new SmtpServer('127.0.0.1', 0, new StandaloneMailerIngestion($storage));
        $server->start();
        try {
            $this->assertGreaterThan(0, $server->port());
        } finally {
            $server->stop();
        }
    }

    public function testFailsToBindOnInvalidHost(): void
    {
        $server = new SmtpServer('256.256.256.256', 1025, new StandaloneMailerIngestion(new RecordingStorage()));
        $this->expectException(\RuntimeException::class);
        $server->start();
    }

    public function testTickBeforeStartThrows(): void
    {
        $server = new SmtpServer('127.0.0.1', 0, new StandaloneMailerIngestion(new RecordingStorage()));
        $this->expectException(\LogicException::class);
        $server->tick();
    }

    public function testStopIsSafeWithoutStart(): void
    {
        $server = new SmtpServer('127.0.0.1', 0, new StandaloneMailerIngestion(new RecordingStorage()));
        $server->stop();
        $this->addToAssertionCount(1);
    }

    public function testRequestStopFlipsShouldStop(): void
    {
        $server = new SmtpServer('127.0.0.1', 0, new StandaloneMailerIngestion(new RecordingStorage()));
        $this->assertFalse($server->shouldStop());
        $server->requestStop();
        $this->assertTrue($server->shouldStop());
    }

    public function testPortReturnsConfiguredValueBeforeStart(): void
    {
        $server = new SmtpServer('127.0.0.1', 2525, new StandaloneMailerIngestion(new RecordingStorage()));
        $this->assertSame(2525, $server->port());
    }

    public function testLoggerReceivesStartAndStopMessages(): void
    {
        $logger = new RecordingLogger();
        $server = new SmtpServer(
            host: '127.0.0.1',
            port: 0,
            ingestion: new StandaloneMailerIngestion(new RecordingStorage()),
            logger: $logger,
        );
        $server->start();
        $server->stop();

        $this->assertGreaterThanOrEqual(2, count($logger->messages));
        $this->assertStringContainsString('listener started', $logger->messages[0]);
        $this->assertStringContainsString('listener stopped', $logger->messages[count($logger->messages) - 1]);
    }

    public function testTickTimeoutWithoutActivityIsSafe(): void
    {
        $server = new SmtpServer('127.0.0.1', 0, new StandaloneMailerIngestion(new RecordingStorage()));
        $server->start();
        try {
            // No clients connected — stream_select should time out and return.
            $server->tick(0.01);
            $this->addToAssertionCount(1);
        } finally {
            $server->stop();
        }
    }

    public function testClientThatDisconnectsImmediatelyIsCleanedUp(): void
    {
        $server = new SmtpServer('127.0.0.1', 0, new StandaloneMailerIngestion(new RecordingStorage()));
        $server->start();
        try {
            $port = $server->port();
            $client = stream_socket_client("tcp://127.0.0.1:{$port}", $errno, $errstr, 2.0);
            $this->assertNotFalse($client);
            fclose($client);
            // Drive a few ticks so the server sees EOF and reaps the connection.
            for ($i = 0; $i < 5; $i++) {
                $server->tick(0.02);
            }
            $this->addToAssertionCount(1);
        } finally {
            $server->stop();
        }
    }

    private function drive(SmtpServer $server, float $seconds): void
    {
        $deadline = microtime(true) + $seconds;
        do {
            $server->tick(0.02);
        } while (microtime(true) < $deadline);
    }

    /**
     * @param resource $client
     */
    private function expectLine($client, string $prefix): void
    {
        $line = $this->readResponse($client);
        $this->assertStringStartsWith($prefix, $line);
    }

    /**
     * @param resource $client
     */
    private function readResponse($client): string
    {
        $buffer = '';
        $deadline = microtime(true) + 1.0;
        while (microtime(true) < $deadline) {
            $chunk = fread($client, 4096);
            if ($chunk === false || $chunk === '') {
                usleep(10_000);
                continue;
            }
            $buffer .= $chunk;
            if (str_contains($buffer, "\r\n")) {
                break;
            }
        }
        return $buffer;
    }
}
