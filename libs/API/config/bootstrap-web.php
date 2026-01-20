<?php

declare(strict_types=1);

/**
 * @var $params array
 */

use Yiisoft\Yii\Debug\Api\Inspector\ApplicationState;

return [
    static function ($container) use ($params) {
        ApplicationState::$params = $params;
    },
];
