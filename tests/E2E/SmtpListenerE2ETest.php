<?php

declare(strict_types=1);

namespace AppDevPanel\Tests\E2E;

use AppDevPanel\Kernel\DebuggerIdGenerator;
use AppDevPanel\Kernel\Storage\FileStorage;
use AppDevPanel\Kernel\Storage\StorageInterface;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Process\PhpExecutableFinder;
use Symfony\Component\Process\Process;

/**
 * End-to-end test for the SMTP listener: spawns the real `mail:listen` CLI command in
 * a subprocess, sends a realistic multipart mail over raw SMTP, then verifies that the
 * captured entry is written to FileStorage with the expected shape.
 *
 * This test does not require a browser or any framework — it exercises the full stack:
 * SmtpListenCommand → SmtpServer → SmtpSession → MimeParser → StandaloneMailerIngestion → FileStorage.
 */
#[Group('e2e')]
final class SmtpListenerE2ETest extends TestCase
{
    private string $storagePath = '';

    /** @var Process<string, string>|null */
    private ?Process $listener = null;

    private int $port = 0;

    protected function setUp(): void
    {
        if (PHP_OS_FAMILY === 'Windows') {
            $this->markTestSkipped('E2E listener relies on POSIX signals for shutdown.');
        }
        $this->storagePath = sys_get_temp_dir() . '/adp-smtp-e2e-' . uniqid();
        $this->port = $this->pickFreePort();
        $this->startListener();
    }

    protected function tearDown(): void
    {
        $this->stopListener();
        if (is_dir($this->storagePath)) {
            $this->removeDir($this->storagePath);
        }
    }

    public function testCapturesMultipartMailIntoStorage(): void
    {
        $sessionId = 'e2e-' . uniqid();
        $boundary = 'bnd-' . uniqid();
        $body = $this->buildMultipartMessage($sessionId, $boundary);

        $this->speakSmtp([
            'from' => 'sender@e2e',
            'to' => ['alice@e2e', 'bob@e2e'],
            'dataLines' => $body,
        ]);

        // Give the listener a moment to flush the entry to disk.
        $entries = $this->waitForEntries(1, 3.0);
        $this->assertCount(1, $entries);

        [$id, $summary] = [array_key_first($entries), array_values($entries)[0]];
        $this->assertIsString($id);
        $this->assertStringStartsWith('smtp-', (string) $id);
        $this->assertSame('smtp', $summary['context']['type']);
        $this->assertSame($sessionId, $summary['context']['sessionId']);
        $this->assertSame(['alice@e2e', 'bob@e2e'], $summary['context']['envelopeRcpt']);
        $this->assertSame('sender@e2e', $summary['context']['envelopeFrom']);

        $storage = $this->openStorage();
        $entry = $storage->read(StorageInterface::TYPE_DATA, (string) $id);
        $this->assertArrayHasKey((string) $id, $entry);
        $message = $entry[(string) $id]['mailer']['messages'][0];
        $this->assertSame('E2E capture test', $message['subject']);
        $this->assertSame('Plain part', $message['textBody']);
        $this->assertSame('<p>HTML part</p>', $message['htmlBody']);
        $this->assertArrayHasKey('alice@e2e', $message['to']);
        $this->assertArrayHasKey('bob@e2e', $message['to']);
    }

    public function testSecondDeliveryOnSameConnectionCreatesSeparateEntry(): void
    {
        $this->speakSmtp([
            'from' => 'sender@a',
            'to' => ['rcpt@a'],
            'dataLines' => "Subject: First\r\nContent-Type: text/plain\r\n\r\nFirst body",
            'keepAlive' => true,
        ], function ($client): void {
            $this->writeLine($client, 'MAIL FROM:<sender@b>');
            $this->expectCode($client, '250');
            $this->writeLine($client, 'RCPT TO:<rcpt@b>');
            $this->expectCode($client, '250');
            $this->writeLine($client, 'DATA');
            $this->expectCode($client, '354');
            fwrite($client, "Subject: Second\r\nContent-Type: text/plain\r\n\r\nSecond body\r\n.\r\n");
            $this->expectCode($client, '250');
            $this->writeLine($client, 'QUIT');
        });

        $entries = $this->waitForEntries(2, 3.0);
        $this->assertCount(2, $entries);
        $subjects = array_map(
            fn(array $entry): string => $this->openStorage()->read(
                StorageInterface::TYPE_DATA,
                $entry['id'],
            )[$entry['id']]['mailer']['messages'][0]['subject'],
            array_values($entries),
        );
        sort($subjects);
        $this->assertSame(['First', 'Second'], $subjects);
    }

    public function testInvalidCommandReceives500ButDoesNotKillListener(): void
    {
        $this->speakSmtp([
            'pre' => function ($client): void {
                $this->writeLine($client, 'FROBNICATE');
                $this->expectCode($client, '500');
            },
            'from' => 'recovery@x',
            'to' => ['ok@y'],
            'dataLines' => "Subject: Recovered\r\nContent-Type: text/plain\r\n\r\nok",
        ]);

        $entries = $this->waitForEntries(1, 3.0);
        $this->assertCount(1, $entries);
        $summary = array_values($entries)[0];
        $this->assertSame('recovery@x', $summary['context']['envelopeFrom']);
    }

    // ---- helpers ----

    /**
     * @param array{
     *     from?: string,
     *     to?: list<string>,
     *     dataLines?: string,
     *     keepAlive?: bool,
     *     pre?: callable,
     * } $scenario
     */
    private function speakSmtp(array $scenario, ?callable $continuation = null): void
    {
        $client = stream_socket_client("tcp://127.0.0.1:{$this->port}", $errno, $errstr, 3.0);
        $this->assertNotFalse($client, "Failed to connect: {$errstr} ({$errno})");
        stream_set_timeout($client, 3);

        try {
            $this->expectCode($client, '220');
            $this->writeLine($client, 'EHLO e2e');
            $this->expectCode($client, '250');

            if (isset($scenario['pre'])) {
                $scenario['pre']($client);
            }

            if (isset($scenario['from'])) {
                $this->writeLine($client, "MAIL FROM:<{$scenario['from']}>");
                $this->expectCode($client, '250');
                foreach ($scenario['to'] ?? [] as $rcpt) {
                    $this->writeLine($client, "RCPT TO:<{$rcpt}>");
                    $this->expectCode($client, '250');
                }
                $this->writeLine($client, 'DATA');
                $this->expectCode($client, '354');
                fwrite($client, ($scenario['dataLines'] ?? '') . "\r\n.\r\n");
                $this->expectCode($client, '250');
            }

            if ($continuation !== null) {
                $continuation($client);
            }

            if (!($scenario['keepAlive'] ?? false)) {
                $this->writeLine($client, 'QUIT');
                $this->readUntilClose($client);
            }
        } finally {
            @fclose($client);
        }
    }

    private function buildMultipartMessage(string $sessionId, string $boundary): string
    {
        return implode("\r\n", [
            'From: "E2E Sender" <sender@e2e>',
            'To: Alice <alice@e2e>, Bob <bob@e2e>',
            "X-ADP-Session-Id: {$sessionId}",
            'Subject: E2E capture test',
            "Content-Type: multipart/alternative; boundary=\"{$boundary}\"",
            '',
            "--{$boundary}",
            'Content-Type: text/plain; charset=utf-8',
            '',
            'Plain part',
            "--{$boundary}",
            'Content-Type: text/html; charset=utf-8',
            '',
            '<p>HTML part</p>',
            "--{$boundary}--",
        ]);
    }

    /** @param resource $client */
    private function writeLine($client, string $line): void
    {
        fwrite($client, $line . "\r\n");
    }

    /** @param resource $client */
    private function expectCode($client, string $code): void
    {
        $line = $this->readLine($client);
        $this->assertStringStartsWith($code, $line, "Expected SMTP code {$code}, got: {$line}");
    }

    /** @param resource $client */
    private function readLine($client): string
    {
        $buffer = '';
        $deadline = microtime(true) + 3.0;
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

    /** @param resource $client */
    private function readUntilClose($client): void
    {
        $deadline = microtime(true) + 1.0;
        while (microtime(true) < $deadline) {
            $chunk = @fread($client, 4096);
            if ($chunk === false || $chunk === '') {
                usleep(10_000);
                if (feof($client)) {
                    return;
                }
            }
        }
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    private function waitForEntries(int $expected, float $timeout): array
    {
        $deadline = microtime(true) + $timeout;
        $entries = [];
        while (microtime(true) < $deadline) {
            $entries = $this->openStorage()->read(StorageInterface::TYPE_SUMMARY);
            if (count($entries) >= $expected) {
                return $entries;
            }
            usleep(100_000);
        }
        return $entries;
    }

    private function openStorage(): FileStorage
    {
        return new FileStorage($this->storagePath, new DebuggerIdGenerator());
    }

    private function pickFreePort(): int
    {
        $sock = stream_socket_server('tcp://127.0.0.1:0', $errno, $errstr);
        $this->assertNotFalse($sock, "Failed to bind temp socket: {$errstr}");
        $name = stream_socket_get_name($sock, false);
        $this->assertNotFalse($name);
        fclose($sock);
        $pos = strrpos((string) $name, ':');
        $this->assertNotFalse($pos);
        return (int) substr((string) $name, $pos + 1);
    }

    private function startListener(): void
    {
        $phpBinary = new PhpExecutableFinder()->find();
        $this->assertNotFalse($phpBinary, 'PHP binary not found.');

        $runner = __DIR__ . '/Support/smtp-listen-runner.php';
        $this->listener = new Process([
            $phpBinary,
            $runner,
            '--host=127.0.0.1',
            '--port=' . $this->port,
            '--storage-path=' . $this->storagePath,
        ]);
        $this->listener->setTimeout(null);
        $this->listener->start();

        // Wait until the listener accepts connections.
        $deadline = microtime(true) + 5.0;
        while (microtime(true) < $deadline) {
            $probe = @stream_socket_client("tcp://127.0.0.1:{$this->port}", $errno, $errstr, 0.2);
            if ($probe !== false) {
                fclose($probe);
                return;
            }
            usleep(50_000);
            if (!$this->listener->isRunning()) {
                $this->fail('Listener subprocess terminated early: ' . $this->listener->getErrorOutput());
            }
        }
        $this->fail('Listener did not become ready within timeout.');
    }

    private function stopListener(): void
    {
        if ($this->listener === null) {
            return;
        }
        if ($this->listener->isRunning()) {
            $this->listener->stop(2.0, SIGTERM);
        }
        $this->listener = null;
    }

    private function removeDir(string $path): void
    {
        if (!is_dir($path)) {
            return;
        }
        $it = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($path, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST,
        );
        foreach ($it as $file) {
            if ($file->isDir()) {
                @rmdir($file->getRealPath());
            } else {
                @unlink($file->getRealPath());
            }
        }
        @rmdir($path);
    }
}
