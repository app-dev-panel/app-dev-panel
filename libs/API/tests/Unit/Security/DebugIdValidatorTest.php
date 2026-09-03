<?php

declare(strict_types=1);

namespace AppDevPanel\Api\Tests\Unit\Security;

use AppDevPanel\Api\Debug\Exception\BadRequestException;
use AppDevPanel\Api\Security\DebugIdValidator;
use AppDevPanel\Kernel\DebuggerIdGenerator;
use AppDevPanel\Kernel\Storage\StorageIdValidator;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class DebugIdValidatorTest extends TestCase
{
    public function testKernelGeneratedIdIsValid(): void
    {
        $id = new DebuggerIdGenerator()->getId();

        $this->assertTrue(DebugIdValidator::isValid($id));
        $this->assertSame($id, DebugIdValidator::assertValid($id));
    }

    public function testDelegatesToKernelStorageIdValidator(): void
    {
        $this->assertSame(StorageIdValidator::PATTERN, DebugIdValidator::PATTERN);
        $this->assertSame(StorageIdValidator::isValid('ok-id'), DebugIdValidator::isValid('ok-id'));
        $this->assertSame(StorageIdValidator::isValid('../etc'), DebugIdValidator::isValid('../etc'));
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function validIds(): iterable
    {
        yield 'hex' => ['68b8f1a2c3d4e12345678901'];
        yield 'dashes and underscores' => ['external-123_abc'];
        yield 'single char' => ['a'];
        yield 'max length' => [str_repeat('x', 64)];
    }

    #[DataProvider('validIds')]
    public function testValidIds(string $id): void
    {
        $this->assertTrue(DebugIdValidator::isValid($id));
    }

    /**
     * @return iterable<string, array{mixed}>
     */
    public static function invalidIds(): iterable
    {
        yield 'empty' => [''];
        yield 'traversal' => ['../../etc/passwd'];
        yield 'slash' => ['2026-01-01/abc'];
        yield 'backslash' => ['abc\\def'];
        yield 'dot' => ['abc.json'];
        yield 'glob' => ['*'];
        yield 'space' => ['a b'];
        yield 'null byte' => ["abc\0def"];
        yield 'too long' => [str_repeat('x', 65)];
        yield 'not a string' => [123];
        yield 'null' => [null];
        yield 'array' => [['a']];
    }

    #[DataProvider('invalidIds')]
    public function testInvalidIds(mixed $id): void
    {
        $this->assertFalse(DebugIdValidator::isValid($id));

        $this->expectException(BadRequestException::class);
        $this->expectExceptionMessage('debugId');
        DebugIdValidator::assertValid($id, 'debugId');
    }
}
