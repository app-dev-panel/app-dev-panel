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

    private readonly Broadcaster $broadcaster;

    public function __construct(
        private readonly LoggerInterface $decorated,
        ?Broadcaster $broadcaster = null,
    ) {
        $this->broadcaster = $broadcaster ?? new Broadcaster();
    }

    public function log($level, Stringable|string $message, array $context = []): void
    {
        $this->decorated->log($level, $message, $context);
        $this->broadcaster->broadcast(Connection::MESSAGE_TYPE_LOGGER, VarDumper::create([
            'level' => $level,
            'message' => $message,
            'context' => $context,
        ])->asJson(false, 1));
    }
}
