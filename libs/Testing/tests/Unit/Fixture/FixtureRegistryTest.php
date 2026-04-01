<?php

declare(strict_types=1);

namespace AppDevPanel\Testing\Tests\Unit\Fixture;

use AppDevPanel\Testing\Fixture\Fixture;
use AppDevPanel\Testing\Fixture\FixtureRegistry;
use PHPUnit\Framework\TestCase;

final class FixtureRegistryTest extends TestCase
{
    public function testAllReturnsNonEmptyList(): void
    {
        $fixtures = FixtureRegistry::all();

        $this->assertNotEmpty($fixtures);
    }

    public function testAllFixtureNamesAreUnique(): void
    {
        $fixtures = FixtureRegistry::all();
        $names = array_map(static fn(Fixture $f) => $f->name, $fixtures);

        $this->assertSame($names, array_unique($names));
    }

    public function testAllFixturesHaveExpectations(): void
    {
        foreach (FixtureRegistry::all() as $fixture) {
            $this->assertNotEmpty($fixture->expectations, sprintf('Fixture "%s" has no expectations', $fixture->name));
        }
    }

    public function testAllFixturesHaveEndpoints(): void
    {
        foreach (FixtureRegistry::all() as $fixture) {
            $this->assertStringStartsWith(
                '/test/fixtures/',
                $fixture->endpoint,
                sprintf('Fixture "%s" endpoint must start with /test/fixtures/', $fixture->name),
            );
        }
    }

    public function testTagsReturnsExpectedList(): void
    {
        $this->assertSame(['core', 'web', 'error', 'advanced'], FixtureRegistry::tags());
    }

    public function testByTagReturnsSubsetOfAll(): void
    {
        $all = FixtureRegistry::all();
        $allNames = array_map(static fn(Fixture $f) => $f->name, $all);

        foreach (FixtureRegistry::tags() as $tag) {
            $tagged = FixtureRegistry::byTag($tag);
            foreach ($tagged as $fixture) {
                $this->assertContains(
                    $fixture->name,
                    $allNames,
                    sprintf('Fixture "%s" from tag "%s" not found in all()', $fixture->name, $tag),
                );
            }
        }
    }

    public function testByTagUnknownReturnsEmpty(): void
    {
        $this->assertSame([], FixtureRegistry::byTag('nonexistent'));
    }

    public function testByTagCoverageMatchesAll(): void
    {
        $allNames = array_map(static fn(Fixture $f) => $f->name, FixtureRegistry::all());
        sort($allNames);

        $taggedNames = [];
        foreach (FixtureRegistry::tags() as $tag) {
            foreach (FixtureRegistry::byTag($tag) as $fixture) {
                $taggedNames[] = $fixture->name;
            }
        }
        sort($taggedNames);

        $this->assertSame($allNames, $taggedNames);
    }

    public function testFilesystemBasicFixtureExists(): void
    {
        $fixtures = FixtureRegistry::byTag('advanced');
        $fsFixtures = array_filter($fixtures, static fn(Fixture $f) => $f->name === 'filesystem:basic');

        $this->assertCount(1, $fsFixtures);

        $fs = array_values($fsFixtures)[0];
        $this->assertSame('/test/fixtures/filesystem', $fs->endpoint);
        $this->assertSame('GET', $fs->method);
        $this->assertArrayHasKey('fs_stream', $fs->expectations);
        $this->assertCount(2, $fs->expectations['fs_stream']);
    }

    public function testFilesystemStreamsFixtureExists(): void
    {
        $fixtures = FixtureRegistry::byTag('advanced');
        $fsFixtures = array_filter($fixtures, static fn(Fixture $f) => $f->name === 'filesystem:streams');

        $this->assertCount(1, $fsFixtures);

        $fs = array_values($fsFixtures)[0];
        $this->assertSame('/test/fixtures/filesystem-streams', $fs->endpoint);
        $this->assertSame('GET', $fs->method);
        $this->assertArrayHasKey('fs_stream', $fs->expectations);
        $this->assertCount(2, $fs->expectations['fs_stream']);
    }

    public function testAssetsFixtureExists(): void
    {
        $fixtures = FixtureRegistry::byTag('advanced');
        $assetsFixtures = array_filter($fixtures, static fn(Fixture $f) => $f->name === 'assets:basic');

        $this->assertCount(1, $assetsFixtures);

        $assets = array_values($assetsFixtures)[0];
        $this->assertSame('/test/fixtures/assets', $assets->endpoint);
        $this->assertSame('GET', $assets->method);
        $this->assertArrayHasKey('assets', $assets->expectations);
        $this->assertCount(3, $assets->expectations['assets']);
    }

    public function testCacheFixtureExists(): void
    {
        $fixtures = FixtureRegistry::byTag('advanced');
        $cacheFixtures = array_filter($fixtures, static fn(Fixture $f) => $f->name === 'cache:basic');

        $this->assertCount(1, $cacheFixtures);

        $cache = array_values($cacheFixtures)[0];
        $this->assertSame('/test/fixtures/cache', $cache->endpoint);
        $this->assertSame('GET', $cache->method);
        $this->assertArrayHasKey('cache', $cache->expectations);
        $this->assertCount(5, $cache->expectations['cache']);
    }
}
