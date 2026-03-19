<?php

declare(strict_types=1);

namespace App\actions\testScenarios;

use yii\base\Action;
use yii\helpers\VarDumper;

final class DumpAction extends Action
{
    public function run(): array
    {
        VarDumper::dump(['scenario' => 'var-dumper:basic', 'nested' => ['key' => 'value']], 10, false);

        return ['scenario' => 'var-dumper:basic', 'status' => 'ok'];
    }
}
