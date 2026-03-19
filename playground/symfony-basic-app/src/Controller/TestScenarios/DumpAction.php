<?php

declare(strict_types=1);

namespace App\Controller\TestScenarios;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/test/scenarios/dump', name: 'test_dump', methods: ['GET'])]
final class DumpAction
{
    public function __invoke(): JsonResponse
    {
        dump(['scenario' => 'var-dumper:basic', 'nested' => ['key' => 'value']]);

        return new JsonResponse(['scenario' => 'var-dumper:basic', 'status' => 'ok']);
    }
}
