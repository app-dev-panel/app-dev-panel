<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Debug\DebugServer;

use Psr\Log\LoggerInterface;
use Psr\Log\LoggerTrait;
use Stringable;
use Yiisoft\VarDumper\VarDumper;

final class LoggerDecorator implements LoggerInterface
{
    use LoggerTrait;

    public Connection $connection;

    public function __construct(private LoggerInterface $decorated)
    {
        $this->connection = Connection::create();
    }

    public function log($level, Stringable|string $message, array $context = []): void
    {
        $this->connection->broadcast(
            Connection::MESSAGE_TYPE_LOGGER,
            VarDumper::create(['message' => $message, 'context' => $context])->asJson(false, 1)
        );
        $this->decorated->log($level, $message, $context);
    }
}
