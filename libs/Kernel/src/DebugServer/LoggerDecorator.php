<?php

declare(strict_types=1);

namespace AppDevPanel\Kernel\DebugServer;

use Psr\Log\LoggerInterface;
use Psr\Log\LoggerTrait;
use Stringable;
use Yiisoft\VarDumper\VarDumper;

final class LoggerDecorator implements LoggerInterface
{
    use LoggerTrait;

    public Broadcaster $broadcaster;

    public function __construct(
        private LoggerInterface $decorated,
    ) {
        $this->broadcaster = new Broadcaster();
    }

    public function log($level, Stringable|string $message, array $context = []): void
    {
        $this->broadcaster->broadcast(Connection::MESSAGE_TYPE_LOGGER, VarDumper::create([
            'level' => (string) $level,
            'message' => $message,
            'context' => $context,
        ])->asJson(false, 1));
        $this->decorated->log($level, $message, $context);
    }
}
