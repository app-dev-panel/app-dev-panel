<?php

declare(strict_types=1);

namespace AppDevPanel\Api\Tests\Unit\Inspector\Command;

use AppDevPanel\Api\Inspector\Command\PsalmCommand;
use AppDevPanel\Api\Inspector\CommandInterface;
use AppDevPanel\Api\Inspector\CommandResponse;
use AppDevPanel\Api\PathResolverInterface;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunInSeparateProcess;
use PHPUnit\Framework\TestCase;

final class PsalmCommandTest extends TestCase
{
    public function testImplementsCommandInterface(): void
    {
        $this->assertTrue(is_subclass_of(PsalmCommand::class, CommandInterface::class));
    }

    public function testGetTitle(): void
    {
        $this->assertSame('Psalm', PsalmCommand::getTitle());
    }

    public function testGetDescription(): void
    {
        $this->assertSame('', PsalmCommand::getDescription());
    }

    public function testCommandName(): void
    {
        $this->assertSame('analyse/psalm', PsalmCommand::COMMAND_NAME);
    }

    public function testIsAvailableReturnsFalseWhenPsalmNotInstalled(): void
    {
        // vimeo/psalm is NOT installed in this project
        $this->assertFalse(PsalmCommand::isAvailable());
    }

    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function testRunWithMockBinarySuccess(): void
    {
        $tmpDir = sys_get_temp_dir() . '/adp-psalm-ok-' . uniqid();
        $debugDir = $tmpDir . '/runtime/debug';
        mkdir($tmpDir . '/vendor/bin', 0755, true);
        mkdir($debugDir, 0755, true);

        // Psalm writes JSON to a report file. Create a fake psalm that writes the report.
        $reportPath = $debugDir . DIRECTORY_SEPARATOR . 'psalm-report.json';
        $script = <<<BASH
            #!/bin/bash
            echo '[]' > "$reportPath"
            exit 0
            BASH;
        // Replace the placeholder with the actual path
        $script = str_replace('$reportPath', $reportPath, <<<BASH
            #!/bin/bash
            # Psalm writes report to --report= path, extract it
            for arg in "\$@"; do
                case "\$arg" in
                    --report=*)
                        REPORT_PATH="\${arg#--report=}"
                        echo '[]' > "\$REPORT_PATH"
                        ;;
                esac
            done
            exit 0
            BASH);
        file_put_contents($tmpDir . '/vendor/bin/psalm', $script);
        chmod($tmpDir . '/vendor/bin/psalm', 0755);

        try {
            $pathResolver = $this->createPathResolver($tmpDir);
            $command = new PsalmCommand($pathResolver);
            $response = $command->run();

            $this->assertSame(CommandResponse::STATUS_OK, $response->getStatus());
            $this->assertSame([], $response->getResult());
            $this->assertSame([], $response->getErrors());
        } finally {
            @unlink($reportPath);
            @unlink($tmpDir . '/vendor/bin/psalm');
            @rmdir($debugDir);
            @rmdir($tmpDir . '/runtime/debug');
            @rmdir($tmpDir . '/runtime');
            @rmdir($tmpDir . '/vendor/bin');
            @rmdir($tmpDir . '/vendor');
            @rmdir($tmpDir);
        }
    }

    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function testRunWithMockBinaryErrorExitCode(): void
    {
        $tmpDir = sys_get_temp_dir() . '/adp-psalm-err-' . uniqid();
        $debugDir = $tmpDir . '/runtime/debug';
        mkdir($tmpDir . '/vendor/bin', 0755, true);
        mkdir($debugDir, 0755, true);

        $script = <<<'BASH'
            #!/bin/bash
            for arg in "$@"; do
                case "$arg" in
                    --report=*)
                        REPORT_PATH="${arg#--report=}"
                        echo '[{"severity":"error","line_from":5,"line_to":5,"type":"InvalidReturnType","message":"Bad return","file_name":"src/Foo.php","file_path":"src/Foo.php","snippet":"return 1;","selected_text":"1","from":100,"to":101,"snippet_from":90,"snippet_to":110,"column_from":12,"column_to":13}]' > "$REPORT_PATH"
                        ;;
                esac
            done
            exit 1
            BASH;
        file_put_contents($tmpDir . '/vendor/bin/psalm', $script);
        chmod($tmpDir . '/vendor/bin/psalm', 0755);

        try {
            $pathResolver = $this->createPathResolver($tmpDir);
            $command = new PsalmCommand($pathResolver);
            $response = $command->run();

            // Exit code 1 is not > 1, so STATUS_ERROR
            $this->assertSame(CommandResponse::STATUS_ERROR, $response->getStatus());
            $this->assertIsArray($response->getResult());
            $this->assertNotEmpty($response->getResult());
            $this->assertSame([], $response->getErrors());
        } finally {
            $reportPath = $debugDir . DIRECTORY_SEPARATOR . 'psalm-report.json';
            @unlink($reportPath);
            @unlink($tmpDir . '/vendor/bin/psalm');
            @rmdir($debugDir);
            @rmdir($tmpDir . '/runtime/debug');
            @rmdir($tmpDir . '/runtime');
            @rmdir($tmpDir . '/vendor/bin');
            @rmdir($tmpDir . '/vendor');
            @rmdir($tmpDir);
        }
    }

    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function testRunWithMockBinaryFail(): void
    {
        $tmpDir = sys_get_temp_dir() . '/adp-psalm-fail-' . uniqid();
        $debugDir = $tmpDir . '/runtime/debug';
        mkdir($tmpDir . '/vendor/bin', 0755, true);
        mkdir($debugDir, 0755, true);

        $script = <<<'BASH'
            #!/bin/bash
            for arg in "$@"; do
                case "$arg" in
                    --report=*)
                        REPORT_PATH="${arg#--report=}"
                        echo '[]' > "$REPORT_PATH"
                        ;;
                esac
            done
            echo "Internal error" >&2
            exit 2
            BASH;
        file_put_contents($tmpDir . '/vendor/bin/psalm', $script);
        chmod($tmpDir . '/vendor/bin/psalm', 0755);

        try {
            $pathResolver = $this->createPathResolver($tmpDir);
            $command = new PsalmCommand($pathResolver);
            $response = $command->run();

            $this->assertSame(CommandResponse::STATUS_FAIL, $response->getStatus());
            $this->assertNull($response->getResult());
            $this->assertNotEmpty($response->getErrors());
        } finally {
            $reportPath = $debugDir . DIRECTORY_SEPARATOR . 'psalm-report.json';
            @unlink($reportPath);
            @unlink($tmpDir . '/vendor/bin/psalm');
            @rmdir($debugDir);
            @rmdir($tmpDir . '/runtime/debug');
            @rmdir($tmpDir . '/runtime');
            @rmdir($tmpDir . '/vendor/bin');
            @rmdir($tmpDir . '/vendor');
            @rmdir($tmpDir);
        }
    }

    private function createPathResolver(string $rootPath): PathResolverInterface
    {
        $pathResolver = $this->createMock(PathResolverInterface::class);
        $pathResolver->method('getRootPath')->willReturn($rootPath);
        $pathResolver->method('getRuntimePath')->willReturn($rootPath . '/runtime');
        return $pathResolver;
    }
}
