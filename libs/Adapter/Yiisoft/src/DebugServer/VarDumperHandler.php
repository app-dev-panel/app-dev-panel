<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Debug\DebugServer;

use Yiisoft\VarDumper\HandlerInterface;
use Yiisoft\VarDumper\VarDumper;

final class VarDumperHandler implements HandlerInterface
{
    public Connection $connection;

    public function __construct()
    {
        $this->connection = Connection::create();
    }

    public function handle(mixed $variable, int $depth, bool $highlight = false): void
    {
        $this->connection->broadcast(Connection::MESSAGE_TYPE_VAR_DUMPER, VarDumper::create($variable)->asJson(false));
    }
}
