<?php

declare(strict_types=1);

namespace AppDevPanel\Kernel\Tests\Unit\Collector;

use AppDevPanel\Kernel\Collector\CollectorInterface;
use AppDevPanel\Kernel\Collector\MailerCollector;
use AppDevPanel\Kernel\Collector\TimelineCollector;
use AppDevPanel\Kernel\Tests\Shared\AbstractCollectorTestCase;

final class MailerCollectorTest extends AbstractCollectorTestCase
{
    protected function getCollector(): CollectorInterface
    {
        return new MailerCollector(new TimelineCollector());
    }

    protected function collectTestData(CollectorInterface $collector): void
    {
        /** @var MailerCollector $collector */
        $collector->collectMessage([
            'from' => ['sender@example.com' => 'Sender'],
            'to' => ['recipient@example.com' => 'Recipient'],
            'cc' => [],
            'bcc' => [],
            'replyTo' => [],
            'subject' => 'Test Subject',
            'textBody' => 'Hello World',
            'htmlBody' => '<p>Hello World</p>',
            'raw' => 'From: sender@example.com\r\nTo: recipient@example.com\r\nSubject: Test',
            'charset' => 'utf-8',
            'date' => 'Thu, 19 Mar 2026 12:00:00 +0000',
        ]);
    }

    protected function checkCollectedData(array $data): void
    {
        $this->assertArrayHasKey('messages', $data);
        $this->assertCount(1, $data['messages']);

        $msg = $data['messages'][0];
        $this->assertSame(['sender@example.com' => 'Sender'], $msg['from']);
        $this->assertSame(['recipient@example.com' => 'Recipient'], $msg['to']);
        $this->assertSame('Test Subject', $msg['subject']);
        $this->assertSame('Hello World', $msg['textBody']);
        $this->assertSame('<p>Hello World</p>', $msg['htmlBody']);
        $this->assertSame('utf-8', $msg['charset']);
        // New fields default to empty when adapter omits them.
        $this->assertNull($msg['messageId']);
        $this->assertSame([], $msg['headers']);
        $this->assertSame([], $msg['attachments']);
        $this->assertIsInt($msg['size']);
    }

    protected function checkSummaryData(array $data): void
    {
        $this->assertArrayHasKey('mailer', $data);
        $this->assertSame(1, $data['mailer']['total']);
    }

    public function testCollectMultipleMessages(): void
    {
        $collector = new MailerCollector(new TimelineCollector());
        $collector->startup();

        $msg = $this->makeMessage('first@test.com', 'First');
        $msg2 = $this->makeMessage('second@test.com', 'Second');

        $collector->collectMessages([$msg, $msg2]);

        $data = $collector->getCollected();
        $this->assertCount(2, $data['messages']);
        $this->assertSame('First', $data['messages'][0]['subject']);
        $this->assertSame('Second', $data['messages'][1]['subject']);
    }

    public function testInactiveGuards(): void
    {
        $collector = new MailerCollector(new TimelineCollector());

        $collector->collectMessage($this->makeMessage('test@test.com', 'Test'));
        $collector->collectMessages([$this->makeMessage('test@test.com', 'Test')]);

        $this->assertSame([], $collector->getCollected());
        $this->assertSame([], $collector->getSummary());
    }

    public function testCollectMessageNormalizesAttachmentsAndSize(): void
    {
        $collector = new MailerCollector(new TimelineCollector());
        $collector->startup();

        $content = 'hello';
        $contentBase64 = base64_encode($content);

        $collector->collectMessage([
            'from' => ['sender@example.com' => 'Sender'],
            'to' => ['recipient@example.com' => 'Recipient'],
            'subject' => 'With attachment',
            'raw' => 'RAW',
            'messageId' => '<abc@example.com>',
            'headers' => ['X-Custom' => 'yes'],
            'attachments' => [
                [
                    'filename' => 'release-notes.txt',
                    'contentType' => 'text/plain',
                    'size' => \strlen($content),
                    'contentId' => null,
                    'inline' => false,
                    'contentBase64' => $contentBase64,
                ],
                [
                    'filename' => 'logo.png',
                    'contentType' => 'image/png',
                    'contentId' => 'cid-123',
                    'inline' => true,
                    'contentBase64' => $contentBase64,
                ],
            ],
        ]);

        $data = $collector->getCollected();
        $msg = $data['messages'][0];

        $this->assertSame('<abc@example.com>', $msg['messageId']);
        $this->assertSame(['X-Custom' => 'yes'], $msg['headers']);
        $this->assertSame(3, $msg['size']); // strlen('RAW')
        $this->assertCount(2, $msg['attachments']);

        $this->assertSame('release-notes.txt', $msg['attachments'][0]['filename']);
        $this->assertFalse($msg['attachments'][0]['inline']);
        $this->assertNull($msg['attachments'][0]['contentId']);
        $this->assertSame(\strlen($content), $msg['attachments'][0]['size']);

        $this->assertTrue($msg['attachments'][1]['inline']);
        $this->assertSame('cid-123', $msg['attachments'][1]['contentId']);
        // Size computed from base64 when not provided.
        $this->assertSame(\strlen($content), $msg['attachments'][1]['size']);
    }

    public function testResetClearsData(): void
    {
        $collector = new MailerCollector(new TimelineCollector());
        $collector->startup();

        $collector->collectMessage($this->makeMessage('test@test.com', 'Test'));
        $this->assertCount(1, $collector->getCollected()['messages']);

        $collector->shutdown();
        $collector->startup();

        $this->assertCount(0, $collector->getCollected()['messages']);
    }

    private function makeMessage(string $to, string $subject): array
    {
        return [
            'from' => ['sender@example.com' => 'Sender'],
            'to' => [$to => ''],
            'cc' => [],
            'bcc' => [],
            'replyTo' => [],
            'subject' => $subject,
            'textBody' => 'body',
            'htmlBody' => null,
            'raw' => '',
            'charset' => 'utf-8',
            'date' => date('r'),
        ];
    }
}
