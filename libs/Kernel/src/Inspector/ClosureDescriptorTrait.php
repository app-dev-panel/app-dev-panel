<?php

declare(strict_types=1);

namespace AppDevPanel\Kernel\Inspector;

/**
 * Shared trait for describing Closure instances as structured arrays with source code and location.
 *
 * Thin sugar over {@see ClosureDescriptor::describe()} — kept so adapter ConfigProviders keep their
 * existing call shape (`self::describeClosure($x)`) without importing a helper class.
 */
trait ClosureDescriptorTrait
{
    /**
     * @return array{__closure: true, source: string, file: string|null, startLine: int|null, endLine: int|null}
     */
    private static function describeClosure(\Closure $closure): array
    {
        return ClosureDescriptor::describe($closure);
    }
}
