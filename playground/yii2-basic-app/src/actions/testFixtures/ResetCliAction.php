<?php

declare(strict_types=1);

namespace App\actions\testFixtures;

use AppDevPanel\Kernel\Storage\StorageInterface;
use yii\base\Action;

final class ResetCliAction extends Action
{
    public function run(): array
    {
        /** @var \AppDevPanel\Adapter\Yii2\Module $module */
        $module = \Yii::$app->getModule('app-dev-panel');
        $module->getDebugger()->stop();

        /** @var StorageInterface $storage */
        $storage = \Yii::$container->get(StorageInterface::class);
        $storage->clear();

        return [
            'fixture' => 'reset-cli',
            'status' => 'ok',
            'exitCode' => 0,
            'output' => 'Debug storage cleared.',
        ];
    }
}
