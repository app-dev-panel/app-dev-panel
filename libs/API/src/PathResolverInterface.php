<?php

declare(strict_types=1);

namespace AppDevPanel\Api;

interface PathResolverInterface
{
    public function getRootPath(): string;

    public function getRuntimePath(): string;
}
