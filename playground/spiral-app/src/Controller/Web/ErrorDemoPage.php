<?php

declare(strict_types=1);

namespace App\Controller\Web;

final class ErrorDemoPage
{
    public function __invoke(): never
    {
        throw new \RuntimeException('Demo: intentional error triggered from /error');
    }
}
