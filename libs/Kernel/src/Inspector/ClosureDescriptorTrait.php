<?php

declare(strict_types=1);

namespace AppDevPanel\Kernel\Inspector;

use Yiisoft\VarDumper\ClosureExporter;

/**
 * Shared trait for describing Closure instances as structured arrays with source code and location.
 *
 * Used by all framework adapter ConfigProviders to serialize event listener closures.
 */
trait ClosureDescriptorTrait
{
    private static ?ClosureExporter $closureExporter = null;

    /**
     * @return array{__closure: true, source: string, file: string|null, startLine: int|null, endLine: int|null}
     */
    private static function describeClosure(\Closure $closure): array
    {
        $ref = new \ReflectionFunction($closure);
        return [
            '__closure' => true,
            'source' => (self::$closureExporter ??= new ClosureExporter())->export($closure),
            'file' => $ref->getFileName() ?: null,
            'startLine' => $ref->getStartLine() ?: null,
            'endLine' => $ref->getEndLine() ?: null,
        ];
    }
}
