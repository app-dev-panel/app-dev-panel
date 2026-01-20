<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Debug\Tests\Unit\Collector;

use PHPUnit\Framework\Attributes\DataProvider;
use Yiisoft\Files\FileHelper;
use Yiisoft\Yii\Debug\Collector\CollectorInterface;
use Yiisoft\Yii\Debug\Collector\Stream\FilesystemStreamCollector;
use Yiisoft\Yii\Debug\Tests\Shared\AbstractCollectorTestCase;

final class FilesystemStreamCollectorTest extends AbstractCollectorTestCase
{
    /**
     * @param FilesystemStreamCollector $collector
     */
    protected function collectTestData(CollectorInterface $collector): void
    {
        $collector->collect(
            operation: 'read',
            path: __FILE__,
            args: ['arg1' => 'v1', 'arg2' => 'v2'],
        );
        $collector->collect(
            operation: 'read',
            path: __FILE__,
            args: ['arg3' => 'v3', 'arg4' => 'v4'],
        );
        $collector->collect(
            operation: 'mkdir',
            path: __DIR__,
            args: ['recursive'],
        );
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
                @rmdir($path);
            }
        };
        $mkdirOperation = static function (string $path) {
            mkdir($path, 0777, true);
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
                    ['path' => $path, 'args' => ['mode' => 0777, 'options' => 9]], // 9 for some reason
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
                mkdir(dirname($path), 0777, true);
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
            [ basename(__FILE__, '.php') ],
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
                mkdir($path, 0777, true);
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
                mkdir(dirname($path), 0777, true);
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
                mkdir(dirname($path), 0777, true);
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
        $this->assertEquals([
            ['path' => __FILE__, 'args' => ['arg1' => 'v1', 'arg2' => 'v2']],
            ['path' => __FILE__, 'args' => ['arg3' => 'v3', 'arg4' => 'v4']],
        ], $collected['read']);

        $this->assertCount(1, $collected['mkdir']);
        $this->assertEquals([
            ['path' => __DIR__, 'args' => ['recursive']],
        ], $collected['mkdir']);
    }

    protected function checkSummaryData(array $data): void
    {
        parent::checkSummaryData($data);
        $this->assertArrayHasKey('fs_stream', $data);
        $this->assertEquals(
            ['read' => 2, 'mkdir' => 1],
            $data['fs_stream'],
            print_r($data, true),
        );
    }
}
