<?php

declare(strict_types=1);

namespace App\Controller\TestFixtures;

final class RouterAction
{
    /**
     * @return array<string, string>
     */
    public function __invoke(): array
    {
        return ['fixture' => 'router:basic', 'status' => 'ok'];
    }
}
