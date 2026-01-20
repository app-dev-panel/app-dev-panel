<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Debug\Collector;

use Yiisoft\VarDumper\HandlerInterface;
use Yiisoft\Yii\Debug\ProxyDecoratedCalls;

final class VarDumperHandlerInterfaceProxy implements HandlerInterface
{
    use ProxyDecoratedCalls;

    public function __construct(
        private readonly HandlerInterface $decorated,
        private readonly VarDumperCollector $collector,
    ) {
    }

    public function handle(mixed $variable, int $depth, bool $highlight = false): void
    {
        $stack = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);

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

        $this->collector->collect(
            $variable,
            $callStack === null ? '' : $callStack['file'] . ':' . $callStack['line']
        );
        $this->decorated->handle($variable, $depth, $highlight);
    }
}
