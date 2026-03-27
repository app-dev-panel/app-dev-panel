<?php

declare(strict_types=1);

namespace App\Http\Controllers\TestFixtures;

use Illuminate\Http\JsonResponse;

final class FileStreamAction
{
    public function __invoke(): JsonResponse
    {
        $tmpDir = sys_get_temp_dir() . '/adp-test-stream-' . uniqid();
        $tmpFile = $tmpDir . '/stream-test.txt';
        $renamedFile = $tmpDir . '/stream-test-renamed.txt';

        mkdir($tmpDir, 0o777, true);

        $stream = fopen($tmpFile, 'w+');
        fwrite($stream, 'ADP file stream test');
        fseek($stream, 0);
        fread($stream, 20);
        fclose($stream);

        rename($tmpFile, $renamedFile);
        unlink($renamedFile);
        rmdir($tmpDir);

        return new JsonResponse(['fixture' => 'filesystem:streams', 'status' => 'ok']);
    }
}
