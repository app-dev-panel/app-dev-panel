<?php

declare(strict_types=1);

namespace AppDevPanel\Kernel\DebugServer;

use AppDevPanel\Kernel\DumpHandlerInterface;

final class VarDumperHandler implements DumpHandlerInterface
{
    public Connection $connection;

    public function __construct()
    {
        $this->connection = Connection::create();
    }

    public function handle(mixed $variable, int $depth, bool $highlight = false): void
    {
        $json = json_encode($variable, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
        $this->connection->broadcast(Connection::MESSAGE_TYPE_VAR_DUMPER, $json);
    }
}
