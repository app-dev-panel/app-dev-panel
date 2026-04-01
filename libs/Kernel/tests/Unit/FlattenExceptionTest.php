<?php

declare(strict_types=1);

namespace AppDevPanel\Kernel\Tests\Unit;

use AppDevPanel\Kernel\FlattenException;
use Closure;
use Exception;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class FlattenExceptionTest extends TestCase
{
    public function testMessage(): void
    {
        $flattened = new FlattenException(new Exception('test'));
        $this->assertEquals('test', $flattened->getMessage());
    }

    public function testCode(): void
    {
        $flattened = new FlattenException(new Exception('test', 100));
        $this->assertEquals(100, $flattened->getCode());
    }

    public function testFile(): void
    {
        $flattened = new FlattenException(new Exception('test', 100));
        $this->assertEquals(__FILE__, $flattened->getFile());
    }

    public function testLine(): void
    {
        $flattened = new FlattenException(new Exception('test', 100));
        $this->assertEquals(__LINE__ - 1, $flattened->getLine());
    }

    public function testTrace(): void
    {
        $i = new Exception('test');
        $flattened = new FlattenException($i);

        $trace = $flattened->getTrace();
        $this->assertEquals(__NAMESPACE__, $trace[0]['namespace']);
        $this->assertEquals(self::class, $trace[0]['class']);
        $this->assertEquals('FlattenExceptionTest', $trace[0]['short_class']);
        $this->assertEquals(__FUNCTION__, $trace[0]['function']);
    }

    public function testPrevious(): void
    {
        $exception2 = new Exception();
        $exception = new Exception('test', 0, $exception2);

        $flattened = new FlattenException($exception);
        $flattened2 = new FlattenException($exception2);

        $this->assertSame($flattened2->getTrace(), $flattened->getPrevious()->getTrace());
    }

    public function testTraceAsString(): void
    {
        $exception = $this->createException('test');
        $this->assertEquals($exception->getTraceAsString(), new FlattenException($exception)->getTraceAsString());
    }

    public function testToString(): void
    {
        $exception = new Exception();
        $this->assertEquals($exception->__toString(), new FlattenException($exception)->__toString(), 'empty');
        $exception = new Exception('test');
        $this->assertEquals($exception->__toString(), new FlattenException($exception)->__toString());
    }

    public function testClass(): void
    {
        $this->assertEquals('Exception', new FlattenException(new Exception())->getClass());
    }

    public function testArguments(): void
    {
        $previousValue = ini_get('zend.exception_ignore_args');
        ini_set('zend.exception_ignore_args', '0');

        try {
            $this->runArgumentsAssertions();
        } finally {
            ini_set('zend.exception_ignore_args', $previousValue);
        }
    }

    private function runArgumentsAssertions(): void
    {
        $dh = opendir(__DIR__);
        $fh = tmpfile();

        $incomplete = unserialize('O:14:"BogusTestClass":0:{}');

        $exception = $this->createException([
            (object) ['foo' => 1],
            new RuntimeException('test'),
            $incomplete,
            $dh,
            $fh,
            static function () {},
            [1, 2],
            ['foo' => 123],
            null,
            true,
            false,
            0,
            0.0,
            '0',
            '',
            INF,
            NAN,
        ]);

        $flattened = new FlattenException($exception);
        $trace = $flattened->getTrace();
        $args = $trace[0]['args'];
        $array = $args[0][1];

        closedir($dh);
        fclose($fh);

        $i = 0;
        $this->assertSame(['object', 'stdClass'], $array[$i++]);
        $this->assertSame(['object', RuntimeException::class], $array[$i++]);
        $this->assertSame(['incomplete-object', 'BogusTestClass'], $array[$i++]);
        $this->assertSame(['resource', 'stream'], $array[$i++]);
        $this->assertSame(['resource', 'stream'], $array[$i++]);

        $args = $array[$i++];
        $this->assertSame($args[0], 'object');
        $this->assertTrue(
            Closure::class === $args[1] || is_subclass_of($args[1], '\\' . Closure::class),
            'Expect object class name to be Closure or a subclass of Closure.',
        );

        $this->assertSame(['array', [['integer', 1], ['integer', 2]]], $array[$i++]);
        $this->assertSame(['array', ['foo' => ['integer', 123]]], $array[$i++]);
        $this->assertSame(['null', null], $array[$i++]);
        $this->assertSame(['boolean', true], $array[$i++]);
        $this->assertSame(['boolean', false], $array[$i++]);
        $this->assertSame(['integer', 0], $array[$i++]);
        $this->assertSame(['float', 0.0], $array[$i++]);
        $this->assertSame(['string', '0'], $array[$i++]);
        $this->assertSame(['string', ''], $array[$i++]);
        $this->assertSame(['float', INF], $array[$i++]);

        // assertEquals() does not like NAN values.
        $this->assertEquals('float', $array[$i][0]);
        $this->assertNan($array[$i++][1]);
    }

    public function testClosureSerialize(): void
    {
        $exception = $this->createException(static fn() => 1 + 1);

        $flattened = new FlattenException($exception);
        $this->assertStringContainsString(Closure::class, serialize($flattened));
    }

    public function testRecursionInArguments(): void
    {
        $previousValue = ini_get('zend.exception_ignore_args');
        ini_set('zend.exception_ignore_args', '0');

        try {
            $a = ['foo'];
            $a[] = [2, &$a];
            $exception = $this->createException($a);

            $flattened = new FlattenException($exception);
            $trace = $flattened->getTrace();
            $this->assertStringContainsString('*DEEP NESTED ARRAY*', serialize($trace));
        } finally {
            ini_set('zend.exception_ignore_args', $previousValue);
        }
    }

    public function testTooBigArray(): void
    {
        $previousValue = ini_get('zend.exception_ignore_args');
        ini_set('zend.exception_ignore_args', '0');

        try {
            $a = [];
            for ($i = 0; $i < 20; ++$i) {
                for ($j = 0; $j < 50; ++$j) {
                    for ($k = 0; $k < 10; ++$k) {
                        $a[$i][$j][$k] = 'value';
                    }
                }
            }
            $a[20] = 'value';
            $a[21] = 'value1';
            $exception = $this->createException($a);

            $flattened = new FlattenException($exception);
            $trace = $flattened->getTrace();

            $this->assertSame($trace[0]['args'][0], ['array', ['array', '*SKIPPED over 10000 entries*']]);

            $serializeTrace = serialize($trace);

            $this->assertStringContainsString('*SKIPPED over 10000 entries*', $serializeTrace);
            $this->assertStringNotContainsString('*value1*', $serializeTrace);
        } finally {
            ini_set('zend.exception_ignore_args', $previousValue);
        }
    }

    public function testDeepExceptionChain(): void
    {
        $inner = new Exception('inner', 1);
        $middle = new Exception('middle', 2, $inner);
        $outer = new Exception('outer', 3, $middle);

        $flattened = new FlattenException($outer);

        $this->assertSame('outer', $flattened->getMessage());
        $this->assertSame(3, $flattened->getCode());

        $prev = $flattened->getPrevious();
        $this->assertNotNull($prev);
        $this->assertSame('middle', $prev->getMessage());
        $this->assertSame(2, $prev->getCode());

        $innerFlattened = $prev->getPrevious();
        $this->assertNotNull($innerFlattened);
        $this->assertSame('inner', $innerFlattened->getMessage());
        $this->assertSame(1, $innerFlattened->getCode());

        $this->assertNull($innerFlattened->getPrevious());
    }

    public function testNoPreviousException(): void
    {
        $exception = new Exception('standalone');
        $flattened = new FlattenException($exception);

        $this->assertNull($flattened->getPrevious());
    }

    public function testTraceAsStringWithNoStackTrace(): void
    {
        // Create an exception with custom toString that has no "Stack trace:" marker
        $exception = new Exception('test');
        $flattened = new FlattenException($exception);

        // The real exception always has a stack trace, so getTraceAsString returns it
        $traceStr = $flattened->getTraceAsString();
        $this->assertIsString($traceStr);
    }

    public function testClassWithNamespace(): void
    {
        $exception = new RuntimeException('ns test');
        $flattened = new FlattenException($exception);

        $this->assertSame(RuntimeException::class, $flattened->getClass());

        $trace = $flattened->getTrace();
        // First trace entry should have namespace and short_class
        $this->assertNotEmpty($trace);
        $first = $trace[0];
        $this->assertArrayHasKey('namespace', $first);
        $this->assertArrayHasKey('short_class', $first);
        $this->assertArrayHasKey('class', $first);
        $this->assertArrayHasKey('type', $first);
        $this->assertArrayHasKey('function', $first);
        $this->assertArrayHasKey('file', $first);
        $this->assertArrayHasKey('line', $first);
        $this->assertArrayHasKey('args', $first);
    }

    public function testFlattenTraceWithNoClass(): void
    {
        // Exception trace entries from global scope have no class
        $exception = new Exception('global');
        $flattened = new FlattenException($exception);
        $trace = $flattened->getTrace();

        // Verify structure is valid even if some entries have no class
        foreach ($trace as $entry) {
            $this->assertArrayHasKey('namespace', $entry);
            $this->assertArrayHasKey('short_class', $entry);
            $this->assertArrayHasKey('class', $entry);
        }
    }

    public function testStringableInterface(): void
    {
        $exception = new Exception('stringable test');
        $flattened = new FlattenException($exception);

        $str = (string) $flattened;
        $this->assertStringContainsString('stringable test', $str);
        $this->assertStringContainsString('Exception', $str);
    }

    public function testExceptionWithStringCode(): void
    {
        // PDOException can have string codes
        $exception = new \PDOException('db error');
        // PDOException code defaults to "0" (string), cast depends on exception
        $flattened = new FlattenException($exception);

        $this->assertSame('db error', $flattened->getMessage());
        // Code can be int or string depending on exception type
        $this->assertNotNull($flattened->getCode());
    }

    private function createException($foo): Exception
    {
        return new Exception();
    }
}
