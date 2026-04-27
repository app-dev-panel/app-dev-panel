<?php

declare(strict_types=1);

namespace AppDevPanel\Kernel\Collector;

final class DeprecationCollector implements SummaryCollectorInterface
{
    use CollectorTrait;

    private array $deprecations = [];
    private bool $handlerRegistered = false;
    /** @var callable|null */
    private mixed $previousHandler = null;

    public function __construct(
        private readonly TimelineCollector $timelineCollector,
    ) {}

    public function startup(): void
    {
        $this->reset();
        $this->isActive = true;
        if (!$this->handlerRegistered) {
            $this->registerErrorHandler();
        }
    }

    public function shutdown(): void
    {
        // Restore the error handler before flush so deprecations triggered while
        // serializing the debug payload don't get appended to this same entry.
        // Buffer preserved for post-shutdown getCollected()/getSummary() reads.
        $this->restoreErrorHandler();
        $this->isActive = false;
    }

    public function getCollected(): array
    {
        return $this->deprecations;
    }

    public function getSummary(): array
    {
        return [
            'deprecation' => [
                'total' => count($this->deprecations),
            ],
        ];
    }

    private function reset(): void
    {
        $this->deprecations = [];
    }

    private function registerErrorHandler(): void
    {
        $this->previousHandler = set_error_handler(function (
            int $errno,
            string $errstr,
            string $errfile,
            int $errline,
        ): bool {
            if ($this->isActive()) {
                $this->deprecations[] = [
                    'time' => microtime(true),
                    'message' => $errstr,
                    'file' => $errfile,
                    'line' => $errline,
                    'category' => $errno === E_USER_DEPRECATED ? 'user' : 'php',
                    'trace' => $this->buildTrace(),
                ];
                $this->timelineCollector->collect($this, count($this->deprecations));
            }

            if ($this->previousHandler !== null) {
                return (bool) ($this->previousHandler)($errno, $errstr, $errfile, $errline);
            }
            return false;
        }, E_DEPRECATED | E_USER_DEPRECATED);
        $this->handlerRegistered = true;
    }

    private function restoreErrorHandler(): void
    {
        if ($this->handlerRegistered) {
            restore_error_handler();
            $this->handlerRegistered = false;
            $this->previousHandler = null;
        }
    }

    private function buildTrace(): array
    {
        $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 10);

        // Remove frames from this collector's error handler
        $filtered = [];
        $skip = true;
        foreach ($trace as $frame) {
            if ($skip && isset($frame['class']) && $frame['class'] === self::class) {
                continue;
            }
            $skip = false;
            $filtered[] = [
                'file' => $frame['file'] ?? '',
                'line' => $frame['line'] ?? 0,
                'function' => $frame['function'] ?? '',
                'class' => $frame['class'] ?? '',
            ];
        }

        return $filtered;
    }
}
