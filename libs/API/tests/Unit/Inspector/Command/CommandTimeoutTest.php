<?php

declare(strict_types=1);

namespace AppDevPanel\Api\Tests\Unit\Inspector\Command;

use AppDevPanel\Api\Inspector\Command\BashCommand;
use AppDevPanel\Api\Inspector\Command\CodeceptionCommand;
use AppDevPanel\Api\Inspector\Command\CodeceptionRawCommand;
use AppDevPanel\Api\Inspector\Command\CommandTimeout;
use AppDevPanel\Api\Inspector\Command\MagoCommand;
use AppDevPanel\Api\Inspector\Command\PestCommand;
use AppDevPanel\Api\Inspector\Command\PHPStanCommand;
use AppDevPanel\Api\Inspector\Command\PHPUnitCommand;
use AppDevPanel\Api\Inspector\Command\PHPUnitRawCommand;
use AppDevPanel\Api\Inspector\Command\ProcessCommandTrait;
use AppDevPanel\Api\Inspector\Command\PsalmCommand;
use AppDevPanel\Api\Inspector\Command\TestoCommand;
use AppDevPanel\Api\Inspector\CommandInterface;
use AppDevPanel\Api\Inspector\CommandResponse;
use AppDevPanel\Api\PathResolverInterface;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class CommandTimeoutTest extends TestCase
{
    public function testDefaultMatchesFixtureCeiling(): void
    {
        $this->assertSame(120, CommandTimeout::DEFAULT);
    }

    public function testClampOnlyShortens(): void
    {
        $this->assertSame(120, CommandTimeout::clamp(120));
        $this->assertSame(120, CommandTimeout::clamp(121));
        $this->assertSame(120, CommandTimeout::clamp(PHP_INT_MAX));
        $this->assertSame(30, CommandTimeout::clamp(30));
        $this->assertSame(1, CommandTimeout::clamp(1));
        $this->assertSame(1, CommandTimeout::clamp(0));
        $this->assertSame(1, CommandTimeout::clamp(-5));
    }

    /**
     * @return iterable<string, array{class-string<CommandInterface>}>
     */
    public static function processCommands(): iterable
    {
        foreach ([
            BashCommand::class,
            MagoCommand::class,
            PsalmCommand::class,
            PHPStanCommand::class,
            PHPUnitCommand::class,
            PHPUnitRawCommand::class,
            CodeceptionCommand::class,
            CodeceptionRawCommand::class,
            PestCommand::class,
            TestoCommand::class,
        ] as $class) {
            yield $class => [$class];
        }
    }

    /**
     * Every subprocess-backed inspector command must carry the hard ceiling —
     * `setTimeout(null)` (unbounded) is not allowed anywhere.
     *
     * @param class-string<CommandInterface> $class
     */
    #[DataProvider('processCommands')]
    public function testEveryProcessCommandUsesTheCeiling(string $class): void
    {
        $this->assertContains(ProcessCommandTrait::class, class_uses($class));

        $command = $this->instantiate($class);
        $this->assertSame(CommandTimeout::DEFAULT, $command->getTimeout());
        $this->assertSame(10, $command->withTimeout(10)->getTimeout());
        $this->assertSame(CommandTimeout::DEFAULT, $command->withTimeout(9999)->getTimeout());
        // Immutable: the original keeps its ceiling.
        $this->assertSame(CommandTimeout::DEFAULT, $command->getTimeout());

        $source = (string) file_get_contents((string) new \ReflectionClass($class)->getFileName());
        $this->assertStringNotContainsString('setTimeout(null)', $source);
    }

    public function testApplyFromQueryShortensWhenRequested(): void
    {
        $command = new BashCommand($this->pathResolver(), ['true']);

        $applied = CommandTimeout::applyFromQuery($command, ['timeout' => '7']);
        $this->assertInstanceOf(BashCommand::class, $applied);
        $this->assertSame(7, $applied->getTimeout());

        $this->assertSame(
            CommandTimeout::DEFAULT,
            CommandTimeout::applyFromQuery($command, ['timeout' => '600'])->getTimeout(),
        );
        $this->assertSame($command, CommandTimeout::applyFromQuery($command, []));
        $this->assertSame($command, CommandTimeout::applyFromQuery($command, ['timeout' => 'abc']));
    }

    public function testApplyFromQueryLeavesForeignCommandsUntouched(): void
    {
        $command = new class implements CommandInterface {
            public static function isAvailable(): bool
            {
                return true;
            }

            public static function getTitle(): string
            {
                return 'x';
            }

            public static function getDescription(): string
            {
                return '';
            }

            public function run(): CommandResponse
            {
                return new CommandResponse(CommandResponse::STATUS_OK, null);
            }
        };

        $this->assertSame($command, CommandTimeout::applyFromQuery($command, ['timeout' => '5']));
    }

    /**
     * @param class-string<CommandInterface> $class
     */
    private function instantiate(string $class): CommandInterface
    {
        $resolver = $this->pathResolver();

        return $class === BashCommand::class ? new BashCommand($resolver, ['true']) : new $class($resolver);
    }

    private function pathResolver(): PathResolverInterface
    {
        $resolver = $this->createMock(PathResolverInterface::class);
        $resolver->method('getRootPath')->willReturn(sys_get_temp_dir());
        $resolver->method('getRuntimePath')->willReturn(sys_get_temp_dir());

        return $resolver;
    }
}
