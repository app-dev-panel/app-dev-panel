<?php

declare(strict_types=1);

namespace App\actions\testFixtures;

use yii\base\Action;

final class ExceptionChainedAction extends Action
{
    public function run(): never
    {
        try {
            throw new \InvalidArgumentException('Original cause');
        } catch (\InvalidArgumentException $e) {
            throw new \RuntimeException('Wrapper exception', 0, $e);
        }
    }
}
