<?php

declare(strict_types=1);

namespace App\Controller\TestFixtures;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/test/fixtures/dump', name: 'test_dump', methods: ['GET'])]
final class DumpAction
{
    public function __invoke(): JsonResponse
    {
        dump(['fixture' => 'var-dumper:basic', 'nested' => ['key' => 'value']]);

        return new JsonResponse(['fixture' => 'var-dumper:basic', 'status' => 'ok']);
    }
}
