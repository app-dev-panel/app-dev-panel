<?php

declare(strict_types=1);

namespace App\actions\testFixtures;

use yii\base\Action;

final class RequestInfoAction extends Action
{
    public function run(): array
    {
        return ['fixture' => 'request:basic', 'status' => 'ok'];
    }
}
