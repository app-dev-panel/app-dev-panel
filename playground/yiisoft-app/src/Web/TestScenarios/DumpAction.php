<?php

declare(strict_types=1);

namespace App\Web\TestScenarios;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Yiisoft\DataResponse\DataResponseFactoryInterface;
use Yiisoft\VarDumper\VarDumper;

final readonly class DumpAction implements RequestHandlerInterface
{
    public function __construct(
        private DataResponseFactoryInterface $responseFactory,
    ) {}

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        VarDumper::dump(['scenario' => 'var-dumper:basic', 'nested' => ['key' => 'value']]);

        return $this->responseFactory->createResponse(['scenario' => 'var-dumper:basic', 'status' => 'ok']);
    }
}
