<?php

declare(strict_types=1);

namespace Unit\Command;

use AppDevPanel\Cli\Command\InspectConfigCommand;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Symfony\Component\Console\Tester\CommandTester;

final class InspectConfigCommandTest extends TestCase
{
    public function testCommandCanBeInstantiated(): void
    {
        $container = $this->createMock(ContainerInterface::class);
        $command = new InspectConfigCommand($container);

        $this->assertSame('inspect:config', $command->getName());
    }

    public function testCommandHasOptions(): void
    {
        $container = $this->createMock(ContainerInterface::class);
        $command = new InspectConfigCommand($container);
        $definition = $command->getDefinition();

        $this->assertTrue($definition->hasOption('group'));
        $this->assertTrue($definition->hasOption('filter'));
        $this->assertTrue($definition->hasOption('json'));
        $this->assertTrue($definition->hasArgument('action'));
    }

    public function testDiWithoutConfig(): void
    {
        $container = $this->createMock(ContainerInterface::class);
        $container->method('has')->with('config')->willReturn(false);

        $tester = new CommandTester(new InspectConfigCommand($container));
        $tester->execute(['action' => 'di']);

        $this->assertSame(1, $tester->getStatusCode());
        $this->assertStringContainsString('Config inspection requires framework integration', $tester->getDisplay());
    }

    public function testDiWithConfig(): void
    {
        $configService = new class() {
            public function get(string $group): array
            {
                return ['App\\Service\\FooService' => ['class' => 'App\\Service\\FooService']];
            }
        };

        $container = $this->createMock(ContainerInterface::class);
        $container->method('has')->with('config')->willReturn(true);
        $container->method('get')->with('config')->willReturn($configService);

        $tester = new CommandTester(new InspectConfigCommand($container));
        $tester->execute(['action' => 'di']);

        $this->assertSame(0, $tester->getStatusCode());
        $display = $tester->getDisplay();
        $this->assertStringContainsString('DI Configuration', $display);
        $this->assertStringContainsString('FooService', $display);
    }

    public function testDiWithConfigJson(): void
    {
        $configService = new class() {
            public function get(string $group): array
            {
                return ['service' => 'value'];
            }
        };

        $container = $this->createMock(ContainerInterface::class);
        $container->method('has')->with('config')->willReturn(true);
        $container->method('get')->with('config')->willReturn($configService);

        $tester = new CommandTester(new InspectConfigCommand($container));
        $tester->execute(['action' => 'di', '--json' => true]);

        $this->assertSame(0, $tester->getStatusCode());
        $decoded = json_decode($tester->getDisplay(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertIsArray($decoded);
    }

    public function testDiWithCustomGroup(): void
    {
        $configService = new class() {
            public function get(string $group): array
            {
                return ['custom_key' => 'custom_value'];
            }
        };

        $container = $this->createMock(ContainerInterface::class);
        $container->method('has')->with('config')->willReturn(true);
        $container->method('get')->with('config')->willReturn($configService);

        $tester = new CommandTester(new InspectConfigCommand($container));
        $tester->execute(['action' => 'di', '--group' => 'services']);

        $this->assertSame(0, $tester->getStatusCode());
        $this->assertStringContainsString('DI Configuration: services', $tester->getDisplay());
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
        $this->assertStringContainsString('Application Parameters', $display);
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

    public function testPhpinfoJson(): void
    {
        $container = $this->createMock(ContainerInterface::class);

        $tester = new CommandTester(new InspectConfigCommand($container));
        $tester->execute(['action' => 'phpinfo', '--json' => true]);

        $this->assertSame(0, $tester->getStatusCode());
        $display = $tester->getDisplay();
        // Output is a JSON-encoded string of phpinfo() HTML
        $this->assertStringContainsString('PHP Version', $display);
        $this->assertStringStartsWith('"', trim($display));
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

    public function testClassesWithFilterNoResults(): void
    {
        $container = $this->createMock(ContainerInterface::class);

        $tester = new CommandTester(new InspectConfigCommand($container));
        $tester->execute(['action' => 'classes', '--filter' => 'ZZZ_NONEXISTENT_CLASS_ZZZ']);

        $this->assertSame(0, $tester->getStatusCode());
        $this->assertStringContainsString('No classes found', $tester->getDisplay());
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

    public function testEventsWithConfig(): void
    {
        $configService = new class() {
            public function get(string $group): array
            {
                return match ($group) {
                    'events' => ['App\\Event\\UserCreated' => [['App\\Listener\\SendWelcomeEmail', 'handle']]],
                    'events-web' => ['App\\Event\\RequestReceived' => [['App\\Listener\\LogRequest', 'handle']]],
                    default => [],
                };
            }
        };

        $container = $this->createMock(ContainerInterface::class);
        $container->method('has')->with('config')->willReturn(true);
        $container->method('get')->with('config')->willReturn($configService);

        $tester = new CommandTester(new InspectConfigCommand($container));
        $tester->execute(['action' => 'events']);

        $this->assertSame(0, $tester->getStatusCode());
        $display = $tester->getDisplay();
        $this->assertStringContainsString('Event Listeners', $display);
    }

    public function testEventsWithConfigJson(): void
    {
        $configService = new class() {
            public function get(string $group): array
            {
                return [];
            }
        };

        $container = $this->createMock(ContainerInterface::class);
        $container->method('has')->with('config')->willReturn(true);
        $container->method('get')->with('config')->willReturn($configService);

        $tester = new CommandTester(new InspectConfigCommand($container));
        $tester->execute(['action' => 'events', '--json' => true]);

        $this->assertSame(0, $tester->getStatusCode());
        $decoded = json_decode($tester->getDisplay(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertArrayHasKey('common', $decoded);
        $this->assertArrayHasKey('console', $decoded);
        $this->assertArrayHasKey('web', $decoded);
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
