<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Debug\Collector;

use Throwable;
use Yiisoft\ErrorHandler\Event\ApplicationError;

final class ExceptionCollector implements SummaryCollectorInterface
{
    use CollectorTrait;

    private ?Throwable $exception = null;

    public function __construct(
        private readonly TimelineCollector $timelineCollector
    ) {
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
        $exceptions = [
            $throwable,
        ];
        while (($throwable = $throwable->getPrevious()) !== null) {
            $exceptions[] = $throwable;
        }

        return array_map([$this, 'serializeException'], $exceptions);
    }

    public function collect(ApplicationError $error): void
    {
        if (!$this->isActive()) {
            return;
        }

        $this->exception = $error->getThrowable();
        $this->timelineCollector->collect($this, $error::class);
    }

    public function getSummary(): array
    {
        if (!$this->isActive()) {
            return [];
        }
        return [
            'exception' => $this->exception === null ? [] : [
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
