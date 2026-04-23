<?php

declare(strict_types=1);

namespace App\Controller\TestFixtures;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/test/fixtures/filesystem', name: 'test_filesystem', methods: ['GET'])]
final class FilesystemAction
{
    public function __invoke(): JsonResponse
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

        return new JsonResponse(['fixture' => 'filesystem:basic', 'status' => 'ok']);
    }
}
