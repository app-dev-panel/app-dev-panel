<?php

declare(strict_types=1);

namespace App\Controller;

use OpenApi\Generator;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

final class OpenApiController
{
    #[Route('/api/openapi.json', name: 'api_openapi', methods: ['GET'])]
    public function __invoke(): JsonResponse
    {
        $openapi = Generator::scan([dirname(__DIR__)]);

        $response = new JsonResponse($openapi->toJson(), json: true);
        $response->headers->set('Access-Control-Allow-Origin', '*');
        $response->headers->set('Access-Control-Allow-Methods', 'GET, OPTIONS');
        $response->headers->set('Access-Control-Allow-Headers', 'Content-Type');

        return $response;
    }
}
