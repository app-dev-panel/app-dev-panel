<?php

declare(strict_types=1);

namespace AppDevPanel\Kernel\Tests\Unit\Collector;

use AppDevPanel\Kernel\Collector\CollectorInterface;
use AppDevPanel\Kernel\Collector\Stream\FilesystemStreamCollector;
use AppDevPanel\Kernel\Tests\Shared\AbstractCollectorTestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use Yiisoft\Files\FileHelper;

final class FilesystemStreamCollectorTest extends AbstractCollectorTestCase
{
    /**
     * @param FilesystemStreamCollector $collector
     */
    protected function collectTestData(CollectorInterface $collector): void
    {
        $collector->collect(operation: 'read', path: __FILE__, args: ['arg1' => 'v1', 'arg2' => 'v2']);
        $collector->collect(operation: 'read', path: __FILE__, args: ['arg3' => 'v3', 'arg4' => 'v4']);
        $collector->collect(operation: 'mkdir', path: __DIR__, args: ['recursive']);
    }

    #[DataProvider('dataSkipCollectOnMatchIgnoreReferences')]
    public function testSkipCollectOnMatchIgnoreReferences(
        string $path,
        callable $before,
        array $ignoredPathPatterns,
        array $ignoredClasses,
        callable $operation,
        callable $after,
        array $result,
    ): void {
        $before($path);

        try {
            $collector = new FilesystemStreamCollector(
                ignoredPathPatterns: $ignoredPathPatterns,
                ignoredClasses: $ignoredClasses,
            );
            $collector->startup();

            $operation($path);

            $collected = $collector->getCollected();
            $collector->shutdown();
        } finally {
            $after($path);
        }
        $this->assertEquals($result, $collected);
    }

    public static function dataSkipCollectOnMatchIgnoreReferences(): iterable
    {
        $mkdirBefore = static function (string $path) {
            if (is_dir($path)) {
                rmdir($path);
            }
        };
        $mkdirOperation = static function (string $path) {
            mkdir($path, 0o777, true);
        };
        $mkdirAfter = $mkdirBefore;

        yield 'mkdir matched' => [
            $path = __DIR__ . DIRECTORY_SEPARATOR . 'stub' . DIRECTORY_SEPARATOR . 'internal',
            $mkdirBefore,
            [],
            [],
            $mkdirOperation,
            $mkdirAfter,
            [
                'mkdir' => [
                    ['path' => $path, 'args' => ['mode' => 0o777, 'options' => 9]], // 9 for some reason
                ],
            ],
        ];
        yield 'mkdir ignored by path' => [
            $path,
            $mkdirBefore,
            [basename(__FILE__, '.php')],
            [],
            $mkdirOperation,
            $mkdirAfter,
            [],
        ];
        yield 'mkdir ignored by class' => [
            $path,
            $mkdirBefore,
            [],
            [self::class],
            $mkdirOperation,
            $mkdirAfter,
            [],
        ];

        $renameBefore = static function (string $path) {
            if (!is_dir(dirname($path))) {
                mkdir(dirname($path), 0o777, true);
            }
            if (!is_file($path)) {
                touch($path);
            }
        };
        $renameOperation = static function (string $path) {
            rename($path, $path . '.renamed');
        };
        $renameAfter = static function (string $path) {
            FileHelper::removeDirectory(dirname($path));
        };

        yield 'rename matched' => [
            $path = __DIR__ . DIRECTORY_SEPARATOR . 'stub' . DIRECTORY_SEPARATOR . 'file-to-rename.txt',
            $renameBefore,
            [],
            [],
            $renameOperation,
            $renameAfter,
            [
                'rename' => [
                    ['path' => $path, 'args' => ['path_to' => $path . '.renamed']],
                ],
            ],
        ];
        yield 'rename ignored by path' => [
            $path,
            $renameBefore,
            [basename(__FILE__, '.php')],
            [],
            $renameOperation,
            $renameAfter,
            [],
        ];
        yield 'rename ignored by class' => [
            $path,
            $renameBefore,
            [],
            [self::class],
            $renameOperation,
            $renameAfter,
            [],
        ];

        $rmdirBefore = static function (string $path): void {
            if (!is_dir($path)) {
                mkdir($path, 0o777, true);
            }
        };
        $rmdirOperation = static function (string $path): void {
            rmdir($path);
        };
        $rmdirAfter = static function (string $path): void {
            if (is_dir($path)) {
                rmdir($path);
            }
        };

        yield 'rmdir matched' => [
            $path = __DIR__ . DIRECTORY_SEPARATOR . 'stub' . DIRECTORY_SEPARATOR . 'dir-to-remove',
            $rmdirBefore,
            [],
            [],
            $rmdirOperation,
            $rmdirAfter,
            [
                'rmdir' => [
                    ['path' => $path, 'args' => ['options' => 8]], // 8 for some reason
                ],
            ],
        ];
        yield 'rmdir ignored by path' => [
            $path,
            $rmdirBefore,
            [basename(__FILE__, '.php')],
            [],
            $rmdirOperation,
            $rmdirAfter,
            [],
        ];
        yield 'rmdir ignored by class' => [
            $path,
            $rmdirBefore,
            [],
            [self::class],
            $rmdirOperation,
            $rmdirAfter,
            [],
        ];

        $unlinkBefore = static function (string $path) {
            if (!is_dir(dirname($path))) {
                mkdir(dirname($path), 0o777, true);
            }
            if (!is_file($path)) {
                touch($path);
            }
        };
        $unlinkOperation = static function (string $path) {
            unlink($path);
        };
        $unlinkAfter = static function (string $path) {
            FileHelper::removeDirectory(dirname($path));
        };

        yield 'unlink matched' => [
            $path = __DIR__ . DIRECTORY_SEPARATOR . 'stub' . DIRECTORY_SEPARATOR . 'file-to-unlink.txt',
            $unlinkBefore,
            [],
            [],
            $unlinkOperation,
            $unlinkAfter,
            [
                'unlink' => [
                    ['path' => $path, 'args' => []],
                ],
            ],
        ];
        yield 'unlink ignored by path' => [
            $path,
            $unlinkBefore,
            [basename(__FILE__, '.php')],
            [],
            $unlinkOperation,
            $unlinkAfter,
            [],
        ];
        yield 'unlink ignored by class' => [
            $path,
            $unlinkBefore,
            [],
            [self::class],
            $unlinkOperation,
            $unlinkAfter,
            [],
        ];

        $fileStreamBefore = static function (string $path) {
            if (!is_dir(dirname($path))) {
                mkdir(dirname($path), 0o777, true);
            }
            if (!is_file($path)) {
                touch($path);
            }
        };
        $fileStreamOperation = static function (string $path) {
            $stream = fopen($path, 'a+');
            fwrite($stream, 'test');
            fread($stream, 4);
            fseek($stream, 0);
            ftell($stream);
            feof($stream);
            ftruncate($stream, 0);
            fstat($stream);
            flock($stream, LOCK_EX);
            fclose($stream);
        };
        $fileStreamAfter = static function (string $path) {
            FileHelper::removeDirectory(dirname($path));
        };

        yield 'file stream matched' => [
            $path = __DIR__ . DIRECTORY_SEPARATOR . 'stub' . DIRECTORY_SEPARATOR . 'file-to-stream.txt',
            $fileStreamBefore,
            [],
            [],
            $fileStreamOperation,
            $fileStreamAfter,
            [
                'write' => [
                    ['path' => $path, 'args' => []],
                ],
                'read' => [
                    ['path' => $path, 'args' => []],
                ],
            ],
        ];
        yield 'file stream ignored by path' => [
            $path,
            $fileStreamBefore,
            [basename(__FILE__, '.php')],
            [],
            $fileStreamOperation,
            $fileStreamAfter,
            [],
        ];
        yield 'file stream ignored by class' => [
            $path,
            $fileStreamBefore,
            [],
            [self::class],
            $fileStreamOperation,
            $fileStreamAfter,
            [],
        ];
    }

    public function testFilePutContentsAndGetContentsCollected(): void
    {
        $collector = new FilesystemStreamCollector();
        $collector->startup();

        $tmpFile = __DIR__ . DIRECTORY_SEPARATOR . 'stub' . DIRECTORY_SEPARATOR . 'put-get-' . uniqid() . '.txt';

        try {
            (static function () use ($tmpFile): void {
                file_put_contents($tmpFile, 'ADP filesystem test');
                file_get_contents($tmpFile);
                unlink($tmpFile);
            })();

            $collected = $collector->getCollected();
            $summary = $collector->getSummary();
        } finally {
            $collector->shutdown();
            if (is_file($tmpFile)) {
                @unlink($tmpFile);
            }
        }

        $this->assertArrayHasKey('write', $collected);
        $this->assertArrayHasKey('read', $collected);
        $this->assertArrayHasKey('unlink', $collected);
        $this->assertCount(1, $collected['write']);
        $this->assertCount(1, $collected['read']);
        $this->assertCount(1, $collected['unlink']);

        $this->assertArrayHasKey('fs_stream', $summary);
        $this->assertSame(1, $summary['fs_stream']['write']);
        $this->assertSame(1, $summary['fs_stream']['read']);
        $this->assertSame(1, $summary['fs_stream']['unlink']);
    }

    public function testProxyRemainsRegisteredAcrossMultipleOperations(): void
    {
        $collector = new FilesystemStreamCollector();
        $collector->startup();

        $baseDir = __DIR__ . DIRECTORY_SEPARATOR . 'stub' . DIRECTORY_SEPARATOR . 'multi-op-' . uniqid();
        $file1 = $baseDir . DIRECTORY_SEPARATOR . 'file1.txt';
        $file2 = $baseDir . DIRECTORY_SEPARATOR . 'file2.txt';

        try {
            (static function () use ($baseDir, $file1, $file2): void {
                mkdir($baseDir, 0o777, true);
                file_put_contents($file1, 'first');
                file_put_contents($file2, 'second');
                file_get_contents($file1);
                file_get_contents($file2);
                unlink($file1);
                unlink($file2);
                rmdir($baseDir);
            })();

            $collected = $collector->getCollected();
        } finally {
            $collector->shutdown();
            if (is_file($file1)) {
                @unlink($file1);
            }
            if (is_file($file2)) {
                @unlink($file2);
            }
            if (is_dir($baseDir)) {
                @rmdir($baseDir);
            }
        }

        $this->assertArrayHasKey('mkdir', $collected);
        $this->assertArrayHasKey('write', $collected);
        $this->assertArrayHasKey('read', $collected);
        $this->assertArrayHasKey('unlink', $collected);
        $this->assertArrayHasKey('rmdir', $collected);
        $this->assertCount(1, $collected['mkdir']);
        $this->assertCount(2, $collected['write']);
        $this->assertCount(2, $collected['read']);
        $this->assertCount(2, $collected['unlink']);
        $this->assertCount(1, $collected['rmdir']);
    }

    public function testReaddirCollected(): void
    {
        $collector = new FilesystemStreamCollector();
        $collector->startup();

        $tmpDir = __DIR__ . DIRECTORY_SEPARATOR . 'stub' . DIRECTORY_SEPARATOR . 'readdir-' . uniqid();

        try {
            (static function () use ($tmpDir): void {
                mkdir($tmpDir, 0o777, true);
                file_put_contents($tmpDir . '/a.txt', 'a');
                $handle = opendir($tmpDir);
                readdir($handle);
                closedir($handle);
                unlink($tmpDir . '/a.txt');
                rmdir($tmpDir);
            })();

            $collected = $collector->getCollected();
        } finally {
            $collector->shutdown();
            if (is_file($tmpDir . '/a.txt')) {
                @unlink($tmpDir . '/a.txt');
            }
            if (is_dir($tmpDir)) {
                @rmdir($tmpDir);
            }
        }

        $this->assertArrayHasKey('readdir', $collected);
        $this->assertCount(1, $collected['readdir']);
    }

    public function testStreamIsLocalReturnsTrueWhileProxyActive(): void
    {
        $collector = new FilesystemStreamCollector();
        $collector->startup();

        try {
            $this->assertTrue(stream_is_local(__FILE__));
            $this->assertTrue(stream_is_local(__DIR__));
        } finally {
            $collector->shutdown();
        }
    }

    public function testFileStreamFixtureScenario(): void
    {
        $collector = new FilesystemStreamCollector();
        $collector->startup();

        $tmpDir = __DIR__ . DIRECTORY_SEPARATOR . 'stub' . DIRECTORY_SEPARATOR . 'stream-fixture-' . uniqid();
        $tmpFile = $tmpDir . DIRECTORY_SEPARATOR . 'stream-test.txt';
        $renamedFile = $tmpDir . DIRECTORY_SEPARATOR . 'stream-test-renamed.txt';

        try {
            // Run all operations in a closure so $stream goes out of scope
            // and the proxy flushes its buffered operations via __destruct
            (static function () use ($tmpDir, $tmpFile, $renamedFile): void {
                mkdir($tmpDir, 0o777, true);

                $stream = fopen($tmpFile, 'w+');
                fwrite($stream, 'ADP file stream test');
                fseek($stream, 0);
                fread($stream, 20);
                fclose($stream);
                unset($stream);

                rename($tmpFile, $renamedFile);
                unlink($renamedFile);
                rmdir($tmpDir);
            })();

            $collected = $collector->getCollected();
            $summary = $collector->getSummary();
        } finally {
            $collector->shutdown();
            if (is_file($tmpFile)) {
                @unlink($tmpFile);
            }
            if (is_file($renamedFile)) {
                @unlink($renamedFile);
            }
            if (is_dir($tmpDir)) {
                @rmdir($tmpDir);
            }
        }

        $this->assertArrayHasKey('mkdir', $collected);
        $this->assertArrayHasKey('write', $collected);
        $this->assertArrayHasKey('read', $collected);
        $this->assertArrayHasKey('rename', $collected);
        $this->assertArrayHasKey('unlink', $collected);
        $this->assertArrayHasKey('rmdir', $collected);

        $this->assertCount(1, $collected['mkdir']);
        $this->assertCount(1, $collected['write']);
        $this->assertCount(1, $collected['read']);
        $this->assertCount(1, $collected['rename']);
        $this->assertCount(1, $collected['unlink']);
        $this->assertCount(1, $collected['rmdir']);

        $this->assertArrayHasKey('fs_stream', $summary);
        $this->assertSame(1, $summary['fs_stream']['mkdir']);
        $this->assertSame(1, $summary['fs_stream']['write']);
        $this->assertSame(1, $summary['fs_stream']['read']);
        $this->assertSame(1, $summary['fs_stream']['rename']);
        $this->assertSame(1, $summary['fs_stream']['unlink']);
        $this->assertSame(1, $summary['fs_stream']['rmdir']);
    }

    protected function getCollector(): CollectorInterface
    {
        return new FilesystemStreamCollector();
    }

    protected function checkCollectedData(array $data): void
    {
        parent::checkCollectedData($data);
        $collected = $data;
        $this->assertCount(2, $collected);

        $this->assertCount(2, $collected['read']);
        $this->assertEquals(
            [
                ['path' => __FILE__, 'args' => ['arg1' => 'v1', 'arg2' => 'v2']],
                ['path' => __FILE__, 'args' => ['arg3' => 'v3', 'arg4' => 'v4']],
            ],
            $collected['read'],
        );

        $this->assertCount(1, $collected['mkdir']);
        $this->assertEquals(
            [
                ['path' => __DIR__, 'args' => ['recursive']],
            ],
            $collected['mkdir'],
        );
    }

    protected function checkSummaryData(array $data): void
    {
        parent::checkSummaryData($data);
        $this->assertArrayHasKey('fs_stream', $data);
        $this->assertEquals(['read' => 2, 'mkdir' => 1], $data['fs_stream'], print_r($data, true));
    }
}
