<?php

declare(strict_types=1);

namespace AppDevPanel\Adapter\Yiisoft\DebugServer;

use AppDevPanel\Kernel\DebugServer\Broadcaster;
use AppDevPanel\Kernel\DebugServer\Connection;
use Yiisoft\VarDumper\HandlerInterface;
use Yiisoft\VarDumper\VarDumper;

final class VarDumperHandler implements HandlerInterface
{
    public Broadcaster $broadcaster;

    public function __construct()
    {
        $this->broadcaster = new Broadcaster();
    }

    public function handle(mixed $variable, int $depth, bool $highlight = false): void
    {
        $this->broadcaster->broadcast(Connection::MESSAGE_TYPE_VAR_DUMPER, VarDumper::create($variable)->asJson(false));
    }
}
