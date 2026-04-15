<?php

declare(strict_types=1);

namespace AppDevPanel\Kernel\Tests\Unit\Mail;

use AppDevPanel\Kernel\Collector\CollectorInterface;
use AppDevPanel\Kernel\Mail\StandaloneMailerIngestion;
use AppDevPanel\Kernel\Storage\StorageInterface;
use PHPUnit\Framework\TestCase;

final class StandaloneMailerIngestionTest extends TestCase
{
    public function testWritesEntryToStorageWithParsedMessage(): void
    {
        $storage = new RecordingStorage();
        $ingestion = new StandaloneMailerIngestion($storage);

        $raw = "From: a@b\r\nTo: c@d\r\nSubject: Hi\r\nContent-Type: text/plain\r\n\r\nBody";
        $id = $ingestion->ingest(['from' => 'a@b', 'rcpt' => ['c@d'], 'raw' => $raw]);

        $this->assertStringStartsWith('smtp-', $id);
        $this->assertCount(1, $storage->entries);
        $entry = $storage->entries[0];
        $this->assertSame($id, $entry['id']);

        $this->assertSame('smtp', $entry['summary']['context']['type']);
        $this->assertSame(['c@d'], $entry['summary']['context']['envelopeRcpt']);

        $messages = $entry['data']['mailer']['messages'];
        $this->assertCount(1, $messages);
        $this->assertSame('Hi', $messages[0]['subject']);
        $this->assertSame('Body', $messages[0]['textBody']);
    }

    public function testFallsBackToEnvelopeAddressesWhenHeadersMissing(): void
    {
        $storage = new RecordingStorage();
        $ingestion = new StandaloneMailerIngestion($storage);

        $raw = "Subject: s\r\nContent-Type: text/plain\r\n\r\nbody";
        $ingestion->ingest(['from' => 'sender@x', 'rcpt' => ['rcpt@y'], 'raw' => $raw]);

        $messages = $storage->entries[0]['data']['mailer']['messages'];
        $this->assertSame(['sender@x' => ''], $messages[0]['from']);
        $this->assertSame(['rcpt@y' => ''], $messages[0]['to']);
    }

    public function testSummaryContainsMailerTotal(): void
    {
        $storage = new RecordingStorage();
        $ingestion = new StandaloneMailerIngestion($storage);

        $ingestion->ingest([
            'from' => 'a@b',
            'rcpt' => ['c@d'],
            'raw' => "Subject: t\r\n\r\nx",
        ]);

        $this->assertSame(['total' => 1], $storage->entries[0]['summary']['mailer']);
    }

    public function testCapturesSessionIdHeaderInContext(): void
    {
        $storage = new RecordingStorage();
        $ingestion = new StandaloneMailerIngestion($storage);

        $raw = "X-ADP-Session-Id: sess-42\r\nSubject: s\r\nContent-Type: text/plain\r\n\r\nbody";
        $ingestion->ingest(['from' => 'a@b', 'rcpt' => ['c@d'], 'raw' => $raw]);

        $this->assertSame('sess-42', $storage->entries[0]['summary']['context']['sessionId']);
    }

    public function testPassesSmtpMetaIntoContext(): void
    {
        $storage = new RecordingStorage();
        $ingestion = new StandaloneMailerIngestion($storage);

        $ingestion->ingest(['from' => 'a@b', 'rcpt' => ['c@d'], 'raw' => "Subject: s\r\n\r\nx"], [
            'peer' => '127.0.0.1:55000',
        ]);

        $this->assertSame('127.0.0.1:55000', $storage->entries[0]['summary']['context']['smtp']['peer']);
    }
}

final class RecordingStorage implements StorageInterface
{
    /** @var list<array{id: string, summary: array, data: array, objects: array}> */
    public array $entries = [];

    public function addCollector(CollectorInterface $collector): void {}

    public function getData(): array
    {
        return [];
    }

    public function read(string $type, ?string $id = null): array
    {
        return [];
    }

    public function write(string $id, array $summary, array $data, array $objects): void
    {
        $this->entries[] = [
            'id' => $id,
            'summary' => $summary,
            'data' => $data,
            'objects' => $objects,
        ];
    }

    public function flush(): void {}

    public function clear(): void
    {
        $this->entries = [];
    }
}
