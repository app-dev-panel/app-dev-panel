<?php

declare(strict_types=1);

namespace AppDevPanel\Adapter\Yiisoft\Api\Inspector;

interface CommandInterface
{
    public static function getTitle(): string;

    public static function getDescription(): string;

    public function run(): CommandResponse;
}
