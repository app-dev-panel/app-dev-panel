<?php

declare(strict_types=1);

namespace App\Controller\TestFixtures;

final class FilesystemAction
{
    /**
     * @return array<string, mixed>
     */
    public function __invoke(): array
    {
        $dir = sys_get_temp_dir() . '/adp-fs-fixture-' . uniqid('', true);
        mkdir($dir, 0o777, true);
        $file = $dir . '/hello.txt';

        // High-level fs_stream ops
        file_put_contents($file, 'hello ADP');
        $read = file_get_contents($file);

        // Low-level fopen/fwrite/fread/fclose
        $fh = fopen($file, 'ab');
        if ($fh !== false) {
            fwrite($fh, "\nappended");
            fclose($fh);
        }

        $rh = fopen($file, 'rb');
        $low = $rh !== false ? fread($rh, 1024) : '';
        if ($rh !== false) {
            fclose($rh);
        }

        $renamed = $dir . '/hello2.txt';
        rename($file, $renamed);
        unlink($renamed);
        rmdir($dir);

        return [
            'fixture' => 'filesystem:basic',
            'status' => 'ok',
            'read' => $read,
            'low' => $low,
        ];
    }
}
