<?php

declare(strict_types=1);

namespace App\actions\testFixtures;

use yii\base\Action;

final class FilesystemAction extends Action
{
    public function run(): array
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

        return ['fixture' => 'filesystem:basic', 'status' => 'ok'];
    }
}
