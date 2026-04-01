<?php

declare(strict_types=1);

namespace App\Web\TestFixtures;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Yiisoft\DataResponse\DataResponseFactoryInterface;

final readonly class ResetCliAction implements RequestHandlerInterface
{
    public function __construct(
        private DataResponseFactoryInterface $responseFactory,
    ) {}

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $yiiPath = dirname(__DIR__, 3) . '/yii';

        $output = [];
        $exitCode = 0;
        exec(sprintf('php %s debug:reset 2>&1', escapeshellarg($yiiPath)), $output, $exitCode);

        return $this->responseFactory->createResponse([
            'fixture' => 'reset-cli',
            'status' => $exitCode === 0 ? 'ok' : 'error',
            'exitCode' => $exitCode,
            'output' => implode("\n", $output),
        ]);
    }
}
