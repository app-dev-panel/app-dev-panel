<?php

declare(strict_types=1);

/**
 * @var $params array
 */

use AppDevPanel\Adapter\Yiisoft\Api\Inspector\ApplicationState;

return [
    static function ($container) use ($params) {
        ApplicationState::$params = $params;
    },
];
