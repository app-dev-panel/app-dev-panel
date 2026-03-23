<?php

declare(strict_types=1);

namespace AppDevPanel\Kernel\Tests\Unit;

use AppDevPanel\Kernel\DumpContext;
use PHPUnit\Framework\TestCase;

final class DumpContextTest extends TestCase
{
    public function testDumpScalarValues(): void
    {
        $context = new DumpContext(objects: [], excludedClasses: []);

        $this->assertSame('hello', $context->dumpNestedInternal('hello', 3, 0, 0, false));
        $this->assertSame(42, $context->dumpNestedInternal(42, 3, 0, 0, false));
        $this->assertTrue($context->dumpNestedInternal(true, 3, 0, 0, false));
        $this->assertNull($context->dumpNestedInternal(null, 3, 0, 0, false));
    }

    public function testDumpArrayWithinDepth(): void
    {
        $context = new DumpContext(objects: [], excludedClasses: []);

        $result = $context->dumpNestedInternal(['a' => 1, 'b' => 2], 3, 0, 0, false);

        $this->assertSame(['a' => 1, 'b' => 2], $result);
    }

    public function testDumpArrayBeyondDepth(): void
    {
        $context = new DumpContext(objects: [], excludedClasses: []);

        $result = $context->dumpNestedInternal(['a' => 1, 'b' => 2], 1, 1, 0, false);

        $this->assertSame('array (2 items) [...]', $result);
    }

    public function testDumpArrayBeyondDepthSingleItem(): void
    {
        $context = new DumpContext(objects: [], excludedClasses: []);

        $result = $context->dumpNestedInternal(['key' => 'val'], 1, 1, 0, false);

        $this->assertSame('array (1 item) [...]', $result);
    }

    public function testDumpEmptyArrayBeyondDepth(): void
    {
        $context = new DumpContext(objects: [], excludedClasses: []);

        $result = $context->dumpNestedInternal([], 0, 0, 0, false);

        $this->assertSame([], $result);
    }

    public function testGetObjectDescription(): void
    {
        $context = new DumpContext(objects: [], excludedClasses: []);
        $obj = new \stdClass();

        $description = $context->getObjectDescription($obj);

        $this->assertSame('stdClass#' . spl_object_id($obj), $description);
    }

    public function testGetObjectDescriptionAnonymous(): void
    {
        $context = new DumpContext(objects: [], excludedClasses: []);
        $obj = new class {};

        $description = $context->getObjectDescription($obj);

        $this->assertStringStartsWith('class@anonymous#', $description);
    }

    public function testBuildObjectsCache(): void
    {
        $context = new DumpContext(objects: [], excludedClasses: []);
        $obj = new \stdClass();
        $obj->name = 'test';

        $context->buildObjectsCache($obj);

        $this->assertArrayHasKey('stdClass#' . spl_object_id($obj), $context->objects);
    }

    public function testBuildObjectsCacheWithExcludedClass(): void
    {
        $context = new DumpContext(objects: [], excludedClasses: [\stdClass::class => true]);
        $obj = new \stdClass();

        $context->buildObjectsCache($obj);

        $this->assertSame([], $context->objects);
    }

    public function testBuildObjectsCacheWithDepthLimit(): void
    {
        $inner = new \stdClass();
        $inner->value = 'deep';

        $outer = new \stdClass();
        $outer->child = $inner;

        $context = new DumpContext(objects: [], excludedClasses: []);
        $context->buildObjectsCache($outer, depth: 1);

        $this->assertCount(1, $context->objects);
        $this->assertArrayHasKey('stdClass#' . spl_object_id($outer), $context->objects);
    }

    public function testBuildObjectsCacheWithArrays(): void
    {
        $obj = new \stdClass();
        $context = new DumpContext(objects: [], excludedClasses: []);
        $context->buildObjectsCache([$obj, 'scalar']);

        $this->assertArrayHasKey('stdClass#' . spl_object_id($obj), $context->objects);
    }

    public function testDumpObjectExcluded(): void
    {
        $obj = new \stdClass();
        $context = new DumpContext(objects: [], excludedClasses: [\stdClass::class => true]);

        $result = $context->dumpNestedInternal($obj, 3, 0, 0, false);

        $this->assertStringContainsString('stdClass#', $result);
        $this->assertStringContainsString('(...)', $result);
    }

    public function testDumpObjectNotInCache(): void
    {
        $obj = new \stdClass();
        $context = new DumpContext(objects: [], excludedClasses: []);

        $result = $context->dumpNestedInternal($obj, 3, 0, 0, false);

        $this->assertStringContainsString('stdClass#', $result);
        $this->assertStringContainsString('(...)', $result);
    }

    public function testDumpObjectInCacheInline(): void
    {
        $obj = new \stdClass();
        $obj->name = 'test';
        $context = new DumpContext(objects: [], excludedClasses: []);
        $context->buildObjectsCache($obj);

        $result = $context->dumpNestedInternal($obj, 3, 0, 0, true);

        $this->assertIsArray($result);
        $this->assertSame('test', $result['public $name']);
    }

    public function testDumpObjectInCacheWrapped(): void
    {
        $obj = new \stdClass();
        $obj->value = 42;
        $context = new DumpContext(objects: [], excludedClasses: []);
        $context->buildObjectsCache($obj);

        $result = $context->dumpNestedInternal($obj, 3, 0, 0, false);

        $this->assertIsArray($result);
        $key = 'stdClass#' . spl_object_id($obj);
        $this->assertArrayHasKey($key, $result);
        $this->assertSame(42, $result[$key]['public $value']);
    }

    public function testDumpStatelessObject(): void
    {
        $obj = new class {};
        $context = new DumpContext(objects: [], excludedClasses: []);
        $context->buildObjectsCache($obj);

        $result = $context->dumpNestedInternal($obj, 3, 0, 0, true);

        $this->assertSame('{stateless object}', $result);
    }

    public function testDumpClosure(): void
    {
        $closure = static fn(): string => 'hello';
        $context = new DumpContext(objects: [], excludedClasses: []);

        $result = $context->dumpNestedInternal($closure, 3, 0, 0, true);

        $this->assertIsString($result);
    }

    public function testDumpObjectCollapsedBeyondLevel(): void
    {
        $obj = new \stdClass();
        $obj->x = 1;
        $description = 'stdClass#' . spl_object_id($obj);
        $context = new DumpContext(objects: [$description => $obj], excludedClasses: []);

        $result = $context->dumpNestedInternal($obj, 5, 2, 1, false);

        $this->assertSame('object@' . $description, $result);
    }
}
