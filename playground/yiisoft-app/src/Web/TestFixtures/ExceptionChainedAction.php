<?php

declare(strict_types=1);

namespace App\Web\TestFixtures;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

final readonly class ExceptionChainedAction implements RequestHandlerInterface
{
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        try {
            throw new \InvalidArgumentException('Original cause');
        } catch (\InvalidArgumentException $e) {
            throw new \RuntimeException('Wrapper exception', 0, $e);
        }
    }
}
