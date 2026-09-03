<?php

declare(strict_types=1);

namespace AppDevPanel\Api\Tests\Unit\Security;

use AppDevPanel\Api\Security\ClassNameValidator;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class ClassNameValidatorTest extends TestCase
{
    /**
     * @return iterable<string, array{string}>
     */
    public static function validNames(): iterable
    {
        yield 'global' => ['stdClass'];
        yield 'namespaced' => ['AppDevPanel\\Api\\Security\\ClassNameValidator'];
        yield 'leading backslash' => ['\\AppDevPanel\\Api\\Security\\ClassNameValidator'];
        yield 'underscore' => ['_Foo\\Bar_1'];
    }

    #[DataProvider('validNames')]
    public function testValidNames(string $name): void
    {
        $this->assertTrue(ClassNameValidator::isValid($name));
    }

    /**
     * @return iterable<string, array{mixed}>
     */
    public static function invalidNames(): iterable
    {
        yield 'empty' => [''];
        yield 'path traversal' => ['../../etc/passwd'];
        yield 'slash' => ['Foo/Bar'];
        yield 'dot' => ['Foo.php'];
        yield 'leading digit' => ['1Foo'];
        yield 'space' => ['Foo Bar'];
        yield 'double backslash' => ['Foo\\\\Bar'];
        yield 'trailing backslash' => ['Foo\\'];
        yield 'null byte' => ["Foo\0Bar"];
        yield 'too long' => [str_repeat('A', 256)];
        yield 'not a string' => [42];
        yield 'null' => [null];
    }

    #[DataProvider('invalidNames')]
    public function testInvalidNames(mixed $name): void
    {
        $this->assertFalse(ClassNameValidator::isValid($name));
        $this->assertFalse(ClassNameValidator::classExists($name));
        $this->assertFalse(ClassNameValidator::isSubclassOf($name, \Throwable::class));
    }

    public function testClassExistsForLoadedClass(): void
    {
        $this->assertTrue(ClassNameValidator::classExists(self::class));
        $this->assertFalse(ClassNameValidator::classExists('AppDevPanel\\Api\\Tests\\Unit\\Security\\DoesNotExist'));
    }

    public function testIsSubclassOf(): void
    {
        $this->assertTrue(ClassNameValidator::isSubclassOf(\RuntimeException::class, \Throwable::class));
        $this->assertFalse(ClassNameValidator::isSubclassOf(\stdClass::class, \Throwable::class));
        // The interface name itself is not a subclass of itself.
        $this->assertFalse(ClassNameValidator::isSubclassOf(\Throwable::class, \Throwable::class));
    }
}
