<?php

declare(strict_types=1);

namespace App\Controller\TestFixtures;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/test/fixtures/filesystem-streams', name: 'test_filesystem_streams', methods: ['GET'])]
final class FileStreamAction
{
    public function __invoke(): JsonResponse
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

        return new JsonResponse(['fixture' => 'filesystem:streams', 'status' => 'ok']);
    }
}
