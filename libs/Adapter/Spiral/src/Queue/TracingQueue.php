<?php

declare(strict_types=1);

namespace AppDevPanel\Adapter\Spiral\Queue;

use AppDevPanel\Kernel\Collector\MessageRecord;
use AppDevPanel\Kernel\Collector\QueueCollector;
use Spiral\Queue\OptionsInterface;
use Spiral\Queue\QueueInterface;
use Throwable;

/**
 * Decorates `Spiral\Queue\QueueInterface` so every push is forwarded to
 * {@see QueueCollector} as a {@see MessageRecord} with `dispatched=true`.
 *
 * Push-side only. The consume side (job handlers) is wrapped via the queue interceptor
 * pipeline — see plan 04 (`DebugQueueInterceptor`) for the complementary `handled`/
 * `failed` recording.
 *
 * Lives under the adapter namespace because it implements a Spiral-specific contract.
 * The collector's `logMessage()` handles inactive state internally.
 */
final class TracingQueue implements QueueInterface
{
    public function __construct(
        private readonly QueueInterface $inner,
        private readonly QueueCollector $collector,
    ) {}

    public function push(string $name, array|object $payload = [], ?OptionsInterface $options = null): string
    {
        $messageClass = is_object($payload) ? $payload::class : $name;
        $transport = self::extractTransport($options);
        $start = microtime(true);

        try {
            $id = $this->inner->push($name, $payload, $options);
        } catch (Throwable $e) {
            $this->collector->logMessage(new MessageRecord(
                messageClass: $messageClass,
                bus: 'spiral-queue',
                transport: $transport,
                dispatched: false,
                handled: false,
                failed: true,
                duration: microtime(true) - $start,
                message: is_object($payload) ? $payload : $payload,
            ));
            throw $e;
        }

        $this->collector->logMessage(new MessageRecord(
            messageClass: $messageClass,
            bus: 'spiral-queue',
            transport: $transport,
            dispatched: true,
            handled: false,
            failed: false,
            duration: microtime(true) - $start,
            message: is_object($payload) ? $payload : $payload,
        ));

        return $id;
    }

    private static function extractTransport(?OptionsInterface $options): string
    {
        if ($options === null) {
            return 'default';
        }
        if (method_exists($options, 'getQueue')) {
            $queue = $options->getQueue();
            if (is_string($queue) && $queue !== '') {
                return $queue;
            }
        }
        return 'default';
    }
}
