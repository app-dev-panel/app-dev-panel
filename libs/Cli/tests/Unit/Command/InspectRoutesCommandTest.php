<?php

declare(strict_types=1);

namespace Unit\Command;

use AppDevPanel\Cli\Command\InspectRoutesCommand;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Tester\CommandTester;

final class InspectRoutesCommandTest extends TestCase
{
    public function testListRoutesWithoutFramework(): void
    {
        $tester = new CommandTester(new InspectRoutesCommand(null, null));
        $tester->execute(['action' => 'list']);

        $this->assertSame(1, $tester->getStatusCode());
        $this->assertStringContainsString('Route inspection requires framework integration', $tester->getDisplay());
    }

    public function testCheckRouteWithoutFramework(): void
    {
        $tester = new CommandTester(new InspectRoutesCommand(null, null));
        $tester->execute(['action' => 'check', 'path' => '/test']);

        $this->assertSame(1, $tester->getStatusCode());
        $this->assertStringContainsString('Route checking requires framework integration', $tester->getDisplay());
    }

    public function testCheckRouteRequiresPath(): void
    {
        $urlMatcher = $this->createStub(\stdClass::class);

        $tester = new CommandTester(new InspectRoutesCommand(null, $urlMatcher));
        $tester->execute(['action' => 'check']);

        $this->assertSame(1, $tester->getStatusCode());
        $this->assertStringContainsString('Path is required', $tester->getDisplay());
    }

    public function testUnknownAction(): void
    {
        $tester = new CommandTester(new InspectRoutesCommand());
        $tester->execute(['action' => 'invalid']);

        $this->assertSame(1, $tester->getStatusCode());
        $this->assertStringContainsString('Unknown action', $tester->getDisplay());
    }
}
