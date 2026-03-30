<?php

declare(strict_types=1);

namespace App\Web\TestFixtures;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Yiisoft\DataResponse\DataResponseFactoryInterface;

final readonly class CoverageAction implements RequestHandlerInterface
{
    public function __construct(
        private DataResponseFactoryInterface $responseFactory,
    ) {}

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        // Execute some code to generate coverage data if the collector is active
        $data = array_map(fn(int $i) => $i * $i, range(1, 100));
        $sum = array_sum($data);

        return $this->responseFactory->createResponse(['fixture' => 'coverage:basic', 'status' => 'ok', 'sum' => $sum]);
    }
}
