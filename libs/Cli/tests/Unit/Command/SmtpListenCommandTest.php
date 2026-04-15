<?php

declare(strict_types=1);

namespace Unit\Command;

use AppDevPanel\Cli\Command\SmtpListenCommand;
use AppDevPanel\Kernel\Mail\SmtpServer;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Tester\CommandTester;

final class SmtpListenCommandTest extends TestCase
{
    public function testCommandName(): void
    {
        $this->assertSame('mail:listen', new SmtpListenCommand()->getName());
    }

    public function testHasExpectedOptions(): void
    {
        $definition = new SmtpListenCommand()->getDefinition();
        $this->assertTrue($definition->hasOption('host'));
        $this->assertTrue($definition->hasOption('port'));
        $this->assertTrue($definition->hasOption('storage-path'));
        $this->assertTrue($definition->hasOption('hostname'));
        $this->assertTrue($definition->hasOption('max-size'));
        $this->assertTrue($definition->hasOption('allow-external'));
        $this->assertSame('127.0.0.1', $definition->getOption('host')->getDefault());
        $this->assertSame('1025', $definition->getOption('port')->getDefault());
    }

    public function testNonLoopbackHostWithoutFlagFails(): void
    {
        $command = new SmtpListenCommand();
        $tester = new CommandTester($command);
        $tester->execute(['--host' => '0.0.0.0', '--port' => '0']);

        $this->assertSame(1, $tester->getStatusCode());
        $this->assertStringContainsString('Refusing to bind', $tester->getDisplay());
    }

    public function testBindsAndRunsLoopUntilStopPredicate(): void
    {
        $storageDir = sys_get_temp_dir() . '/adp-smtp-test-' . uniqid();
        $iterations = 0;
        $stopPredicate = static function (SmtpServer $server) use (&$iterations): bool {
            $iterations++;
            return $iterations >= 2;
        };

        $command = new SmtpListenCommand($stopPredicate);
        $tester = new CommandTester($command);
        $exitCode = $tester->execute([
            '--host' => '127.0.0.1',
            '--port' => '0',
            '--storage-path' => $storageDir,
        ]);

        $this->assertSame(0, $exitCode);
        $this->assertStringContainsString('ADP SMTP Listener', $tester->getDisplay());
        $this->assertStringContainsString('Listening on 127.0.0.1', $tester->getDisplay());
        $this->assertGreaterThanOrEqual(2, $iterations);

        if (is_dir($storageDir)) {
            $this->removeDir($storageDir);
        }
    }

    private function removeDir(string $path): void
    {
        if (!is_dir($path)) {
            return;
        }
        $it = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($path, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST,
        );
        foreach ($it as $file) {
            if ($file->isDir()) {
                @rmdir($file->getRealPath());
            } else {
                @unlink($file->getRealPath());
            }
        }
        @rmdir($path);
    }
}
