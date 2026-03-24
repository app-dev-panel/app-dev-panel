<?php

declare(strict_types=1);

namespace AppDevPanel\Adapter\Laravel\Tests\Unit\EventListener;

use AppDevPanel\Adapter\Laravel\EventListener\MailListener;
use AppDevPanel\Kernel\Collector\MailerCollector;
use AppDevPanel\Kernel\Collector\TimelineCollector;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Mail\Events\MessageSent;
use Illuminate\Mail\SentMessage;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Mailer\Envelope;
use Symfony\Component\Mailer\SentMessage as SymfonySentMessage;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;

final class MailListenerTest extends TestCase
{
    public function testRegistersMessageSentListener(): void
    {
        $registeredListeners = [];
        $dispatcher = $this->createMock(Dispatcher::class);
        $dispatcher
            ->method('listen')
            ->willReturnCallback(static function (string $event, \Closure $callback) use (&$registeredListeners): void {
                $registeredListeners[$event] = $callback;
            });

        $listener = new MailListener($this->createCollector(...));
        $listener->register($dispatcher);

        $this->assertCount(1, $registeredListeners);
        $this->assertArrayHasKey(MessageSent::class, $registeredListeners);
    }

    public function testCollectsMailData(): void
    {
        [$collector, $callback] = $this->registerListener();

        $email = new Email()
            ->from(new Address('sender@example.com', 'Sender'))
            ->to(new Address('recipient@example.com', 'Recipient'))
            ->subject('Test Subject')
            ->text('Hello World');

        $event = $this->createMessageSentEvent($email);
        $callback($event);

        $collected = $collector->getCollected();
        $this->assertCount(1, $collected['messages']);

        $message = $collected['messages'][0];
        $this->assertSame('Test Subject', $message['subject']);
        $this->assertArrayHasKey('sender@example.com', $message['from']);
        $this->assertArrayHasKey('recipient@example.com', $message['to']);
    }

    public function testCollectsMailWithoutName(): void
    {
        [$collector, $callback] = $this->registerListener();

        $email = new Email()
            ->from(new Address('no-reply@example.com'))
            ->to(new Address('user@example.com'))
            ->subject('No Name')
            ->text('Body');

        $event = $this->createMessageSentEvent($email);
        $callback($event);

        $collected = $collector->getCollected();
        $this->assertCount(1, $collected['messages']);
        $this->assertSame('no-reply@example.com', $collected['messages'][0]['from']['no-reply@example.com']);
    }

    private function createMessageSentEvent(Email $email): MessageSent
    {
        $envelope = new Envelope(
            $email->getFrom()[0] ?? new Address('test@test.com'),
            array_map(static fn(Address $a) => $a, $email->getTo()),
        );
        $symfonySentMessage = new SymfonySentMessage($email, $envelope);
        $sentMessage = new SentMessage($symfonySentMessage);

        return new MessageSent($sentMessage);
    }

    private function createCollector(): MailerCollector
    {
        $timeline = new TimelineCollector();
        $collector = new MailerCollector($timeline);
        $timeline->startup();
        $collector->startup();
        return $collector;
    }

    /**
     * @return array{MailerCollector, \Closure}
     */
    private function registerListener(): array
    {
        $collector = $this->createCollector();
        $callback = null;

        $dispatcher = $this->createMock(Dispatcher::class);
        $dispatcher
            ->method('listen')
            ->willReturnCallback(static function (string $event, \Closure $cb) use (&$callback): void {
                $callback = $cb;
            });

        $listener = new MailListener(static fn() => $collector);
        $listener->register($dispatcher);

        return [$collector, $callback];
    }
}
