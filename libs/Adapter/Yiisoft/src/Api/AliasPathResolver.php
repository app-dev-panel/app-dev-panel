<?php

declare(strict_types=1);

namespace AppDevPanel\Adapter\Yiisoft\Api;

use AppDevPanel\Api\PathResolverInterface;
use Yiisoft\Aliases\Aliases;

/**
 * Bridges Yii's alias system to the framework-agnostic PathResolverInterface.
 */
final class AliasPathResolver implements PathResolverInterface
{
    public function __construct(
        private readonly Aliases $aliases,
    ) {}

    public function getRootPath(): string
    {
        return $this->aliases->get('@root');
    }

    public function getRuntimePath(): string
    {
        return $this->aliases->get('@runtime');
    }
}
