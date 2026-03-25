<?php

declare(strict_types=1);

namespace AppDevPanel\Kernel\Tests\Unit\Collector;

use AppDevPanel\Kernel\Collector\OtlpTraceParser;
use PHPUnit\Framework\TestCase;

final class OtlpTraceParserTest extends TestCase
{
    private OtlpTraceParser $parser;

    protected function setUp(): void
    {
        $this->parser = new OtlpTraceParser();
    }

    public function testParseMinimalTrace(): void
    {
        $data = [
            'resourceSpans' => [
                [
                    'resource' => [
                        'attributes' => [
                            ['key' => 'service.name', 'value' => ['stringValue' => 'my-service']],
                        ],
                    ],
                    'scopeSpans' => [
                        [
                            'spans' => [
                                [
                                    'traceId' => 'aaaa1111bbbb2222cccc3333dddd4444',
                                    'spanId' => '1111222233334444',
                                    'name' => 'GET /api/users',
                                    'startTimeUnixNano' => '1700000000000000000',
                                    'endTimeUnixNano' => '1700000000150000000',
                                    'kind' => 2,
                                    'status' => ['code' => 1],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $spans = $this->parser->parse($data);

        $this->assertCount(1, $spans);
        $span = $spans[0];
        $this->assertSame('aaaa1111bbbb2222cccc3333dddd4444', $span->traceId);
        $this->assertSame('1111222233334444', $span->spanId);
        $this->assertNull($span->parentSpanId);
        $this->assertSame('GET /api/users', $span->operationName);
        $this->assertSame('my-service', $span->serviceName);
        $this->assertSame('OK', $span->status);
        $this->assertSame('SERVER', $span->kind);
        $this->assertEqualsWithDelta(150.0, $span->duration, 0.01);
    }

    public function testParseWithParentSpan(): void
    {
        $data = [
            'resourceSpans' => [
                [
                    'resource' => ['attributes' => []],
                    'scopeSpans' => [
                        [
                            'spans' => [
                                [
                                    'traceId' => 'trace1',
                                    'spanId' => 'span1',
                                    'parentSpanId' => 'parent1',
                                    'name' => 'child-op',
                                    'startTimeUnixNano' => '1000000000',
                                    'endTimeUnixNano' => '2000000000',
                                    'kind' => 3,
                                    'status' => ['code' => 2, 'message' => 'Not found'],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $spans = $this->parser->parse($data);
        $this->assertCount(1, $spans);
        $this->assertSame('parent1', $spans[0]->parentSpanId);
        $this->assertSame('ERROR', $spans[0]->status);
        $this->assertSame('Not found', $spans[0]->statusMessage);
        $this->assertSame('CLIENT', $spans[0]->kind);
        $this->assertSame('unknown', $spans[0]->serviceName);
    }

    public function testParseAttributes(): void
    {
        $data = [
            'resourceSpans' => [
                [
                    'resource' => ['attributes' => []],
                    'scopeSpans' => [
                        [
                            'spans' => [
                                [
                                    'traceId' => 'trace1',
                                    'spanId' => 'span1',
                                    'name' => 'test',
                                    'startTimeUnixNano' => '0',
                                    'endTimeUnixNano' => '0',
                                    'attributes' => [
                                        ['key' => 'str', 'value' => ['stringValue' => 'hello']],
                                        ['key' => 'num', 'value' => ['intValue' => '42']],
                                        ['key' => 'dbl', 'value' => ['doubleValue' => 3.14]],
                                        ['key' => 'bool', 'value' => ['boolValue' => true]],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $spans = $this->parser->parse($data);
        $attrs = $spans[0]->attributes;

        $this->assertSame('hello', $attrs['str']);
        $this->assertSame(42, $attrs['num']);
        $this->assertSame(3.14, $attrs['dbl']);
        $this->assertTrue($attrs['bool']);
    }

    public function testParseEvents(): void
    {
        $data = [
            'resourceSpans' => [
                [
                    'resource' => ['attributes' => []],
                    'scopeSpans' => [
                        [
                            'spans' => [
                                [
                                    'traceId' => 'trace1',
                                    'spanId' => 'span1',
                                    'name' => 'test',
                                    'startTimeUnixNano' => '0',
                                    'endTimeUnixNano' => '0',
                                    'events' => [
                                        [
                                            'name' => 'exception',
                                            'timeUnixNano' => '1700000000500000000',
                                            'attributes' => [
                                                [
                                                    'key' => 'exception.message',
                                                    'value' => ['stringValue' => 'test error'],
                                                ],
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $spans = $this->parser->parse($data);
        $this->assertCount(1, $spans[0]->events);
        $this->assertSame('exception', $spans[0]->events[0]['name']);
        $this->assertSame('test error', $spans[0]->events[0]['attributes']['exception.message']);
    }

    public function testParseLinks(): void
    {
        $data = [
            'resourceSpans' => [
                [
                    'resource' => ['attributes' => []],
                    'scopeSpans' => [
                        [
                            'spans' => [
                                [
                                    'traceId' => 'trace1',
                                    'spanId' => 'span1',
                                    'name' => 'test',
                                    'startTimeUnixNano' => '0',
                                    'endTimeUnixNano' => '0',
                                    'links' => [
                                        [
                                            'traceId' => 'linked-trace',
                                            'spanId' => 'linked-span',
                                            'attributes' => [],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $spans = $this->parser->parse($data);
        $this->assertCount(1, $spans[0]->links);
        $this->assertSame('linked-trace', $spans[0]->links[0]['traceId']);
        $this->assertSame('linked-span', $spans[0]->links[0]['spanId']);
    }

    public function testParseEmptyPayload(): void
    {
        $this->assertSame([], $this->parser->parse([]));
        $this->assertSame([], $this->parser->parse(['resourceSpans' => []]));
    }

    public function testParseMultipleSpansAcrossScopes(): void
    {
        $data = [
            'resourceSpans' => [
                [
                    'resource' => [
                        'attributes' => [
                            ['key' => 'service.name', 'value' => ['stringValue' => 'svc-a']],
                        ],
                    ],
                    'scopeSpans' => [
                        [
                            'spans' => [
                                [
                                    'traceId' => 'trace1',
                                    'spanId' => 'span1',
                                    'name' => 'op1',
                                    'startTimeUnixNano' => '0',
                                    'endTimeUnixNano' => '0',
                                ],
                            ],
                        ],
                        [
                            'spans' => [
                                [
                                    'traceId' => 'trace1',
                                    'spanId' => 'span2',
                                    'name' => 'op2',
                                    'startTimeUnixNano' => '0',
                                    'endTimeUnixNano' => '0',
                                ],
                            ],
                        ],
                    ],
                ],
                [
                    'resource' => [
                        'attributes' => [
                            ['key' => 'service.name', 'value' => ['stringValue' => 'svc-b']],
                        ],
                    ],
                    'scopeSpans' => [
                        [
                            'spans' => [
                                [
                                    'traceId' => 'trace2',
                                    'spanId' => 'span3',
                                    'name' => 'op3',
                                    'startTimeUnixNano' => '0',
                                    'endTimeUnixNano' => '0',
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $spans = $this->parser->parse($data);
        $this->assertCount(3, $spans);
        $this->assertSame('svc-a', $spans[0]->serviceName);
        $this->assertSame('svc-a', $spans[1]->serviceName);
        $this->assertSame('svc-b', $spans[2]->serviceName);
    }

    public function testAllSpanKinds(): void
    {
        $parser = new OtlpTraceParser();

        $kinds = [
            0 => 'UNSPECIFIED',
            1 => 'INTERNAL',
            2 => 'SERVER',
            3 => 'CLIENT',
            4 => 'PRODUCER',
            5 => 'CONSUMER',
        ];

        foreach ($kinds as $code => $expected) {
            $data = [
                'resourceSpans' => [
                    [
                        'resource' => ['attributes' => []],
                        'scopeSpans' => [
                            [
                                'spans' => [
                                    [
                                        'traceId' => 'trace1',
                                        'spanId' => 'span1',
                                        'name' => 'test',
                                        'startTimeUnixNano' => '0',
                                        'endTimeUnixNano' => '0',
                                        'kind' => $code,
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ];

            $spans = $parser->parse($data);
            $this->assertSame($expected, $spans[0]->kind, "Kind code $code should map to $expected");
        }
    }
}
