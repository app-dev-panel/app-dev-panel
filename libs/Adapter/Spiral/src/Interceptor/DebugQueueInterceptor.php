<?php

declare(strict_types=1);

namespace AppDevPanel\Adapter\Spiral\Interceptor;

use AppDevPanel\Kernel\Collector\MessageRecord;
use AppDevPanel\Kernel\Collector\QueueCollector;
use AppDevPanel\Kernel\Debugger;
use AppDevPanel\Kernel\StartupContext;
use Spiral\Interceptors\Context\CallContextInterface;
use Spiral\Interceptors\HandlerInterface;
use Spiral\Interceptors\InterceptorInterface;
use Throwable;

/**
 * Consume-side interceptor for `spiral/queue` jobs. Each job becomes its own ADP debug
 * entry — registered via `Spiral\Queue\QueueRegistry::addConsumeInterceptor()` from
 * {@see \AppDevPanel\Adapter\Spiral\Bootloader\AdpInterceptorBootloader} only when
 * `spiral/queue` is installed.
 *
 * Push-side recording is handled by {@see \AppDevPanel\Adapter\Spiral\Queue\TracingQueue}
 * (plan 03). This interceptor complements it by emitting a `handled=true` (or
 * `failed=true`) {@see MessageRecord} per consumed job.
 */
final class DebugQueueInterceptor implements InterceptorInterface
{
    public function __construct(
        private readonly Debugger $debugger,
        private readonly QueueCollector $collector,
    ) {}

    public function intercept(CallContextInterface $context, HandlerInterface $handler): mixed
    {
        $arguments = $context->getArguments();
        $payload = $arguments['payload'] ?? null;
        $messageClass = is_object($payload) ? $payload::class : 'unknown';

        $nameAttribute = $context->getAttribute('name');
        $argName = $arguments['name'] ?? null;
        $name = match (true) {
            is_string($nameAttribute) && $nameAttribute !== '' => $nameAttribute,
            is_string($argName) && $argName !== '' => $argName,
            default => $messageClass,
        };

        // Each consumed job gets its own debug entry — like an HTTP request.
        $this->debugger->startup(StartupContext::generic());
        $this->collector->collectWorkerProcessing($payload, $name);

        $start = microtime(true);
        try {
            $result = $handler->handle($context);
            $this->collector->logMessage(new MessageRecord(
                messageClass: $messageClass,
                bus: 'spiral-queue',
                transport: $name,
                dispatched: true,
                handled: true,
                failed: false,
                duration: microtime(true) - $start,
                message: $payload,
            ));
            return $result;
        } catch (Throwable $error) {
            $this->collector->logMessage(new MessageRecord(
                messageClass: $messageClass,
                bus: 'spiral-queue',
                transport: $name,
                dispatched: true,
                handled: false,
                failed: true,
                duration: microtime(true) - $start,
                message: $payload,
            ));
            throw $error;
        } finally {
            $this->debugger->shutdown();
        }
    }
}
