<?php

declare(strict_types=1);

namespace App\Http\Controllers\TestFixtures;

use AppDevPanel\Kernel\Storage\StorageInterface;
use Illuminate\Http\JsonResponse;

final readonly class ResetAction
{
    public function __construct(
        private StorageInterface $storage,
    ) {}

    public function __invoke(): JsonResponse
    {
        $this->storage->clear();

        return new JsonResponse(['fixture' => 'reset', 'status' => 'ok']);
    }
}
