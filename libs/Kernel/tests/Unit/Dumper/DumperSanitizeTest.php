<?php

declare(strict_types=1);

namespace AppDevPanel\Kernel\Tests\Unit\Dumper;

use AppDevPanel\Kernel\Dumper;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * Ensures Dumper::encodeJson does not throw JsonException on PHP values
 * that json_encode cannot represent (resources, NAN, INF, nested resources).
 *
 * Previously, framework-collected data leaked values like Guzzle stream
 * resources which caused json_encode to throw "Type is not supported"
 * and drop all debug data for the request.
 */
final class DumperSanitizeTest extends TestCase
{
    #[Test]
    public function encodesResourceAsDescriptiveString(): void
    {
        $handle = fopen('php://memory', 'rb');
        self::assertIsResource($handle);

        try {
            $json = Dumper::create(['stream' => $handle])->asJson();
        } finally {
            fclose($handle);
        }

        $decoded = json_decode($json, true);
        self::assertIsArray($decoded);
        self::assertArrayHasKey('stream', $decoded);
        // Resources are converted to meta-data array by DumpContext or to a
        // placeholder string by Dumper::sanitizeForJson — either is acceptable,
        // as long as json_encode succeeded.
        self::assertTrue(is_array($decoded['stream']) || is_string($decoded['stream']));
    }

    #[Test]
    public function encodesNanAsPlaceholder(): void
    {
        $json = Dumper::create(['value' => NAN])->asJson();
        $decoded = json_decode($json, true);

        self::assertSame('(nan)', $decoded['value']);
    }

    #[Test]
    public function encodesInfAsPlaceholder(): void
    {
        $json = Dumper::create(['value' => INF])->asJson();
        $decoded = json_decode($json, true);

        self::assertSame('(inf)', $decoded['value']);
    }

    #[Test]
    public function encodesNegativeInfAsPlaceholder(): void
    {
        $json = Dumper::create(['value' => -INF])->asJson();
        $decoded = json_decode($json, true);

        self::assertSame('(-inf)', $decoded['value']);
    }

    #[Test]
    public function encodesDeeplyNestedResourceWithoutThrowing(): void
    {
        $handle = fopen('php://memory', 'rb');
        self::assertIsResource($handle);

        try {
            $data = [
                'level1' => [
                    'level2' => [
                        'level3' => [
                            'resource' => $handle,
                            'nan' => NAN,
                        ],
                    ],
                ],
            ];

            $json = Dumper::create($data)->asJson();
        } finally {
            fclose($handle);
        }

        $decoded = json_decode($json, true);
        self::assertIsArray($decoded);
        $leaf = $decoded['level1']['level2']['level3'];
        self::assertArrayHasKey('resource', $leaf);
        self::assertSame('(nan)', $leaf['nan']);
    }

    #[Test]
    public function encodesFiniteFloatsUnchanged(): void
    {
        $json = Dumper::create(['a' => 1.5, 'b' => 0.0, 'c' => -2.25])->asJson();
        $decoded = json_decode($json, true);

        self::assertSame(1.5, $decoded['a']);
        self::assertEquals(0, $decoded['b']);
        self::assertSame(-2.25, $decoded['c']);
    }
}
