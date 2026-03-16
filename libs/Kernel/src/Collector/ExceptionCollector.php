<?php

declare(strict_types=1);

namespace AppDevPanel\Kernel\Collector;

use Throwable;

final class ExceptionCollector implements SummaryCollectorInterface
{
    use CollectorTrait;

    private ?Throwable $exception = null;

    public function __construct(
        private readonly TimelineCollector $timelineCollector,
    ) {}

    public function getCollected(): array
    {
        if (!$this->isActive()) {
            return [];
        }
        if ($this->exception === null) {
            return [];
        }
        $throwable = $this->exception;
        $exceptions = [
            $throwable,
        ];
        while (($throwable = $throwable->getPrevious()) !== null) {
            $exceptions[] = $throwable;
        }

        return array_map([$this, 'serializeException'], $exceptions);
    }

    /**
     * Collect an exception directly.
     */
    public function collectException(Throwable $exception): void
    {
        if (!$this->isActive()) {
            return;
        }

        $this->exception = $exception;
        $this->timelineCollector->collect($this, $exception::class);
    }

    /**
     * Collect from an event object that wraps a Throwable.
     * Supports any event with getThrowable() or getError() method.
     */
    public function collect(object $error): void
    {
        if (!$this->isActive()) {
            return;
        }

        if ($error instanceof Throwable) {
            $this->exception = $error;
        } elseif (method_exists($error, 'getThrowable')) {
            $this->exception = $error->getThrowable();
        } elseif (method_exists($error, 'getError')) {
            $throwable = $error->getError();
            if ($throwable instanceof Throwable) {
                $this->exception = $throwable;
            }
        }

        $this->timelineCollector->collect($this, $error::class);
    }

    public function getSummary(): array
    {
        if (!$this->isActive()) {
            return [];
        }
        return [
            'exception' => $this->exception === null
                ? []
                : [
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
