<?php

declare(strict_types=1);

namespace App\Web\ErrorPage;

use RuntimeException;

final readonly class Action
{
    public function __invoke(): never
    {
        throw new RuntimeException('This is a demo exception from the ADP Yii 3 Playground error page.');
    }
}
