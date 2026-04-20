<?php

declare(strict_types=1);

namespace AppDevPanel\Adapter\Yii3\Tests\Unit\Collector\Mailer;

use AppDevPanel\Adapter\Yii3\Collector\Mailer\MailerInterfaceProxy;
use AppDevPanel\Kernel\Collector\MailerCollector;
use AppDevPanel\Kernel\Collector\TimelineCollector;
use PHPUnit\Framework\TestCase;

final class MailerInterfaceProxyTest extends TestCase
{
    protected function setUp(): void
    {
        if (!interface_exists(\Yiisoft\Mailer\MailerInterface::class, true)) {
            $this->markTestSkipped('yiisoft/mailer is not installed.');
        }
    }

    public function testSendCollectsNormalizedMessageAndDelegates(): void
    {
        $message = $this->createMessageMock(
            from: ['sender@example.com'],
            to: ['recipient@example.com'],
            subject: 'Test Subject',
            textBody: 'Hello',
            htmlBody: '<p>Hello</p>',
            replyTo: ['reply@example.com'],
            cc: ['cc@example.com'],
            bcc: ['bcc@example.com'],
            charset: 'utf-8',
            date: '2026-01-01',
        );

        $mailer = $this->createMock(\Yiisoft\Mailer\MailerInterface::class);
        $mailer->expects($this->once())->method('send')->with($message);

        $timeline = new TimelineCollector();
        $timeline->startup();
        $collector = new MailerCollector($timeline);
        $collector->startup();

        $proxy = new MailerInterfaceProxy($mailer, $collector);
        $proxy->send($message);

        $collected = $collector->getCollected();
        $this->assertCount(1, $collected['messages']);

        $msg = $collected['messages'][0];
        $this->assertSame(['sender@example.com'], $msg['from']);
        $this->assertSame(['recipient@example.com'], $msg['to']);
        $this->assertSame('Test Subject', $msg['subject']);
        $this->assertSame('Hello', $msg['textBody']);
        $this->assertSame('<p>Hello</p>', $msg['htmlBody']);
        $this->assertSame(['reply@example.com'], $msg['replyTo']);
        $this->assertSame(['cc@example.com'], $msg['cc']);
        $this->assertSame(['bcc@example.com'], $msg['bcc']);
        $this->assertSame('utf-8', $msg['charset']);
        $this->assertSame('2026-01-01 00:00:00', $msg['date']);
    }

    public function testSendMultipleCollectsAllMessagesAndDelegates(): void
    {
        $msg1 = $this->createMessageMock(from: ['a@test.com'], to: ['b@test.com'], subject: 'First');
        $msg2 = $this->createMessageMock(from: ['c@test.com'], to: ['d@test.com'], subject: 'Second');

        $sendResults = new \Yiisoft\Mailer\SendResults([], []);

        $mailer = $this->createMock(\Yiisoft\Mailer\MailerInterface::class);
        $mailer->expects($this->once())->method('sendMultiple')->with([$msg1, $msg2])->willReturn($sendResults);

        $timeline = new TimelineCollector();
        $timeline->startup();
        $collector = new MailerCollector($timeline);
        $collector->startup();

        $proxy = new MailerInterfaceProxy($mailer, $collector);
        $result = $proxy->sendMultiple([$msg1, $msg2]);

        $this->assertSame($sendResults, $result);

        $collected = $collector->getCollected();
        $this->assertCount(2, $collected['messages']);
        $this->assertSame('First', $collected['messages'][0]['subject']);
        $this->assertSame('Second', $collected['messages'][1]['subject']);
    }

    public function testSendWithQuotedPrintableCharsetDecodesHtmlBody(): void
    {
        $encoded = quoted_printable_encode('<p>Hello World</p>');

        $message = $this->createMessageMock(charset: 'quoted-printable', htmlBody: $encoded);

        $mailer = $this->createMock(\Yiisoft\Mailer\MailerInterface::class);
        $mailer->method('send');

        $timeline = new TimelineCollector();
        $timeline->startup();
        $collector = new MailerCollector($timeline);
        $collector->startup();

        $proxy = new MailerInterfaceProxy($mailer, $collector);
        $proxy->send($message);

        $collected = $collector->getCollected();
        $this->assertSame('<p>Hello World</p>', $collected['messages'][0]['htmlBody']);
    }

    public function testSendNormalizesAttachmentsAndEmbeddings(): void
    {
        $attachment = \Yiisoft\Mailer\File::fromContent('release-notes', 'release.txt', 'text/plain');
        $embedding = \Yiisoft\Mailer\File::fromContent('logo-bytes', 'logo.png', 'image/png');

        $message = $this->createMessageMock();
        // Attach both files via real Message methods on the mock's __call - use actual Message instead.
        $realMessage = new \Yiisoft\Mailer\Message()
            ->withFrom('sender@example.com')
            ->withTo('recipient@example.com')
            ->withSubject('With attachments')
            ->withAttachments($attachment)
            ->withEmbeddings($embedding);

        $mailer = $this->createMock(\Yiisoft\Mailer\MailerInterface::class);
        $mailer->expects($this->once())->method('send')->with($realMessage);

        $timeline = new TimelineCollector();
        $timeline->startup();
        $collector = new MailerCollector($timeline);
        $collector->startup();

        $proxy = new MailerInterfaceProxy($mailer, $collector);
        $proxy->send($realMessage);

        $collected = $collector->getCollected();
        $this->assertCount(1, $collected['messages']);

        $attachments = $collected['messages'][0]['attachments'];
        $this->assertCount(2, $attachments);

        $regular = $attachments[0];
        $this->assertSame('release.txt', $regular['filename']);
        $this->assertSame('text/plain', $regular['contentType']);
        $this->assertFalse($regular['inline']);
        $this->assertNull($regular['contentId']);
        $this->assertSame('release-notes', base64_decode($regular['contentBase64'], true));

        $inline = $attachments[1];
        $this->assertSame('logo.png', $inline['filename']);
        $this->assertTrue($inline['inline']);
        $this->assertNotNull($inline['contentId']);
        $this->assertSame('logo-bytes', base64_decode($inline['contentBase64'], true));

        $this->assertIsInt($collected['messages'][0]['size']);
        unset($message);
    }

    public function testSendUpdatesSummary(): void
    {
        $message = $this->createMessageMock();

        $mailer = $this->createMock(\Yiisoft\Mailer\MailerInterface::class);

        $timeline = new TimelineCollector();
        $timeline->startup();
        $collector = new MailerCollector($timeline);
        $collector->startup();

        $proxy = new MailerInterfaceProxy($mailer, $collector);
        $proxy->send($message);

        $this->assertSame(['mailer' => ['total' => 1]], $collector->getSummary());
    }

    private function createMessageMock(
        array $from = [],
        array $to = [],
        string $subject = '',
        ?string $textBody = null,
        ?string $htmlBody = null,
        array $replyTo = [],
        array $cc = [],
        array $bcc = [],
        string $charset = 'utf-8',
        ?string $date = null,
    ): \Yiisoft\Mailer\MessageInterface {
        $message = $this->createMock(\Yiisoft\Mailer\MessageInterface::class);
        $message->method('getFrom')->willReturn($from);
        $message->method('getTo')->willReturn($to);
        $message->method('getSubject')->willReturn($subject);
        $message->method('getTextBody')->willReturn($textBody);
        $message->method('getHtmlBody')->willReturn($htmlBody);
        $message->method('getReplyTo')->willReturn($replyTo);
        $message->method('getCc')->willReturn($cc);
        $message->method('getBcc')->willReturn($bcc);
        $message->method('getCharset')->willReturn($charset);
        $message->method('getDate')->willReturn($date !== null ? new \DateTimeImmutable($date) : null);
        $message->method('__toString')->willReturn('raw-message');
        return $message;
    }
}
