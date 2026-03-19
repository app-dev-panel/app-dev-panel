<?php

declare(strict_types=1);

namespace AppDevPanel\Adapter\Yii2\Tests\Unit\Collector;

use AppDevPanel\Adapter\Yii2\Collector\MailerCollector;
use AppDevPanel\Kernel\Collector\CollectorInterface;
use AppDevPanel\Kernel\Collector\TimelineCollector;
use AppDevPanel\Kernel\Tests\Shared\AbstractCollectorTestCase;
use yii\mail\MessageInterface;

final class MailerCollectorTest extends AbstractCollectorTestCase
{
    protected function getCollector(): CollectorInterface
    {
        return new MailerCollector(new TimelineCollector());
    }

    protected function collectTestData(CollectorInterface $collector): void
    {
        /** @var MailerCollector $collector */
        $collector->logMessage($this->createMockMessage(
            from: ['sender@example.com' => 'Sender'],
            to: ['recipient@example.com' => 'Recipient'],
            cc: null,
            bcc: null,
            replyTo: null,
            subject: 'Test Subject',
            charset: 'utf-8',
        ));
        $collector->logMessage($this->createMockMessage(
            from: ['noreply@app.com' => 'App'],
            to: ['user@example.com' => 'User'],
            cc: ['cc@example.com' => ''],
            bcc: ['bcc@example.com' => ''],
            replyTo: ['reply@example.com' => ''],
            subject: 'Welcome Email',
            charset: 'utf-8',
        ));
    }

    protected function checkCollectedData(array $data): void
    {
        $this->assertArrayHasKey('messages', $data);
        $this->assertCount(2, $data['messages']);

        $first = $data['messages'][0];
        $this->assertSame(['sender@example.com' => 'Sender'], $first['from']);
        $this->assertSame(['recipient@example.com' => 'Recipient'], $first['to']);
        $this->assertSame([], $first['cc']);
        $this->assertSame([], $first['bcc']);
        $this->assertSame([], $first['replyTo']);
        $this->assertSame('Test Subject', $first['subject']);
        $this->assertArrayHasKey('date', $first);
        $this->assertArrayHasKey('charset', $first);
        $this->assertArrayHasKey('raw', $first);

        $second = $data['messages'][1];
        $this->assertSame(['noreply@app.com' => 'App'], $second['from']);
        $this->assertSame(['cc@example.com' => ''], $second['cc']);
        $this->assertSame(['bcc@example.com' => ''], $second['bcc']);
    }

    protected function checkSummaryData(array $data): void
    {
        $this->assertArrayHasKey('mailer', $data);
        $this->assertSame(2, $data['mailer']['messageCount']);
    }

    public function testLogMessageIgnoredWhenInactive(): void
    {
        $collector = new MailerCollector(new TimelineCollector());

        $collector->logMessage($this->createMockMessage(
            from: ['a@b.com' => ''],
            to: ['b@c.com' => ''],
            subject: 'Test',
        ));

        $this->assertSame([], $collector->getCollected());
    }

    public function testResetClearsData(): void
    {
        $collector = new MailerCollector(new TimelineCollector());
        $collector->startup();

        $collector->logMessage($this->createMockMessage(
            from: ['a@b.com' => ''],
            to: ['b@c.com' => ''],
            subject: 'Test',
        ));
        $this->assertCount(1, $collector->getCollected()['messages']);

        $collector->shutdown();
        $collector->startup();
        $this->assertCount(0, $collector->getCollected()['messages']);
    }

    private function createMockMessage(
        array $from = [],
        array $to = [],
        ?array $cc = null,
        ?array $bcc = null,
        ?array $replyTo = null,
        string $subject = '',
        string $charset = 'utf-8',
    ): MessageInterface {
        $message = $this->createMock(MessageInterface::class);
        $message->method('getFrom')->willReturn($from);
        $message->method('getTo')->willReturn($to);
        $message->method('getCc')->willReturn($cc);
        $message->method('getBcc')->willReturn($bcc);
        $message->method('getReplyTo')->willReturn($replyTo);
        $message->method('getSubject')->willReturn($subject);
        $message->method('getCharset')->willReturn($charset);

        return $message;
    }
}
