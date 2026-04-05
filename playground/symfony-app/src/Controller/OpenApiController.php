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

        return new JsonResponse($openapi->toJson(), json: true);
    }
}
