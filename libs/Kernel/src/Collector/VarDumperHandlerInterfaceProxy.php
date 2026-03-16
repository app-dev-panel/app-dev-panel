<?php

declare(strict_types=1);

namespace AppDevPanel\Kernel\Collector;

use AppDevPanel\Kernel\DumpHandlerInterface;
use AppDevPanel\Kernel\ProxyDecoratedCalls;

final class VarDumperHandlerInterfaceProxy implements DumpHandlerInterface
{
    use ProxyDecoratedCalls;

    public function __construct(
        private readonly DumpHandlerInterface $decorated,
        private readonly VarDumperCollector $collector,
    ) {}

    public function handle(mixed $variable, int $depth, bool $highlight = false): void
    {
        $stack = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 5);

        $callStack = null;
        foreach ($stack as $value) {
            if (!isset($value['file'])) {
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
