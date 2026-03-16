<?php

declare(strict_types=1);

namespace AppDevPanel\Adapter\Symfony\Collector;

use AppDevPanel\Kernel\Collector\CollectorTrait;
use AppDevPanel\Kernel\Collector\SummaryCollectorInterface;
use AppDevPanel\Kernel\Collector\TimelineCollector;
use Throwable;

/**
 * Collects exception data from Symfony's kernel.exception event.
 *
 * Unlike the Kernel's ExceptionCollector (which depends on Yii's ApplicationError),
 * this collector accepts a raw Throwable directly.
 */
final class SymfonyExceptionCollector implements SummaryCollectorInterface
{
    use CollectorTrait;

    private ?Throwable $exception = null;

    public function __construct(
        private readonly TimelineCollector $timelineCollector,
    ) {}

    public function collect(Throwable $throwable): void
    {
        if (!$this->isActive()) {
            return;
        }

        $this->exception = $throwable;
        $this->timelineCollector->collect($this, spl_object_id($throwable));
    }

    public function getCollected(): array
    {
        if (!$this->isActive()) {
            return [];
        }
        if ($this->exception === null) {
            return [];
        }

        $throwable = $this->exception;
        $exceptions = [$throwable];
        while (($throwable = $throwable->getPrevious()) !== null) {
            $exceptions[] = $throwable;
        }

        return array_map($this->serializeException(...), $exceptions);
    }

    public function getSummary(): array
    {
        if (!$this->isActive()) {
            return [];
        }

        if ($this->exception === null) {
            return [];
        }

        return [
            'exception' => [
                'class' => $this->exception::class,
                'message' => $this->exception->getMessage(),
                'file' => $this->exception->getFile(),
                'line' => $this->exception->getLine(),
                'code' => $this->exception->getCode(),
            ],
        ];
    }

    private function reset(): void
    {
        $this->exception = null;
    }

    private function serializeException(Throwable $throwable): array
    {
        return [
            'class' => $throwable::class,
            'message' => $throwable->getMessage(),
            'file' => $throwable->getFile(),
            'line' => $throwable->getLine(),
            'code' => $throwable->getCode(),
            'trace' => $throwable->getTrace(),
            'traceAsString' => $throwable->getTraceAsString(),
        ];
    }
}
