<?php

declare(strict_types=1);

namespace App\actions\testFixtures;

use yii\base\Action;

final class ExceptionAction extends Action
{
    public function run(): never
    {
        throw new \RuntimeException('ADP test scenario exception');
    }
}
