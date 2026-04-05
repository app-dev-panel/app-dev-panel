<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use OpenApi\Generator;

final class OpenApiController
{
    public function __invoke(): JsonResponse
    {
        $openapi = Generator::scan([app_path()]);

        return new JsonResponse($openapi->toJson(), json: true);
    }
}
