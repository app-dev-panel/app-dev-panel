<?php

declare(strict_types=1);

namespace AppDevPanel\Adapter\Symfony\Tests\Unit\EventSubscriber;

use AppDevPanel\Adapter\Symfony\EventSubscriber\MailerSubscriber;
use AppDevPanel\Kernel\Collector\MailerCollector;
use AppDevPanel\Kernel\Collector\TimelineCollector;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Mailer\Envelope;
use Symfony\Component\Mailer\Event\MessageEvent;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mime\RawMessage;

final class MailerSubscriberTest extends TestCase
{
    private MailerCollector $collector;

    protected function setUp(): void
    {
        $this->collector = new MailerCollector(new TimelineCollector());
        $this->collector->startup();
    }

    public function testOnMessageCollectsEmailData(): void
    {
        $subscriber = new MailerSubscriber($this->collector);

        $email = new Email()
            ->from('sender@example.com')
            ->to('recipient@example.com')
            ->subject('Test Subject')
            ->text('Text body')
            ->html('<p>HTML body</p>');

        $envelope = Envelope::create($email);
        $event = new MessageEvent($email, $envelope, 'null://null');

        $subscriber->onMessage($event);

        $collected = $this->collector->getCollected();
        $this->assertCount(1, $collected['messages']);

        $message = $collected['messages'][0];
        $this->assertSame('Test Subject', $message['subject']);
        $this->assertSame('Text body', $message['textBody']);
        $this->assertSame('<p>HTML body</p>', $message['htmlBody']);
        $this->assertArrayHasKey('sender@example.com', $message['from']);
        $this->assertArrayHasKey('recipient@example.com', $message['to']);
    }

    public function testOnMessageIgnoresNonEmailMessages(): void
    {
        $subscriber = new MailerSubscriber($this->collector);

        $rawMessage = new RawMessage('raw message content');
        $envelope = new Envelope(
            new \Symfony\Component\Mime\Address('sender@example.com'),
            [new \Symfony\Component\Mime\Address('recipient@example.com')],
        );
        $event = new MessageEvent($rawMessage, $envelope, 'null://null');

        $subscriber->onMessage($event);

        $collected = $this->collector->getCollected();
        $this->assertCount(0, $collected['messages']);
    }

    public function testSubscribedEvents(): void
    {
        $events = MailerSubscriber::getSubscribedEvents();

        $this->assertArrayHasKey(MessageEvent::class, $events);
    }
}
