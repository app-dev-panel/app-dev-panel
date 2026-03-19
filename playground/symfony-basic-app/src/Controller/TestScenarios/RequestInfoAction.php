<?php

declare(strict_types=1);

namespace App\Controller\TestScenarios;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/test/scenarios/request-info', name: 'test_request_info', methods: ['GET'])]
final class RequestInfoAction
{
    public function __invoke(): JsonResponse
    {
        return new JsonResponse(['scenario' => 'request:basic', 'status' => 'ok']);
    }
}
