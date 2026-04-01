<?php

declare(strict_types=1);

namespace App\Web\Api;

use RuntimeException;

final readonly class ErrorAction
{
    public function __invoke(): never
    {
        throw new RuntimeException('This is a demo API exception from the ADP Yii 3 Playground.');
    }
}
