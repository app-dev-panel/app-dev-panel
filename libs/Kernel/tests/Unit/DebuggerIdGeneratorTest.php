<?php

declare(strict_types=1);

namespace AppDevPanel\Kernel\Tests\Unit;

use AppDevPanel\Kernel\DebuggerIdGenerator;
use PHPUnit\Framework\TestCase;

final class DebuggerIdGeneratorTest extends TestCase
{
    public function testGetIdReturnsString(): void
    {
        $generator = new DebuggerIdGenerator();
        $this->assertIsString($generator->getId());
        $this->assertNotEmpty($generator->getId());
    }

    public function testGetIdIsConsistent(): void
    {
        $generator = new DebuggerIdGenerator();
        $id = $generator->getId();

        $this->assertSame($id, $generator->getId());
    }

    public function testTwoInstancesHaveDifferentIds(): void
    {
        $generator1 = new DebuggerIdGenerator();
        $generator2 = new DebuggerIdGenerator();

        $this->assertNotSame($generator1->getId(), $generator2->getId());
    }

    public function testResetChangesId(): void
    {
        $generator = new DebuggerIdGenerator();
        $originalId = $generator->getId();

        $generator->reset();
        $newId = $generator->getId();

        $this->assertNotSame($originalId, $newId);
    }

    public function testIdContainsNoDots(): void
    {
        $generator = new DebuggerIdGenerator();
        $this->assertStringNotContainsString('.', $generator->getId());
    }
}
