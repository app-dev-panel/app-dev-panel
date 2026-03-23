<?php

declare(strict_types=1);

namespace AppDevPanel\Adapter\Yiisoft\Proxy;

use AppDevPanel\Kernel\Collector\VarDumperCollector;
use AppDevPanel\Kernel\ProxyDecoratedCalls;
use Yiisoft\VarDumper\HandlerInterface;

final class VarDumperHandlerInterfaceProxy implements HandlerInterface
{
    use ProxyDecoratedCalls;

    public function __construct(
        private readonly HandlerInterface $decorated,
        private readonly VarDumperCollector $collector,
    ) {}

    public function handle(mixed $variable, int $depth, bool $highlight = false): void
    {
        $stack = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 5);

        $callStack = null;
        foreach ($stack as $value) {
            if (!array_key_exists('file', $value)) {
                continue;
            }
            if (str_ends_with($value['file'], '/var-dumper/src/functions.php')) {
                continue;
            }
            if (str_ends_with($value['file'], '/var-dumper/src/VarDumper.php')) {
                continue;
            }
            $callStack = $value;
            break;
        }
        /** @psalm-var array{file: string, line: int}|null $callStack */

        $this->collector->collect($variable, $callStack === null ? '' : $callStack['file'] . ':' . $callStack['line']);
        $this->decorated->handle($variable, $depth, $highlight);
    }
}
