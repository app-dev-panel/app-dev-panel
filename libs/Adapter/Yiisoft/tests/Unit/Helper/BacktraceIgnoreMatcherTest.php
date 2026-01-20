<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Debug\Tests\Unit\Helper;

use PHPUnit\Framework\TestCase;
use ReflectionClass;
use stdClass;
use Yiisoft\Yii\Debug\Helper\BacktraceIgnoreMatcher;

final class BacktraceIgnoreMatcherTest extends TestCase
{
    public function testClassIgnorance(): void
    {
        $backtrace = debug_backtrace();

        $this->assertFalse(BacktraceIgnoreMatcher::isIgnoredByClass($backtrace, [self::class]));
        $this->assertFalse(BacktraceIgnoreMatcher::isIgnoredByClass($backtrace, [stdClass::class]));

        $backtrace[3] = $backtrace[0];

        $this->assertTrue(BacktraceIgnoreMatcher::isIgnoredByClass($backtrace, [self::class]));
        $this->assertFalse(BacktraceIgnoreMatcher::isIgnoredByClass($backtrace, [stdClass::class]));
    }

    public function testFileIgnorance(): void
    {
        $backtrace = debug_backtrace();
        $reflection = new ReflectionClass(TestCase::class);
        $file = $reflection->getFileName();

        $this->assertFalse(BacktraceIgnoreMatcher::isIgnoredByFile($backtrace, [preg_quote($file)]));
        $this->assertFalse(BacktraceIgnoreMatcher::isIgnoredByFile($backtrace, [preg_quote(__FILE__)]));

        $backtrace[2] = $backtrace[0];

        $this->assertTrue(BacktraceIgnoreMatcher::isIgnoredByFile($backtrace, [preg_quote($file)]));
        $this->assertTrue(
            BacktraceIgnoreMatcher::isIgnoredByFile(
                $backtrace,
                [preg_quote(dirname($file) . DIRECTORY_SEPARATOR) . '*']
            )
        );
        $this->assertFalse(BacktraceIgnoreMatcher::isIgnoredByFile($backtrace, [preg_quote(__FILE__)]));
    }

    public function testStringMatches(): void
    {
        $this->assertTrue(
            BacktraceIgnoreMatcher::doesStringMatchPattern(
                'dev/123/456',
                ['dev/123/456']
            )
        );
        $this->assertTrue(
            BacktraceIgnoreMatcher::doesStringMatchPattern(
                'dev/123/456',
                ['456']
            )
        );
        $this->assertTrue(
            BacktraceIgnoreMatcher::doesStringMatchPattern(
                'dev/123/456',
                ['dev/.*/456']
            )
        );
        $this->assertTrue(
            BacktraceIgnoreMatcher::doesStringMatchPattern(
                'dev/123/456',
                ['dev*/456', 'dev/123/*']
            )
        );
    }

    public function testEmptyBacktrace(): void
    {
        $this->assertFalse(BacktraceIgnoreMatcher::doesStringMatchPattern('dev/123/456', []));
        $this->assertFalse(BacktraceIgnoreMatcher::isIgnoredByFile([], ['dev/123/456']));
        $this->assertFalse(BacktraceIgnoreMatcher::isIgnoredByClass([], ['dev/123/456']));
    }
}
