<?php

declare(strict_types=1);

namespace Unit\Command;

use AppDevPanel\Cli\Command\InspectConfigCommand;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Symfony\Component\Console\Tester\CommandTester;

final class InspectConfigCommandTest extends TestCase
{
    public function testDiWithoutConfig(): void
    {
        $container = $this->createMock(ContainerInterface::class);
        $container->method('has')->with('config')->willReturn(false);

        $tester = new CommandTester(new InspectConfigCommand($container));
        $tester->execute(['action' => 'di']);

        $this->assertSame(1, $tester->getStatusCode());
        $this->assertStringContainsString('Config inspection requires framework integration', $tester->getDisplay());
    }

    public function testParamsEmpty(): void
    {
        $container = $this->createMock(ContainerInterface::class);

        $tester = new CommandTester(new InspectConfigCommand($container, []));
        $tester->execute(['action' => 'params']);

        $this->assertSame(0, $tester->getStatusCode());
        $this->assertStringContainsString('No parameters found', $tester->getDisplay());
    }

    public function testParams(): void
    {
        $container = $this->createMock(ContainerInterface::class);
        $params = ['app.name' => 'Test App', 'app.debug' => true];

        $tester = new CommandTester(new InspectConfigCommand($container, $params));
        $tester->execute(['action' => 'params']);

        $this->assertSame(0, $tester->getStatusCode());
        $display = $tester->getDisplay();
        $this->assertStringContainsString('app.debug', $display);
        $this->assertStringContainsString('app.name', $display);
    }

    public function testParamsJson(): void
    {
        $container = $this->createMock(ContainerInterface::class);
        $params = ['key' => 'value'];

        $tester = new CommandTester(new InspectConfigCommand($container, $params));
        $tester->execute(['action' => 'params', '--json' => true]);

        $this->assertSame(0, $tester->getStatusCode());
        $decoded = json_decode($tester->getDisplay(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertSame('value', $decoded['key']);
    }

    public function testPhpinfo(): void
    {
        $container = $this->createMock(ContainerInterface::class);

        $tester = new CommandTester(new InspectConfigCommand($container));
        $tester->execute(['action' => 'phpinfo']);

        $this->assertSame(0, $tester->getStatusCode());
        $this->assertStringContainsString('PHP Version', $tester->getDisplay());
    }

    public function testClasses(): void
    {
        $container = $this->createMock(ContainerInterface::class);

        $tester = new CommandTester(new InspectConfigCommand($container));
        $tester->execute(['action' => 'classes']);

        $this->assertSame(0, $tester->getStatusCode());
        $this->assertStringContainsString('Declared Classes', $tester->getDisplay());
    }

    public function testClassesWithFilter(): void
    {
        $container = $this->createMock(ContainerInterface::class);

        $tester = new CommandTester(new InspectConfigCommand($container));
        $tester->execute(['action' => 'classes', '--filter' => 'PHPUnit']);

        $this->assertSame(0, $tester->getStatusCode());
    }

    public function testClassesJson(): void
    {
        $container = $this->createMock(ContainerInterface::class);

        $tester = new CommandTester(new InspectConfigCommand($container));
        $tester->execute(['action' => 'classes', '--json' => true]);

        $this->assertSame(0, $tester->getStatusCode());
        $decoded = json_decode($tester->getDisplay(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertIsArray($decoded);
    }

    public function testEventsWithoutConfig(): void
    {
        $container = $this->createMock(ContainerInterface::class);
        $container->method('has')->with('config')->willReturn(false);

        $tester = new CommandTester(new InspectConfigCommand($container));
        $tester->execute(['action' => 'events']);

        $this->assertSame(1, $tester->getStatusCode());
        $this->assertStringContainsString(
            'Event listener inspection requires framework integration',
            $tester->getDisplay(),
        );
    }

    public function testUnknownAction(): void
    {
        $container = $this->createMock(ContainerInterface::class);

        $tester = new CommandTester(new InspectConfigCommand($container));
        $tester->execute(['action' => 'invalid']);

        $this->assertSame(1, $tester->getStatusCode());
        $this->assertStringContainsString('Unknown action', $tester->getDisplay());
    }
}
