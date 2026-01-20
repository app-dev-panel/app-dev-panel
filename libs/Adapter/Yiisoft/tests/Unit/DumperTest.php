<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Debug\Tests\Unit;

use DateTimeZone;
use PHPUnit\Framework\TestCase;
use stdClass;
use Yiisoft\Yii\Debug as D;
use Yiisoft\Yii\Debug\Dumper;
use Yiisoft\Yii\Debug\Tests\Support\Stub\ThreeProperties;

use function socket_create;

use const AF_INET;
use const SOCK_STREAM;
use const SOL_TCP;

final class DumperTest extends TestCase
{
    public function testAsJsonObjectsMapLevelOne(): void
    {
        $object = new stdClass();
        $object->var = 'test';
        $objectId = spl_object_id($object);

        $this->assertSame(
            <<<JSON
            {
                "stdClass#$objectId": {
                    "public \$var": "test"
                }
            }
            JSON,
            Dumper::create($object)->asJsonObjectsMap(1, true)
        );
    }

    public function testAsJsonObjectsMapNestedObject(): void
    {
        $nested2 = new stdClass();
        $nested2->name = 'nested2';
        $nested2Id = spl_object_id($nested2);

        $nested1 = new stdClass();
        $nested1->name = 'nested1';
        $nested1->var = $nested2;
        $nested1Id = spl_object_id($nested1);

        $object = new stdClass();
        $object->name = 'root';
        $object->var = $nested1;
        $objectId = spl_object_id($object);

        $this->assertSame(
            <<<JSON
            {
                "stdClass#$objectId": {
                    "public \$name": "root",
                    "public \$var": "object@stdClass#$nested1Id"
                },
                "stdClass#$nested1Id": {
                    "public \$name": "nested1",
                    "public \$var": "object@stdClass#$nested2Id"
                },
                "stdClass#$nested2Id": {
                    "public \$name": "nested2"
                }
            }
            JSON,
            Dumper::create($object)->asJsonObjectsMap(1, true)
        );
    }

    public function testAsJsonObjectsMapArrayWithObject(): void
    {
        $nested2 = new stdClass();
        $nested2->name = 'nested2';
        $nested2Id = spl_object_id($nested2);

        $nested1 = new stdClass();
        $nested1->name = 'nested1';
        $nested1->var = [$nested2];
        $nested1Id = spl_object_id($nested1);

        $object = new stdClass();
        $object->name = 'root';
        $object->var = $nested1;
        $objectId = spl_object_id($object);

        $this->assertSame(
            <<<JSON
            {
                "stdClass#$objectId": {
                    "public \$name": "root",
                    "public \$var": "object@stdClass#$nested1Id"
                },
                "stdClass#$nested1Id": {
                    "public \$name": "nested1",
                    "public \$var": "array (1 item) [...]"
                },
                "stdClass#$nested2Id": {
                    "public \$name": "nested2"
                }
            }
            JSON,
            Dumper::create($object)->asJsonObjectsMap(0, true)
        );
    }

    /**
     * @dataProvider loopAsJsonObjectMapDataProvider
     */
    public function testLoopAsJsonObjectsMap(mixed $var, int $depth, $expectedResult): void
    {
        $exportResult = Dumper::create($var)->asJsonObjectsMap($depth, true);
        $this->assertEquals($expectedResult, $exportResult);
    }

    public static function loopAsJsonObjectMapDataProvider(): iterable
    {
        // parent->child->parent structure
        $nested1 = new stdClass();
        $nested1->id = 'nested1';
        $nested2 = new stdClass();
        $nested2->id = 'nested2';
        $nested2->nested1 = $nested1;
        $nested1->nested2 = $nested2;

        $nested1Id = spl_object_id($nested1);
        $nested2Id = spl_object_id($nested2);

        // 5 is a min level to reproduce buggy dumping of parent->child->parent structure
        [$object1, $ids1] = self::getNested(5, $nested1);
        yield 'nested loop - object' => [
            $object1,
            5,
            <<<S
            {
                "stdClass#$ids1[0]": {
                    "public \$id": "lvl0",
                    "public \$lvl1": "object@stdClass#$ids1[1]"
                },
                "stdClass#$ids1[1]": {
                    "public \$id": "lvl1",
                    "public \$lvl2": "object@stdClass#$ids1[2]"
                },
                "stdClass#$ids1[2]": {
                    "public \$id": "lvl2",
                    "public \$lvl3": "object@stdClass#$ids1[3]"
                },
                "stdClass#$ids1[3]": {
                    "public \$id": "lvl3",
                    "public \$lvl4": "object@stdClass#$ids1[4]"
                },
                "stdClass#$ids1[4]": {
                    "public \$id": "lvl4",
                    "public \$lvl5": "object@stdClass#$nested1Id"
                },
                "stdClass#$nested1Id": {
                    "public \$id": "nested1",
                    "public \$nested2": "object@stdClass#$nested2Id"
                },
                "stdClass#$nested2Id": {
                    "public \$id": "nested2",
                    "public \$nested1": "object@stdClass#$nested1Id"
                }
            }
            S,
        ];

        // array loop must be 1 level deeper to parse loop objects
        [$object2, $ids2] = self::getNested(6, [$nested1, $nested2]);
        yield 'nested loop - array' => [
            $object2,
            6,
            <<<S
            {
                "stdClass#$ids2[0]": {
                    "public \$id": "lvl0",
                    "public \$lvl1": "object@stdClass#$ids2[1]"
                },
                "stdClass#$ids2[1]": {
                    "public \$id": "lvl1",
                    "public \$lvl2": "object@stdClass#$ids2[2]"
                },
                "stdClass#$ids2[2]": {
                    "public \$id": "lvl2",
                    "public \$lvl3": "object@stdClass#$ids2[3]"
                },
                "stdClass#$ids2[3]": {
                    "public \$id": "lvl3",
                    "public \$lvl4": "object@stdClass#$ids2[4]"
                },
                "stdClass#$ids2[4]": {
                    "public \$id": "lvl4",
                    "public \$lvl5": "object@stdClass#$ids2[5]"
                },
                "stdClass#$ids2[5]": {
                    "public \$id": "lvl5",
                    "public \$lvl6": [
                        "object@stdClass#$nested1Id",
                        "object@stdClass#$nested2Id"
                    ]
                },
                "stdClass#$nested1Id": {
                    "public \$id": "nested1",
                    "public \$nested2": "object@stdClass#$nested2Id"
                },
                "stdClass#$nested2Id": {
                    "public \$id": "nested2",
                    "public \$nested1": "object@stdClass#$nested1Id"
                }
            }
            S,
        ];

        // nested loop to inner array
        $object3 = new stdClass();
        $object3->id = 'lvl0';
        $object3->lv11 = [
            'id' => 'lvl1',
            'loop' => $nested1,
        ];
        $object3Id = spl_object_id($object3);

        yield 'nested loop to object->array' => [
            $object3,
            3,
            <<<S
            {
                "stdClass#$object3Id": {
                    "public \$id": "lvl0",
                    "public \$lv11": {
                        "id": "lvl1",
                        "loop": "object@stdClass#$nested1Id"
                    }
                },
                "stdClass#$nested1Id": {
                    "public \$id": "nested1",
                    "public \$nested2": "object@stdClass#$nested2Id"
                },
                "stdClass#$nested2Id": {
                    "public \$id": "nested2",
                    "public \$nested1": "object@stdClass#$nested1Id"
                }
            }
            S,
        ];
    }

    private static function getNested(int $depth, mixed $data): array
    {
        $objectIds = [];
        $head = $lvl = new stdClass();
        $objectIds[] = spl_object_id($head);
        $lvl->id = 'lvl0';

        for ($i = 1; $i < $depth; $i++) {
            $nested = new stdClass();
            $nested->id = 'lvl' . $i;
            $lvl->{'lvl' . $i} = $nested;
            $lvl = $nested;
            $objectIds[] = spl_object_id($nested);
        }
        $lvl->{'lvl' . $i} = $data;

        return [$head, $objectIds];
    }

    public function testObjectExpanding(): void
    {
        $var = $this->createNested(10, [[[[[[[[['key' => 'end']]]]]]]]]);

        $lvl1Id = spl_object_id($var);
        $lvl2Id = spl_object_id($var->prop1);
        $lvl3Id = spl_object_id($var->prop1->prop1);
        $lvl4Id = spl_object_id($var->prop1->prop1->prop1);
        $lvl5Id = spl_object_id($var->prop1->prop1->prop1->prop1);
        $lvl6Id = spl_object_id($var->prop1->prop2->prop1->prop1->prop1);
        $lvl7Id = spl_object_id($var->prop1->prop2->prop1->prop1->prop1->prop1);
        $lvl8Id = spl_object_id($var->prop1->prop2->prop1->prop1->prop1->prop1->prop1);
        $lvl9Id = spl_object_id($var->prop1->prop2->prop1->prop1->prop1->prop1->prop1->prop1);
        $lvl10Id = spl_object_id($var->prop1->prop2->prop1->prop1->prop1->prop1->prop1->prop1->prop1);

        $expectedResult = <<<JSON
        {
            "stdClass#$lvl1Id": {
                "public \$id": "lvl1",
                "public \$prop1": "object@stdClass#$lvl2Id",
                "public \$prop2": "object@stdClass#$lvl2Id"
            },
            "stdClass#$lvl2Id": {
                "public \$id": "lvl2",
                "public \$prop1": "object@stdClass#$lvl3Id",
                "public \$prop2": "object@stdClass#$lvl3Id"
            },
            "stdClass#$lvl3Id": {
                "public \$id": "lvl3",
                "public \$prop1": "object@stdClass#$lvl4Id",
                "public \$prop2": "object@stdClass#$lvl4Id"
            },
            "stdClass#$lvl4Id": {
                "public \$id": "lvl4",
                "public \$prop1": "object@stdClass#$lvl5Id",
                "public \$prop2": "object@stdClass#$lvl5Id"
            },
            "stdClass#$lvl5Id": {
                "public \$id": "lvl5",
                "public \$prop1": "object@stdClass#$lvl6Id",
                "public \$prop2": "object@stdClass#$lvl6Id"
            },
            "stdClass#$lvl6Id": {
                "public \$id": "lvl6",
                "public \$prop1": "object@stdClass#$lvl7Id",
                "public \$prop2": "object@stdClass#$lvl7Id"
            },
            "stdClass#$lvl7Id": {
                "public \$id": "lvl7",
                "public \$prop1": "object@stdClass#$lvl8Id",
                "public \$prop2": "object@stdClass#$lvl8Id"
            },
            "stdClass#$lvl8Id": {
                "public \$id": "lvl8",
                "public \$prop1": "object@stdClass#$lvl9Id",
                "public \$prop2": "object@stdClass#$lvl9Id"
            },
            "stdClass#$lvl9Id": {
                "public \$id": "lvl9",
                "public \$prop1": "object@stdClass#$lvl10Id",
                "public \$prop2": "object@stdClass#$lvl10Id"
            },
            "stdClass#$lvl10Id": {
                "public \$id": "lvl10",
                "public \$loop": [
                    [
                        "array (1 item) [...]"
                    ]
                ],
                "public \$head": "object@stdClass#$lvl1Id"
            }
        }
        JSON;

        $actualResult = Dumper::create($var)->asJsonObjectsMap(2, true);

        $this->assertEquals($expectedResult, $actualResult);
    }

    private function createNested(int $depth, mixed $data): object
    {
        $head = $lvl = new stdClass();
        $lvl->id = 'lvl1';

        for ($i = 2; $i <= $depth; $i++) {
            $nested = new stdClass();
            $nested->id = 'lvl' . $i;
            $lvl->prop1 = $nested;
            $lvl->prop2 = $nested;
            $lvl = $nested;
        }
        $lvl->loop = $data;
        $lvl->head = $head;

        return $head;
    }

    /**
     * @dataProvider asJsonObjectMapDataProvider
     */
    public function testAsJsonObjectsMap(mixed $var, $expectedResult): void
    {
        $exportResult = Dumper::create($var)->asJsonObjectsMap();
        $this->assertEquals($expectedResult, $exportResult);
    }

    public static function asJsonObjectMapDataProvider(): iterable
    {
        $user = new stdClass();
        $user->id = 1;
        $objectId = spl_object_id($user);

        yield 'flat std class' => [
            $user,
            <<<S
            {"stdClass#{$objectId}":{"public \$id":1}}
            S,
        ];

        $decoratedUser = clone $user;
        $decoratedUser->name = 'Name';
        $decoratedUser->originalUser = $user;
        $decoratedObjectId = spl_object_id($decoratedUser);

        yield 'nested std class' => [
            $decoratedUser,
            <<<S
            {"stdClass#{$decoratedObjectId}":{"public \$id":1,"public \$name":"Name","public \$originalUser":"object@stdClass#{$objectId}"},"stdClass#{$objectId}":{"public \$id":1}}
            S,
        ];

        $closureInsideObject = new stdClass();
        $closureObject = fn () => true;
        $closureObjectId = spl_object_id($closureObject);
        $closureInsideObject->closure = $closureObject;
        $closureInsideObjectId = spl_object_id($closureInsideObject);

        yield 'closure inside std class' => [
            $closureInsideObject,
            <<<S
            {"stdClass#{$closureInsideObjectId}":{"public \$closure":"fn () => true"},"Closure#{$closureObjectId}":"fn () => true"}
            S,
        ];
    }

    /**
     * @dataProvider jsonDataProvider()
     */
    public function testAsJson($variable, string $result): void
    {
        $output = Dumper::create($variable)->asJson();
        $this->assertEqualsWithoutLE($result, $output);
    }

    public function testCacheDoesNotCoversObjectOutOfDumpDepth(): void
    {
        $object1 = new stdClass();
        $object1Id = spl_object_id($object1);
        $object2 = new stdClass();
        $object2Id = spl_object_id($object2);

        $variable = [$object1, [[$object2]]];
        $expectedResult = sprintf('["object@stdClass#%d",["array (1 item) [...]"]]', $object1Id);

        $dumper = Dumper::create($variable);
        $actualResult = $dumper->asJson(2);
        $this->assertEqualsWithoutLE($expectedResult, $actualResult);

        $map = $dumper->asJsonObjectsMap(2);
        $this->assertEqualsWithoutLE(
            <<<S
            {"stdClass#$object1Id":"{stateless object}","stdClass#$object2Id":"{stateless object}"}
            S,
            $map,
        );
    }

    public function testDepthLimitInObjectMap(): void
    {
        $variable = [1, []];
        $expectedResult = '"array (2 items) [...]"';

        $dumper = Dumper::create($variable);
        $actualResult = $dumper->asJson(0);
        $this->assertEqualsWithoutLE($expectedResult, $actualResult);

        $map = $dumper->asJsonObjectsMap(0);
        $this->assertEqualsWithoutLE('[]', $map);
    }

    public function testObjectProvidesDebugInfoMethod(): void
    {
        $variable = new class () {
            public function __debugInfo(): array
            {
                return ['test' => 'ok'];
            }
        };
        $expectedResult = sprintf(
            '{"class@anonymous#%d":{"public $test":"ok"}}',
            spl_object_id($variable),
        );

        $dumper = Dumper::create($variable);
        $actualResult = $dumper->asJson(2);
        $this->assertEqualsWithoutLE($expectedResult, $actualResult);

        $map = $dumper->asJsonObjectsMap(2);
        $this->assertEqualsWithoutLE($expectedResult, $map);
    }

    public function testStatelessObjectInlined(): void
    {
        $statelessObject = new stdClass();
        $statelessObjectId = spl_object_id($statelessObject);

        $statefulObject = new stdClass();
        $statefulObject->id = 1;
        $statefulObjectId = spl_object_id($statefulObject);

        $variable = [$statelessObject, [$statefulObject]];
        $expectedResult = sprintf(
            '["object@stdClass#%d",["stdClass#%d (...)"]]',
            $statelessObjectId,
            $statefulObjectId
        );

        $dumper = Dumper::create($variable);
        $actualResult = $dumper->asJson(2);
        $this->assertEqualsWithoutLE($expectedResult, $actualResult);

        $map = $dumper->asJsonObjectsMap(3);
        $this->assertEqualsWithoutLE(
            <<<S
            {"stdClass#{$statelessObjectId}":"{stateless object}","stdClass#{$statefulObjectId}":{"public \$id":1}}
            S,
            $map,
        );
    }

    /**
     * @dataProvider dataDeepNestedArray
     */
    public function testDeepNestedArray(array $variable, string $expectedResult): void
    {
        $actualResult = Dumper::create($variable)->asJson(2);
        $this->assertEqualsWithoutLE($expectedResult, $actualResult);
    }

    public static function dataDeepNestedArray(): iterable
    {
        yield 'singular' => [
            [[['test']]],
            '[["array (1 item) [...]"]]',
        ];

        yield 'plural' => [
            [[['test', 'test'], ['test']]],
            '[["array (2 items) [...]","array (1 item) [...]"]]',
        ];
    }

    public function testDeepNestedObject(): void
    {
        $object = new ThreeProperties();
        $object->first = $object;
        $variable = [[$object]];

        $output = Dumper::create($variable)->asJson(2);
        $result = sprintf(
            '[["%s#%d (...)"]]',
            str_replace('\\', '\\\\', ThreeProperties::class),
            spl_object_id($object),
        );
        $this->assertEqualsWithoutLE($result, $output);
    }

    public function testObjectVisibilityProperties(): void
    {
        $variable = new ThreeProperties();

        $output = Dumper::create($variable)->asJson(2);
        $result = sprintf(
            '{"%s#%d":{"public $first":"first","protected $second":"second","private $third":"third"}}',
            str_replace('\\', '\\\\', ThreeProperties::class),
            spl_object_id($variable),
        );
        $this->assertEqualsWithoutLE($result, $output);
    }

    public function testFormatJson(): void
    {
        $variable = [['test']];

        $output = Dumper::create($variable)->asJson(2, true);
        $result = <<<S
        [
            [
                "test"
            ]
        ]
        S;
        $this->assertEqualsWithoutLE($result, $output);
    }

    public function testExcludedClasses(): void
    {
        $object1 = new stdClass();
        $object1Id = spl_object_id($object1);
        $object1Class = $object1::class;

        $object2 = new DateTimeZone('UTC');
        $object2Id = spl_object_id($object2);
        $object2Class = $object2::class;

        $actualResult = Dumper::create([$object1, $object2], [$object1Class])->asJson(2, true);
        $expectedResult = <<<S
        [
            "{$object1Class}#{$object1Id} (...)",
            "object@{$object2Class}#{$object2Id}"
        ]
        S;

        $this->assertEqualsWithoutLE($expectedResult, $actualResult);
    }

    public static function jsonDataProvider(): iterable
    {
        $emptyObject = new stdClass();
        $emptyObjectId = spl_object_id($emptyObject);

        yield 'empty object' => [
            $emptyObject,
            <<<S
                {"stdClass#{$emptyObjectId}":"{stateless object}"}
                S,
        ];

        // @formatter:off
        $shortFunctionObject = fn () => 1;
        // @formatter:on
        $shortFunctionObjectId = spl_object_id($shortFunctionObject);

        yield 'short function' => [
            $shortFunctionObject,
            <<<S
                {"Closure#{$shortFunctionObjectId}":"fn () => 1"}
                S,
        ];

        // @formatter:off
        $staticShortFunctionObject = static fn () => 1;
        // @formatter:on
        $staticShortFunctionObjectId = spl_object_id($staticShortFunctionObject);

        yield 'short static function' => [
            $staticShortFunctionObject,
            <<<S
                {"Closure#{$staticShortFunctionObjectId}":"static fn () => 1"}
                S,
        ];

        // @formatter:off
        $functionObject = function () {
            return 1;
        };
        // @formatter:on
        $functionObjectId = spl_object_id($functionObject);

        yield 'function' => [
            $functionObject,
            <<<S
                {"Closure#{$functionObjectId}":"function () {\\n    return 1;\\n}"}
                S,
        ];

        // @formatter:off
        $staticFunctionObject = static function () {
            return 1;
        };
        // @formatter:on
        $staticFunctionObjectId = spl_object_id($staticFunctionObject);

        yield 'static function' => [
            $staticFunctionObject,
            <<<S
                {"Closure#{$staticFunctionObjectId}":"static function () {\\n    return 1;\\n}"}
                S,
        ];
        yield 'string' => [
            'Hello, Yii!',
            '"Hello, Yii!"',
        ];
        yield 'empty string' => [
            '',
            '""',
        ];
        yield 'null' => [
            null,
            'null',
        ];
        yield 'integer' => [
            1,
            '1',
        ];
        yield 'integer with separator' => [
            1_23_456,
            '123456',
        ];
        yield 'boolean' => [
            true,
            'true',
        ];
        yield 'fileResource' => [
            fopen('php://input', 'rb'),
            '{"timed_out":false,"blocked":true,"eof":false,"wrapper_type":"PHP","stream_type":"Input","mode":"rb","unread_bytes":0,"seekable":true,"uri":"php:\/\/input"}',
        ];
        yield 'empty array' => [
            [],
            '[]',
        ];
        yield 'array of 3 elements, automatic keys' => [
            [
                'one',
                'two',
                'three',
            ],
            '["one","two","three"]',
        ];
        yield 'array of 3 elements, custom keys' => [
            [
                2 => 'one',
                'two' => 'two',
                0 => 'three',
            ],
            '{"2":"one","two":"two","0":"three"}',
        ];

        // @formatter:off
        $closureInArrayObject = fn () => new \DateTimeZone('');
        // @formatter:on
        $closureInArrayObjectId = spl_object_id($closureInArrayObject);

        yield 'closure in array' => [
            // @formatter:off
            [$closureInArrayObject],
            // @formatter:on
            <<<S
                [{"Closure#{$closureInArrayObjectId}":"fn () => new \\\DateTimeZone('')"}]
                S,
        ];

        // @formatter:off
        $closureWithUsualClassNameObject = fn (Dumper $date) => new \DateTimeZone('');
        // @formatter:on
        $closureWithUsualClassNameObjectId = spl_object_id($closureWithUsualClassNameObject);

        yield 'original class name' => [
            $closureWithUsualClassNameObject,
            <<<S
                {"Closure#{$closureWithUsualClassNameObjectId}":"fn (\\\Yiisoft\\\Yii\\\Debug\\\Dumper \$date) => new \\\DateTimeZone('')"}
                S,
        ];

        // @formatter:off
        $closureWithAliasedClassNameObject = fn (Dumper $date) => new \DateTimeZone('');
        // @formatter:on
        $closureWithAliasedClassNameObjectId = spl_object_id($closureWithAliasedClassNameObject);

        yield 'class alias' => [
            $closureWithAliasedClassNameObject,
            <<<S
                {"Closure#{$closureWithAliasedClassNameObjectId}":"fn (\\\Yiisoft\\\Yii\\\Debug\\\Dumper \$date) => new \\\DateTimeZone('')"}
                S,
        ];

        // @formatter:off
        $closureWithAliasedNamespaceObject = fn (D\Dumper $date) => new \DateTimeZone('');
        // @formatter:on
        $closureWithAliasedNamespaceObjectId = spl_object_id($closureWithAliasedNamespaceObject);

        yield 'namespace alias' => [
            $closureWithAliasedNamespaceObject,
            <<<S
                {"Closure#{$closureWithAliasedNamespaceObjectId}":"fn (\\\Yiisoft\\\Yii\\\Debug\\\Dumper \$date) => new \\\DateTimeZone('')"}
                S,
        ];
        // @formatter:off
        $closureWithNullCollisionOperatorObject = fn () => $_ENV['var'] ?? null;
        // @formatter:on
        $closureWithNullCollisionOperatorObjectId = spl_object_id($closureWithNullCollisionOperatorObject);

        yield 'closure with null-collision operator' => [
            $closureWithNullCollisionOperatorObject,
            <<<S
                {"Closure#{$closureWithNullCollisionOperatorObjectId}":"fn () => \$_ENV['var'] ?? null"}
                S,
        ];
        yield 'utf8 supported' => [
            '🤣',
            '"🤣"',
        ];


        $objectWithClosureInProperty = new stdClass();
        // @formatter:off
        $objectWithClosureInProperty->a = fn () => 1;
        // @formatter:on
        $objectWithClosureInPropertyId = spl_object_id($objectWithClosureInProperty);
        $objectWithClosureInPropertyClosureId = spl_object_id($objectWithClosureInProperty->a);

        yield 'closure in property supported' => [
            $objectWithClosureInProperty,
            <<<S
                {"stdClass#{$objectWithClosureInPropertyId}":{"public \$a":{"Closure#{$objectWithClosureInPropertyClosureId}":"fn () => 1"}}}
                S,
        ];
        yield 'binary string' => [
            pack('H*', md5('binary string')),
            '"ɍ��^��\u00191\u0017�]�-f�"',
        ];


        $fileResource = tmpfile();
        $fileResourceUri = stream_get_meta_data($fileResource)['uri'];
        $fileResourceUri = addcslashes($fileResourceUri, '/\\');

        yield 'file resource' => [
            $fileResource,
            <<<S
                {"timed_out":false,"blocked":true,"eof":false,"wrapper_type":"plainfile","stream_type":"STDIO","mode":"r+b","unread_bytes":0,"seekable":true,"uri":"{$fileResourceUri}"}
                S,
        ];

        $closedFileResource = tmpfile();
        fclose($closedFileResource);

        yield 'closed file resource' => [
            $closedFileResource,
            '"{closed resource}"',
        ];

        $socketResource = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
        $socketResourceId = spl_object_id($socketResource);
        yield 'socket resource' => [
            $socketResource,
            <<<S
            {"Socket#{$socketResourceId}":"{stateless object}"}
            S,
        ];

        $opendirResource = opendir(sys_get_temp_dir());

        yield 'opendir resource' => [
            $opendirResource,
            <<<S
                {"timed_out":false,"blocked":true,"eof":false,"wrapper_type":"plainfile","stream_type":"dir","mode":"r","unread_bytes":0,"seekable":true}
                S,
        ];

        $curlResource = curl_init('https://example.com');
        $curlResourceObjectId = spl_object_id($curlResource);

        yield 'curl resource' => [
            $curlResource,
            <<<S
                {"CurlHandle#{$curlResourceObjectId}":"{stateless object}"}
                S,
        ];
        yield 'stdout' => [
            STDOUT,
            <<<S
                {"timed_out":false,"blocked":true,"eof":false,"wrapper_type":"PHP","stream_type":"STDIO","mode":"wb","unread_bytes":0,"seekable":false,"uri":"php:\/\/stdout"}
                S,
        ];
        yield 'stderr' => [
            STDERR,
            <<<S
                {"timed_out":false,"blocked":true,"eof":false,"wrapper_type":"PHP","stream_type":"STDIO","mode":"wb","unread_bytes":0,"seekable":false,"uri":"php:\/\/stderr"}
                S,
        ];
        yield 'stdin' => [
            STDIN,
            <<<S
                {"timed_out":false,"blocked":true,"eof":false,"wrapper_type":"PHP","stream_type":"STDIO","mode":"rb","unread_bytes":0,"seekable":false,"uri":"php:\/\/stdin"}
                S,
        ];
    }

    /**
     * Asserting two strings equality ignoring line endings.
     */
    protected function assertEqualsWithoutLE(string $expected, string $actual, string $message = ''): void
    {
        $expected = str_replace(["\r\n", '\r\n'], ["\n", '\n'], $expected);
        $actual = str_replace(["\r\n", '\r\n'], ["\n", '\n'], $actual);
        $this->assertEquals($expected, $actual, $message);
    }
}
