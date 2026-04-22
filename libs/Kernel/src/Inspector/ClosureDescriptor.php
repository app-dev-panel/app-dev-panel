<?php

declare(strict_types=1);

namespace AppDevPanel\Kernel\Inspector;

use Closure;
use ReflectionFunction;
use Yiisoft\VarDumper\ClosureExporter;

/**
 * Converts a {@see Closure} into a structured descriptor the frontend can render as code.
 *
 * The resulting shape is stable across the entire codebase — storage dumps, live inspector
 * responses, and adapter event-listener normalisation all emit the same `__closure` marker
 * so that {@see \AppDevPanel\Sdk\Component\JsonRenderer} can reliably detect and render it.
 */
final class ClosureDescriptor
{
    private static ?ClosureExporter $exporter = null;

    /**
     * @return array{__closure: true, source: string, file: string|null, startLine: int|null, endLine: int|null}
     */
    public static function describe(Closure $closure): array
    {
        $ref = new ReflectionFunction($closure);
        return [
            '__closure' => true,
            'source' => (self::$exporter ??= new ClosureExporter())->export($closure),
            'file' => $ref->getFileName() ?: null,
            'startLine' => $ref->getStartLine() ?: null,
            'endLine' => $ref->getEndLine() ?: null,
        ];
    }
}
