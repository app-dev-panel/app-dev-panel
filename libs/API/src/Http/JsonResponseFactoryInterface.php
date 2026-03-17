<?php

declare(strict_types=1);

namespace AppDevPanel\Api\Http;

use Psr\Http\Message\ResponseInterface;

interface JsonResponseFactoryInterface
{
    public function createJsonResponse(mixed $data, int $status = 200): ResponseInterface;
}
