<?php

declare(strict_types=1);

namespace App\Web\TestFixtures;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Yiisoft\DataResponse\DataResponseFactoryInterface;

final readonly class FileStreamAction implements RequestHandlerInterface
{
    public function __construct(
        private DataResponseFactoryInterface $responseFactory,
    ) {}

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $tmpDir = sys_get_temp_dir() . '/adp-test-stream-' . uniqid();
        $tmpFile = $tmpDir . '/stream-test.txt';

        // mkdir
        mkdir($tmpDir, 0o777, true);

        // fopen + fwrite + fread + fclose
        $stream = fopen($tmpFile, 'w+');
        fwrite($stream, 'ADP file stream test');
        fseek($stream, 0);
        fread($stream, 20);
        fclose($stream);

        // rename
        $renamedFile = $tmpDir . '/stream-test-renamed.txt';
        rename($tmpFile, $renamedFile);

        // unlink + rmdir (cleanup)
        unlink($renamedFile);
        rmdir($tmpDir);

        return $this->responseFactory->createResponse(['fixture' => 'filesystem:streams', 'status' => 'ok']);
    }
}
