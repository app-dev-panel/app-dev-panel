<?php

declare(strict_types=1);

namespace AppDevPanel\Kernel\DebugServer;

use Yiisoft\VarDumper\HandlerInterface;
use Yiisoft\VarDumper\VarDumper;

final class VarDumperHandler implements HandlerInterface
{
    private readonly Broadcaster $broadcaster;

    public function __construct(?Broadcaster $broadcaster = null)
    {
        $this->broadcaster = $broadcaster ?? new Broadcaster();
    }

    public function handle(mixed $variable, int $depth, bool $highlight = false): void
    {
        $this->broadcaster->broadcast(Connection::MESSAGE_TYPE_VAR_DUMPER, VarDumper::create($variable)->asJson(false));
    }
}
