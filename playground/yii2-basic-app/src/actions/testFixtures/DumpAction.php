<?php

declare(strict_types=1);

namespace App\actions\testFixtures;

use AppDevPanel\Kernel\Collector\VarDumperCollector;
use yii\base\Action;

final class DumpAction extends Action
{
    public function run(): array
    {
        /** @var VarDumperCollector|null $collector */
        $collector = \Yii::$container->has(VarDumperCollector::class)
            ? \Yii::$container->get(VarDumperCollector::class)
            : null;

        $data = ['fixture' => 'var-dumper:basic', 'nested' => ['key' => 'value']];

        $collector?->collect($data, __FILE__ . ':' . __LINE__);

        return ['fixture' => 'var-dumper:basic', 'status' => 'ok'];
    }
}
