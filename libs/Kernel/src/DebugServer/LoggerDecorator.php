<?php

declare(strict_types=1);

namespace AppDevPanel\Kernel\DebugServer;

use Psr\Log\LoggerInterface;
use Psr\Log\LoggerTrait;
use Stringable;

final class LoggerDecorator implements LoggerInterface
{
    use LoggerTrait;

    public Connection $connection;

    public function __construct(
        private LoggerInterface $decorated,
    ) {
        $this->connection = Connection::create();
    }

    public function log($level, Stringable|string $message, array $context = []): void
    {
        $json = json_encode(
            ['message' => $message, 'context' => $context],
            JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE,
        );
        $this->connection->broadcast(Connection::MESSAGE_TYPE_LOGGER, $json);
        $this->decorated->log($level, $message, $context);
    }
}
