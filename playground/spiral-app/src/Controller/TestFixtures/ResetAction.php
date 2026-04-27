<?php

declare(strict_types=1);

namespace App\Controller\TestFixtures;

use AppDevPanel\Kernel\Storage\StorageInterface;

final class ResetAction
{
    public function __construct(
        private readonly StorageInterface $storage,
    ) {}

    /**
     * @return array<string, string>
     */
    public function __invoke(): array
    {
        $this->storage->clear();

        return ['fixture' => 'reset', 'status' => 'ok'];
    }
}
