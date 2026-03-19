<?php

declare(strict_types=1);

namespace App\actions\testFixtures;

use yii\base\Action;
use yii\helpers\VarDumper;

final class DumpAction extends Action
{
    public function run(): array
    {
        VarDumper::dump(['fixture' => 'var-dumper:basic', 'nested' => ['key' => 'value']], 10, false);

        return ['fixture' => 'var-dumper:basic', 'status' => 'ok'];
    }
}
