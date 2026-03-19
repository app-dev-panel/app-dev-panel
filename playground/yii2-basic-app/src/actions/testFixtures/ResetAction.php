<?php

declare(strict_types=1);

namespace App\actions\testFixtures;

use AppDevPanel\Kernel\Storage\StorageInterface;
use yii\base\Action;

final class ResetAction extends Action
{
    public function run(): array
    {
        /** @var StorageInterface $storage */
        $storage = \Yii::$container->get(StorageInterface::class);
        $storage->clear();

        return ['fixture' => 'reset', 'status' => 'ok'];
    }
}
