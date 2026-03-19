<?php

declare(strict_types=1);

namespace App\actions\testScenarios;

use yii\base\Action;

final class RequestInfoAction extends Action
{
    public function run(): array
    {
        return ['scenario' => 'request:basic', 'status' => 'ok'];
    }
}
