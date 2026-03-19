<?php

declare(strict_types=1);

namespace App\Controller\TestFixtures;

use AppDevPanel\Kernel\Storage\StorageInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/test/fixtures/reset', name: 'test_reset', methods: ['POST', 'GET'])]
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
