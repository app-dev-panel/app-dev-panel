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

    public function testDumpStreamResource(): void
    {
        $context = new DumpContext(objects: [], excludedClasses: []);
        $resource = fopen('php://memory', 'r');

        $result = $context->dumpNestedInternal($resource, 3, 0, 0, false);

        $this->assertIsArray($result);
        // stream_get_meta_data returns an array with stream_type, uri, etc.
        $this->assertArrayHasKey('stream_type', $result);
        fclose($resource);
    }

    public function testDumpClosedResource(): void
    {
        $context = new DumpContext(objects: [], excludedClasses: []);
        $resource = fopen('php://memory', 'r');
        fclose($resource);

        $result = $context->dumpNestedInternal($resource, 3, 0, 0, false);

        $this->assertSame('{closed resource}', $result);
    }

    public function testDumpClosureWrapped(): void
    {
        $closure = static fn(int $x): int => $x * 2;
        $context = new DumpContext(objects: [], excludedClasses: []);

        $result = $context->dumpNestedInternal($closure, 3, 0, 0, false);

        $this->assertIsArray($result);
        $keys = array_keys($result);
        $this->assertStringContainsString('Closure', $keys[0]);
    }

    public function testDumpObjectWithDebugInfo(): void
    {
        $obj = new class {
            private string $secret = 'hidden';
            public string $visible = 'shown';

            public function __debugInfo(): array
            {
                return ['visible' => $this->visible, 'computed' => 42];
            }
        };

        $context = new DumpContext(objects: [], excludedClasses: []);
        $context->buildObjectsCache($obj);

        $result = $context->dumpNestedInternal($obj, 5, 0, 0, true);

        $this->assertIsArray($result);
        $this->assertSame('shown', $result['public $visible']);
        $this->assertSame(42, $result['public $computed']);
    }

    public function testDumpStatelessObjectWrapped(): void
    {
        $obj = new class {};
        $context = new DumpContext(objects: [], excludedClasses: []);
        $context->buildObjectsCache($obj);

        $result = $context->dumpNestedInternal($obj, 3, 0, 0, false);

        $this->assertIsArray($result);
        $key = array_key_first($result);
        $this->assertSame('{stateless object}', $result[$key]);
    }

    public function testBuildObjectsCacheSkipsDuplicate(): void
    {
        $obj = new \stdClass();
        $obj->name = 'test';

        $context = new DumpContext(objects: [], excludedClasses: []);
        $context->buildObjectsCache($obj);
        $count1 = count($context->objects);

        // Building cache again with same object should not add a duplicate
        $context->buildObjectsCache($obj);
        $this->assertCount($count1, $context->objects);
    }

    public function testBuildObjectsCacheWithNestedArrayOfObjects(): void
    {
        $inner1 = new \stdClass();
        $inner1->id = 1;
        $inner2 = new \stdClass();
        $inner2->id = 2;

        $context = new DumpContext(objects: [], excludedClasses: []);
        $context->buildObjectsCache([[$inner1], [$inner2]]);

        $this->assertCount(2, $context->objects);
    }

    public function testDumpArrayWithNullByteKeys(): void
    {
        $context = new DumpContext(objects: [], excludedClasses: []);

        // Simulates internal PHP property representation
        $data = ["\0Foo\0bar" => 'value'];
        $result = $context->dumpNestedInternal($data, 3, 0, 0, false);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('Foo::bar', $result);
    }

    public function testDumpNestedObjectsWithDepthLimit(): void
    {
        $inner = new \stdClass();
        $inner->value = 'deep';

        $outer = new \stdClass();
        $outer->child = $inner;

        $context = new DumpContext(objects: [], excludedClasses: []);
        $context->buildObjectsCache($outer, depth: 5);

        // Dump outer at depth 1 - inner should show as (...)
        $result = $context->dumpNestedInternal($outer, 1, 0, 0, true);

        // At depth limit, the result should be truncated
        $this->assertIsArray($result);
    }

    public function testGetObjectDescriptionForRegularClass(): void
    {
        $context = new DumpContext(objects: [], excludedClasses: []);
        $exception = new \RuntimeException('test');

        $description = $context->getObjectDescription($exception);

        $this->assertStringStartsWith('RuntimeException#', $description);
    }

    public function testDumpObjectWithPrivateProperty(): void
    {
        $obj = new class {
            private string $secret = 'hidden';
        };

        $context = new DumpContext(objects: [], excludedClasses: []);
        $context->buildObjectsCache($obj);

        $result = $context->dumpNestedInternal($obj, 5, 0, 0, true);

        $this->assertIsArray($result);
        // Private properties show as 'private $propertyName' after normalizeProperty
        $found = false;
        foreach ($result as $key => $value) {
            if ($value === 'hidden' && str_contains($key, 'secret')) {
                $found = true;
                break;
            }
        }
        $this->assertTrue($found, 'Expected private property with value "hidden" to be present');
    }

    public function testDumpObjectWithProtectedProperty(): void
    {
        $obj = new class {
            protected int $value = 42;
        };

        $context = new DumpContext(objects: [], excludedClasses: []);
        $context->buildObjectsCache($obj);

        $result = $context->dumpNestedInternal($obj, 5, 0, 0, true);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('protected $value', $result);
        $this->assertSame(42, $result['protected $value']);
    }

    public function testDumpObjectWithMixedVisibility(): void
    {
        $obj = new class {
            public string $pub = 'a';
            protected string $prot = 'b';
            private string $priv = 'c';
        };

        $context = new DumpContext(objects: [], excludedClasses: []);
        $context->buildObjectsCache($obj);

        $result = $context->dumpNestedInternal($obj, 5, 0, 0, true);

        $this->assertIsArray($result);
        $this->assertSame('a', $result['public $pub']);
        $this->assertSame('b', $result['protected $prot']);

        // Private property value 'c' should exist somewhere in the result
        $this->assertContains('c', $result);
    }

    public function testDumpNestedObjectInArray(): void
    {
        $inner = new \stdClass();
        $inner->x = 1;

        $context = new DumpContext(objects: [], excludedClasses: []);
        $context->buildObjectsCache($inner);

        $result = $context->dumpNestedInternal(['nested' => $inner], 5, 0, 0, false);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('nested', $result);
    }

    public function testBuildObjectsCacheSkipsExcludedNestedObject(): void
    {
        $inner = new \stdClass();
        $inner->value = 'test';

        $context = new DumpContext(objects: [], excludedClasses: [\stdClass::class => true]);

        // Build cache with an array containing an excluded object
        $context->buildObjectsCache([$inner]);

        // Should not have cached the excluded class
        $this->assertEmpty($context->objects);
    }

    public function testBuildObjectsCacheNestedObjectsInObject(): void
    {
        $child = new \stdClass();
        $child->name = 'child';

        $parent = new \stdClass();
        $parent->child = $child;

        $context = new DumpContext(objects: [], excludedClasses: []);
        $context->buildObjectsCache($parent);

        // Both parent and child should be cached
        $this->assertCount(2, $context->objects);
    }
}
