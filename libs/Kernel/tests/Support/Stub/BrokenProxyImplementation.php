<?php

declare(strict_types=1);

namespace AppDevPanel\Kernel\Tests\Support\Stub;

use Exception;

class BrokenProxyImplementation implements Interface1
{
    public function __construct(private readonly Interface1 $decorated)
    {
        throw new Exception('Broken proxy');
    }
}
