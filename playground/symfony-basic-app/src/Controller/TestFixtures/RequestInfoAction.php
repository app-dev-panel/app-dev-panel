<?php

declare(strict_types=1);

namespace App\Controller\TestFixtures;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/test/fixtures/request-info', name: 'test_request_info', methods: ['GET'])]
final class RequestInfoAction
{
    public function __invoke(): JsonResponse
    {
        return new JsonResponse(['fixture' => 'request:basic', 'status' => 'ok']);
    }
}
