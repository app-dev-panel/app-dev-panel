<?php

declare(strict_types=1);

namespace AppDevPanel\Kernel\Collector;

use AppDevPanel\Kernel\ProxyDecoratedCalls;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use Stringable;

final class LoggerInterfaceProxy implements LoggerInterface
{
    use ProxyDecoratedCalls;

    public function __construct(
        private readonly LoggerInterface $decorated,
        private readonly LogCollector $collector,
    ) {}

    public function emergency(string|Stringable $message, array $context = []): void
    {
        $this->log(LogLevel::EMERGENCY, $message, $context);
    }

    public function alert(string|Stringable $message, array $context = []): void
    {
        $this->log(LogLevel::ALERT, $message, $context);
    }

    public function critical(string|Stringable $message, array $context = []): void
    {
        $this->log(LogLevel::CRITICAL, $message, $context);
    }

    public function error(string|Stringable $message, array $context = []): void
    {
        $this->log(LogLevel::ERROR, $message, $context);
    }

    public function warning(string|Stringable $message, array $context = []): void
    {
        $this->log(LogLevel::WARNING, $message, $context);
    }

    public function notice(string|Stringable $message, array $context = []): void
    {
        $this->log(LogLevel::NOTICE, $message, $context);
    }

    public function info(string|Stringable $message, array $context = []): void
    {
        $this->log(LogLevel::INFO, $message, $context);
    }

    public function debug(string|Stringable $message, array $context = []): void
    {
        $this->log(LogLevel::DEBUG, $message, $context);
    }

    public function log(mixed $level, string|Stringable $message, array $context = []): void
    {
        $callStack = $this->getCallStack();

        $this->collector->collect($level, $message, $context, $callStack['file'] . ':' . $callStack['line']);
        $this->decorated->log($level, $message, $context);
    }

    /**
     * @psalm-return array{file: string, line: int}
     */
    private function getCallStack(): array
    {
        $backtrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 5);
        $lastSelfFrame = $backtrace[1];

        foreach ($backtrace as $frame) {
            if (($frame['class'] ?? null) !== self::class) {
                break;
            }
            $lastSelfFrame = $frame;
        }

        /** @psalm-var array{file: string, line: int} */
        return $lastSelfFrame;
    }
}
