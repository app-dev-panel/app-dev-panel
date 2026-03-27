<?php

declare(strict_types=1);

namespace App\actions\testFixtures;

use yii\base\Action;

final class FileStreamAction extends Action
{
    public function run(): array
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

        return ['fixture' => 'filesystem:streams', 'status' => 'ok'];
    }
}
