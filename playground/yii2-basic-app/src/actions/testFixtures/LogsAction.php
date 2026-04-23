<?php

declare(strict_types=1);

namespace App\actions\testFixtures;

use AppDevPanel\Kernel\Collector\VarDumperCollector;
use yii\base\Action;

final class LogsAction extends Action
{
    public function run(): array
    {
        \Yii::info('Test log: info level message', 'application');
        \Yii::warning('Test log: warning level message', 'application');
        \Yii::error('Test log: error level message', 'application');

        \Yii::info(
            ['Test log: debug with dump-like context' => [
                'user' => ['id' => 42, 'name' => 'Alice', 'roles' => ['admin', 'editor']],
                'metadata' => ['session' => 'abc123', 'request_id' => 'req-789'],
            ]],
            'application',
        );

        /** @var VarDumperCollector|null $collector */
        $collector = \Yii::$container->has(VarDumperCollector::class)
            ? \Yii::$container->get(VarDumperCollector::class)
            : null;
        $collector?->collect(
            ['fixture' => 'logs:basic', 'dump_example' => ['key' => 'value', 'nested' => [1, 2, 3]]],
            __FILE__ . ':' . __LINE__,
        );

        \Yii::warning('Test log: deprecated API usage detected', 'application');
        @trigger_error(
            'Method LegacyApi::doStuff() is deprecated since v2.0, use NewApi::doStuff() instead.',
            E_USER_DEPRECATED,
        );

        return ['fixture' => 'logs:basic', 'status' => 'ok'];
    }
}
