<?php

declare(strict_types=1);

namespace App\Http\Controllers\TestFixtures;

final class ExceptionChainedAction
{
    public function __invoke(): never
    {
        try {
            throw new \InvalidArgumentException('Original cause');
        } catch (\InvalidArgumentException $e) {
            throw new \RuntimeException('Wrapper exception', 0, $e);
        }
    }
}
