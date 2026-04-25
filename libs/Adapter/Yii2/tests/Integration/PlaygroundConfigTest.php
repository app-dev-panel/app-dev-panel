<?php

declare(strict_types=1);

namespace AppDevPanel\Adapter\Yii2\Tests\Integration;

use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\TestCase;

/**
 * Tests that the Yii 2 playground app configuration is correct.
 *
 * Specifically validates that the adp module is included in the
 * bootstrap array, which is required for route registration and
 * debugger initialization.
 *
 * Without 'app-dev-panel' in bootstrap, the Module::bootstrap() method
 * is never called, resulting in 404 errors for /debug/api/* and
 * /inspect/api/* endpoints.
 */
#[CoversNothing]
final class PlaygroundConfigTest extends TestCase
{
    private array $config;

    protected function setUp(): void
    {
        $configPath = dirname(__DIR__, 3) . '/../../playground/yii2-basic-app/config/web.php';

        if (!file_exists($configPath)) {
            $this->markTestSkipped('Playground config not found at: ' . $configPath);
        }

        $this->config = require $configPath;
    }

    public function testConfigHasBootstrapArray(): void
    {
        $this->assertArrayHasKey('bootstrap', $this->config);
        $this->assertIsArray($this->config['bootstrap']);
    }

    public function testDebugPanelIsInBootstrapArray(): void
    {
        $this->assertContains(
            'app-dev-panel',
            $this->config['bootstrap'],
            'The "adp" module must be in the bootstrap array. '
            . 'Without it, Module::bootstrap() is never called and API routes '
            . '(/debug/api/*, /inspect/api/*) will return 404.',
        );
    }

    public function testDebugPanelModuleIsConfigured(): void
    {
        $this->assertArrayHasKey('modules', $this->config);
        $this->assertArrayHasKey('app-dev-panel', $this->config['modules']);
    }

    public function testDebugPanelBootstrapsBeforeLog(): void
    {
        $bootstrap = $this->config['bootstrap'];
        $debugIndex = array_search('app-dev-panel', $bootstrap, true);
        $logIndex = array_search('log', $bootstrap, true);

        if ($logIndex === false) {
            $this->markTestSkipped('No "log" component in bootstrap array.');
        }

        $this->assertNotFalse($debugIndex, '"adp" should be in bootstrap array');
        $this->assertLessThan(
            $logIndex,
            $debugIndex,
            '"adp" should bootstrap before "log" to capture early log messages.',
        );
    }
}
