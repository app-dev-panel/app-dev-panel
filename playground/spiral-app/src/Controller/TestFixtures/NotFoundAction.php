<?php

declare(strict_types=1);

namespace App\Controller\TestFixtures;

use Nyholm\Psr7\Response;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class NotFoundAction
{
    public function __invoke(ServerRequestInterface $request): ResponseInterface
    {
        $body = json_encode(['error' => 'Not Found', 'path' => $request->getUri()->getPath()], JSON_THROW_ON_ERROR);

        return new Response(404)
            ->withHeader('Content-Type', 'application/json')
            ->withBody(\Nyholm\Psr7\Stream::create($body));
    }
}
