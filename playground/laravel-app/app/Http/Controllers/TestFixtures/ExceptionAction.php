<?php

declare(strict_types=1);

namespace App\Http\Controllers\TestFixtures;

final class ExceptionAction
{
    public function __invoke(): never
    {
        throw new \RuntimeException('ADP test fixture exception');
    }
}
