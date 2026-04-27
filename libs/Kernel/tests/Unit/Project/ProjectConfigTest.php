<?php

declare(strict_types=1);

namespace AppDevPanel\Kernel\Tests\Unit\Project;

use AppDevPanel\Kernel\Project\ProjectConfig;
use PHPUnit\Framework\TestCase;

final class ProjectConfigTest extends TestCase
{
    public function testEmptyConfigHasNoEntries(): void
    {
        $config = ProjectConfig::empty();

        $this->assertSame([], $config->frames);
        $this->assertSame([], $config->openapi);
    }

    public function testToArrayIncludesVersion(): void
    {
        $config = new ProjectConfig(frames: ['Logs' => 'https://logs.example/'], openapi: [
            'Main' => '/api/openapi.json',
        ]);

        $array = $config->toArray();

        $this->assertSame(ProjectConfig::CURRENT_VERSION, $array['version']);
        $this->assertSame(['Logs' => 'https://logs.example/'], $array['frames']);
        $this->assertSame(['Main' => '/api/openapi.json'], $array['openapi']);
    }

    public function testFromArrayIgnoresMissingKeys(): void
    {
        $config = ProjectConfig::fromArray([]);

        $this->assertSame([], $config->frames);
        $this->assertSame([], $config->openapi);
    }

    public function testFromArrayDropsNonStringValues(): void
    {
        $config = ProjectConfig::fromArray([
            'frames' => [
                'Valid' => 'https://valid.example/',
                'BadInt' => 42,
                '' => 'https://emptyname.example/',
                'Empty' => '',
            ],
            'openapi' => [
                'Spec' => '/openapi.json',
                123 => 'https://numkey.example/',
            ],
        ]);

        $this->assertSame(['Valid' => 'https://valid.example/'], $config->frames);
        $this->assertSame(['Spec' => '/openapi.json'], $config->openapi);
    }

    public function testFromArrayIgnoresNonArrayBranches(): void
    {
        $config = ProjectConfig::fromArray([
            'frames' => 'not-an-array',
            'openapi' => null,
        ]);

        $this->assertSame([], $config->frames);
        $this->assertSame([], $config->openapi);
    }

    public function testWithFramesProducesNewInstance(): void
    {
        $original = new ProjectConfig(frames: ['A' => 'https://a/'], openapi: ['Spec' => '/spec.json']);
        $updated = $original->withFrames(['B' => 'https://b/']);

        $this->assertNotSame($original, $updated);
        $this->assertSame(['A' => 'https://a/'], $original->frames);
        $this->assertSame(['B' => 'https://b/'], $updated->frames);
        $this->assertSame(['Spec' => '/spec.json'], $updated->openapi);
    }

    public function testWithOpenApiProducesNewInstance(): void
    {
        $original = new ProjectConfig(frames: ['A' => 'https://a/'], openapi: ['Spec' => '/spec.json']);
        $updated = $original->withOpenApi(['Other' => '/other.json']);

        $this->assertNotSame($original, $updated);
        $this->assertSame(['Spec' => '/spec.json'], $original->openapi);
        $this->assertSame(['Other' => '/other.json'], $updated->openapi);
        $this->assertSame(['A' => 'https://a/'], $updated->frames);
    }

    public function testWithFramesNormalisesInput(): void
    {
        $config = ProjectConfig::empty()->withFrames([
            'OK' => 'https://ok/',
            '' => 'https://emptyname/',
            'Bad' => '',
        ]);

        $this->assertSame(['OK' => 'https://ok/'], $config->frames);
    }
}
