<?php

declare(strict_types=1);

namespace App\Web\TestFixtures;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Yiisoft\DataResponse\DataResponseFactoryInterface;

final readonly class FilesystemAction implements RequestHandlerInterface
{
    public function __construct(
        private DataResponseFactoryInterface $responseFactory,
    ) {}

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $tmpDir = sys_get_temp_dir() . '/adp-test-fs-' . uniqid();
        $highLevelFile = $tmpDir . '/high-level.txt';
        $streamFile = $tmpDir . '/stream-test.txt';
        $renamedFile = $tmpDir . '/stream-test-renamed.txt';

        mkdir($tmpDir, 0o777, true);

        file_put_contents($highLevelFile, 'ADP filesystem high-level test');
        file_get_contents($highLevelFile);

        $stream = fopen($streamFile, 'w+');
        fwrite($stream, 'ADP file stream test');
        fseek($stream, 0);
        fread($stream, 20);
        fclose($stream);

        rename($streamFile, $renamedFile);
        unlink($highLevelFile);
        unlink($renamedFile);
        rmdir($tmpDir);

        return $this->responseFactory->createResponse(['fixture' => 'filesystem:basic', 'status' => 'ok']);
    }
}
