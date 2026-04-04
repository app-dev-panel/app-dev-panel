<?php

declare(strict_types=1);

namespace AppDevPanel\Adapter\Symfony\Proxy;

use AppDevPanel\Kernel\Collector\MessageRecord;
use AppDevPanel\Kernel\Collector\QueueCollector;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Middleware\MiddlewareInterface;
use Symfony\Component\Messenger\Middleware\StackInterface;
use Symfony\Component\Messenger\Stamp\BusNameStamp;
use Symfony\Component\Messenger\Stamp\HandledStamp;
use Symfony\Component\Messenger\Stamp\ReceivedStamp;
use Symfony\Component\Messenger\Stamp\SentStamp;
use Symfony\Component\Messenger\Stamp\TransportMessageIdStamp;

/**
 * Symfony Messenger middleware that feeds message data to QueueCollector.
 *
 * Captures dispatched, handled, and failed messages with timing data.
 * Registered in the Symfony DI container via AppDevPanelExtension.
 */
final class MessengerCollectorMiddleware implements MiddlewareInterface
{
    public function __construct(
        private readonly QueueCollector $collector,
    ) {}

    public function handle(Envelope $envelope, StackInterface $stack): Envelope
    {
        $start = microtime(true);
        $messageClass = $envelope->getMessage()::class;

        try {
            $envelope = $stack->next()->handle($envelope, $stack);
        } catch (\Throwable $e) {
            $duration = (microtime(true) - $start) * 1000;

            $this->collector->logMessage(new MessageRecord(
                messageClass: $messageClass,
                bus: $this->getBusName($envelope),
                transport: $this->getTransport($envelope),
                dispatched: true,
                failed: true,
                duration: $duration,
                message: $this->serializeMessage($envelope->getMessage()),
            ));

            throw $e;
        }

        $duration = (microtime(true) - $start) * 1000;

        $this->collector->logMessage(new MessageRecord(
            messageClass: $messageClass,
            bus: $this->getBusName($envelope),
            transport: $this->getTransport($envelope),
            dispatched: true,
            handled: $envelope->last(HandledStamp::class) !== null,
            duration: $duration,
            message: $this->serializeMessage($envelope->getMessage()),
        ));

        return $envelope;
    }

    private function getBusName(Envelope $envelope): string
    {
        /** @var BusNameStamp|null $stamp */
        $stamp = $envelope->last(BusNameStamp::class);

        return $stamp?->getBusName() ?? 'messenger.bus.default';
    }

    private function getTransport(Envelope $envelope): string
    {
        /** @var SentStamp|null $sentStamp */
        $sentStamp = $envelope->last(SentStamp::class);
        if ($sentStamp !== null) {
            return $sentStamp->getSenderAlias() ?? $sentStamp->getSenderClass();
        }

        /** @var ReceivedStamp|null $receivedStamp */
        $receivedStamp = $envelope->last(ReceivedStamp::class);
        if ($receivedStamp !== null) {
            return $receivedStamp->getTransportName();
        }

        return 'sync';
    }

    private function serializeMessage(object $message): array
    {
        if (method_exists($message, '__serialize')) {
            return $message->__serialize();
        }

        return (array) $message;
    }
}
