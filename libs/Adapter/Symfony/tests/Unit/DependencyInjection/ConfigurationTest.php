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

        // All collectors enabled by default (except opt-in collectors)
        $optInCollectors = ['code_coverage'];
        foreach ($config['collectors'] as $name => $enabled) {
            if (in_array($name, $optInCollectors, true)) {
                $this->assertFalse($enabled, "Collector '{$name}' should be disabled by default (opt-in)");
            } else {
                $this->assertTrue($enabled, "Collector '{$name}' should be enabled by default");
            }
        }

        // Default ignored patterns
        $this->assertContains('/_wdt/**', $config['ignored_requests']);
        $this->assertContains('/_profiler/**', $config['ignored_requests']);
        $this->assertContains('/debug/**', $config['ignored_requests']);
        $this->assertContains('/inspect/**', $config['ignored_requests']);
        $this->assertContains('completion', $config['ignored_commands']);

        $this->assertSame([], $config['dumper']['excluded_classes']);

        // Panel defaults
        $this->assertSame('', $config['panel']['static_url']);
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

    public function testPanelDefaults(): void
    {
        $config = $this->processConfiguration([]);

        $this->assertArrayHasKey('panel', $config);
        $this->assertSame('', $config['panel']['static_url']);
    }

    public function testPanelCustomStaticUrl(): void
    {
        $config = $this->processConfiguration([
            [
                'panel' => ['static_url' => '/bundles/appdevpanel'],
            ],
        ]);

        $this->assertSame('/bundles/appdevpanel', $config['panel']['static_url']);
    }

    public function testToolbarDefaults(): void
    {
        $config = $this->processConfiguration([]);

        $this->assertArrayHasKey('toolbar', $config);
        $this->assertTrue($config['toolbar']['enabled']);
        $this->assertSame('', $config['toolbar']['static_url']);
    }

    public function testToolbarCustomConfiguration(): void
    {
        $config = $this->processConfiguration([
            [
                'toolbar' => [
                    'enabled' => false,
                    'static_url' => 'http://localhost:3001',
                ],
            ],
        ]);

        $this->assertFalse($config['toolbar']['enabled']);
        $this->assertSame('http://localhost:3001', $config['toolbar']['static_url']);
    }

    public function testApiDefaults(): void
    {
        $config = $this->processConfiguration([]);

        $this->assertArrayHasKey('api', $config);
        $this->assertTrue($config['api']['enabled']);
        $this->assertSame(['127.0.0.1', '::1'], $config['api']['allowed_ips']);
        $this->assertSame('', $config['api']['auth_token']);
        $this->assertNull($config['api']['inspector_url']);
    }

    public function testApiCustomConfiguration(): void
    {
        $config = $this->processConfiguration([
            [
                'api' => [
                    'enabled' => false,
                    'allowed_ips' => ['10.0.0.1', '192.168.1.0'],
                    'auth_token' => 'secret-token',
                ],
            ],
        ]);

        $this->assertFalse($config['api']['enabled']);
        $this->assertSame(['10.0.0.1', '192.168.1.0'], $config['api']['allowed_ips']);
        $this->assertSame('secret-token', $config['api']['auth_token']);
    }

    public function testApiInspectorUrl(): void
    {
        $config = $this->processConfiguration([
            [
                'api' => [
                    'inspector_url' => 'http://localhost:8080',
                ],
            ],
        ]);

        $this->assertSame('http://localhost:8080', $config['api']['inspector_url']);
    }

    public function testPathMappingDefaults(): void
    {
        $config = $this->processConfiguration([]);

        $this->assertArrayHasKey('path_mapping', $config);
        $this->assertSame([], $config['path_mapping']);
    }

    public function testPathMappingCustomConfiguration(): void
    {
        $config = $this->processConfiguration([
            [
                'path_mapping' => [
                    '/app' => '/home/user/project',
                    '/var/www' => '/Users/dev/sites/myapp',
                ],
            ],
        ]);

        $this->assertSame('/home/user/project', $config['path_mapping']['/app']);
        $this->assertSame('/Users/dev/sites/myapp', $config['path_mapping']['/var/www']);
    }

    public function testEnableCodeCoverageCollector(): void
    {
        $config = $this->processConfiguration([
            [
                'collectors' => [
                    'code_coverage' => true,
                ],
            ],
        ]);

        $this->assertTrue($config['collectors']['code_coverage']);
    }

    public function testPanelCustomViteDevServerUrl(): void
    {
        $config = $this->processConfiguration([
            [
                'panel' => ['static_url' => 'http://localhost:3000'],
            ],
        ]);

        $this->assertSame('http://localhost:3000', $config['panel']['static_url']);
    }

    public function testMultipleConfigsMerged(): void
    {
        $config = $this->processConfiguration([
            [
                'storage' => ['path' => '/tmp/debug1'],
                'collectors' => ['doctrine' => false],
            ],
            [
                'storage' => ['path' => '/tmp/debug2'],
                'collectors' => ['twig' => false],
            ],
        ]);

        // Last config wins for scalars
        $this->assertSame('/tmp/debug2', $config['storage']['path']);
        // Both collector changes should be present
        $this->assertFalse($config['collectors']['doctrine']);
        $this->assertFalse($config['collectors']['twig']);
    }

    public function testAllCollectorNodesPresent(): void
    {
        $config = $this->processConfiguration([]);

        $expectedCollectors = [
            'environment',
            'request',
            'exception',
            'log',
            'event',
            'service',
            'http_client',
            'timeline',
            'var_dumper',
            'filesystem_stream',
            'http_stream',
            'command',
            'doctrine',
            'twig',
            'security',
            'cache',
            'mailer',
            'queue',
            'validator',
            'router',
            'deprecation',
            'opentelemetry',
            'translator',
            'elasticsearch',
            'redis',
            'assets',
            'code_coverage',
        ];

        foreach ($expectedCollectors as $name) {
            $this->assertArrayHasKey($name, $config['collectors'], "Collector '{$name}' should exist");
        }
    }

    public function testDisableAllCollectorsIndividually(): void
    {
        $collectorsConfig = [];
        $collectorNames = [
            'environment',
            'request',
            'exception',
            'log',
            'event',
            'service',
            'http_client',
            'timeline',
            'var_dumper',
            'filesystem_stream',
            'http_stream',
            'command',
            'doctrine',
            'twig',
            'security',
            'cache',
            'mailer',
            'queue',
            'validator',
            'router',
            'deprecation',
            'opentelemetry',
            'translator',
            'elasticsearch',
            'redis',
            'assets',
        ];

        foreach ($collectorNames as $name) {
            $collectorsConfig[$name] = false;
        }

        $config = $this->processConfiguration([['collectors' => $collectorsConfig]]);

        foreach ($collectorNames as $name) {
            $this->assertFalse($config['collectors'][$name], "Collector '{$name}' should be disabled");
        }
    }

    public function testIgnoredCommandsDefault(): void
    {
        $config = $this->processConfiguration([]);

        $this->assertContains('help', $config['ignored_commands']);
        $this->assertContains('list', $config['ignored_commands']);
        $this->assertContains('debug:*', $config['ignored_commands']);
    }

    public function testDumperDefaults(): void
    {
        $config = $this->processConfiguration([]);

        $this->assertArrayHasKey('dumper', $config);
        $this->assertArrayHasKey('excluded_classes', $config['dumper']);
        $this->assertSame([], $config['dumper']['excluded_classes']);
    }

    public function testFullCustomConfiguration(): void
    {
        $config = $this->processConfiguration([
            [
                'enabled' => false,
                'storage' => [
                    'path' => '/custom/path',
                    'history_size' => 200,
                ],
                'collectors' => [
                    'request' => false,
                    'log' => false,
                    'code_coverage' => true,
                ],
                'ignored_requests' => ['/custom/**'],
                'ignored_commands' => ['custom:cmd'],
                'dumper' => ['excluded_classes' => ['App\\Heavy']],
                'path_mapping' => ['/app' => '/local'],
                'panel' => ['static_url' => 'http://cdn.example.com'],
                'toolbar' => ['enabled' => false, 'static_url' => 'http://cdn.example.com/toolbar'],
                'api' => [
                    'enabled' => false,
                    'allowed_ips' => ['10.0.0.0'],
                    'auth_token' => 'my-token',
                    'inspector_url' => 'http://myapp.example.com',
                ],
            ],
        ]);

        $this->assertFalse($config['enabled']);
        $this->assertSame('/custom/path', $config['storage']['path']);
        $this->assertSame(200, $config['storage']['history_size']);
        $this->assertFalse($config['collectors']['request']);
        $this->assertFalse($config['collectors']['log']);
        $this->assertTrue($config['collectors']['code_coverage']);
        $this->assertSame(['/custom/**'], $config['ignored_requests']);
        $this->assertSame(['custom:cmd'], $config['ignored_commands']);
        $this->assertSame(['App\\Heavy'], $config['dumper']['excluded_classes']);
        $this->assertSame('/local', $config['path_mapping']['/app']);
        $this->assertSame('http://cdn.example.com', $config['panel']['static_url']);
        $this->assertFalse($config['toolbar']['enabled']);
        $this->assertSame('http://cdn.example.com/toolbar', $config['toolbar']['static_url']);
        $this->assertFalse($config['api']['enabled']);
        $this->assertSame(['10.0.0.0'], $config['api']['allowed_ips']);
        $this->assertSame('my-token', $config['api']['auth_token']);
        $this->assertSame('http://myapp.example.com', $config['api']['inspector_url']);
    }

    private function processConfiguration(array $configs): array
    {
        return new Processor()->processConfiguration(new Configuration(), $configs);
    }
}
