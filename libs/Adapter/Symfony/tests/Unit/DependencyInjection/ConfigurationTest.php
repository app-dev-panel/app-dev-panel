<?php

declare(strict_types=1);

namespace AppDevPanel\Adapter\Symfony\Tests\Unit\DependencyInjection;

use AppDevPanel\Adapter\Symfony\DependencyInjection\Configuration;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Config\Definition\Processor;

final class ConfigurationTest extends TestCase
{
    public function testDefaultConfiguration(): void
    {
        $config = $this->processConfiguration([]);

        $this->assertTrue($config['enabled']);
        $this->assertSame('%kernel.project_dir%/var/debug', $config['storage']['path']);
        $this->assertSame(50, $config['storage']['history_size']);

        // All collectors enabled by default
        foreach ($config['collectors'] as $name => $enabled) {
            $this->assertTrue($enabled, "Collector '{$name}' should be enabled by default");
        }

        // Default ignored patterns
        $this->assertContains('/_wdt/*', $config['ignored_requests']);
        $this->assertContains('/_profiler/*', $config['ignored_requests']);
        $this->assertContains('/debug/api/*', $config['ignored_requests']);
        $this->assertContains('completion', $config['ignored_commands']);

        $this->assertSame([], $config['dumper']['excluded_classes']);
    }

    public function testDisabled(): void
    {
        $config = $this->processConfiguration([['enabled' => false]]);

        $this->assertFalse($config['enabled']);
    }

    public function testCustomStorage(): void
    {
        $config = $this->processConfiguration([
            [
                'storage' => [
                    'path' => '/tmp/debug',
                    'history_size' => 100,
                ],
            ],
        ]);

        $this->assertSame('/tmp/debug', $config['storage']['path']);
        $this->assertSame(100, $config['storage']['history_size']);
    }

    public function testDisableSpecificCollectors(): void
    {
        $config = $this->processConfiguration([
            [
                'collectors' => [
                    'doctrine' => false,
                    'twig' => false,
                    'security' => false,
                ],
            ],
        ]);

        $this->assertFalse($config['collectors']['doctrine']);
        $this->assertFalse($config['collectors']['twig']);
        $this->assertFalse($config['collectors']['security']);
        $this->assertTrue($config['collectors']['request']);
        $this->assertTrue($config['collectors']['log']);
    }

    public function testCustomIgnoredPatterns(): void
    {
        $config = $this->processConfiguration([
            [
                'ignored_requests' => ['/health', '/metrics/*'],
                'ignored_commands' => ['cache:clear'],
            ],
        ]);

        $this->assertSame(['/health', '/metrics/*'], $config['ignored_requests']);
        $this->assertSame(['cache:clear'], $config['ignored_commands']);
    }

    public function testDumperExcludedClasses(): void
    {
        $config = $this->processConfiguration([
            [
                'dumper' => [
                    'excluded_classes' => ['App\\HeavyObject', 'App\\CircularRef'],
                ],
            ],
        ]);

        $this->assertSame(['App\\HeavyObject', 'App\\CircularRef'], $config['dumper']['excluded_classes']);
    }

    private function processConfiguration(array $configs): array
    {
        return (new Processor())->processConfiguration(new Configuration(), $configs);
    }
}
