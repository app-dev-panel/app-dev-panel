<?php

declare(strict_types=1);

namespace App\Controller\TestFixtures;

final class ExceptionAction
{
    public function __invoke(): never
    {
        throw new \RuntimeException('ADP test fixture exception');
    }
}
