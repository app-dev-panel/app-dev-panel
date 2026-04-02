<?php

declare(strict_types=1);

namespace AppDevPanel\Adapter\Yii2\Tests\Integration;

use AppDevPanel\Adapter\Yii2\Module;
use PHPUnit\Framework\TestCase;
use yii\base\Event;

/**
 * Base test case for Yii 2 integration tests.
 *
 * Creates a real Yii 2 Application with the ADP module bootstrapped,
 * replacing the previous approach of calling private methods via reflection.
 *
 * Handles:
 * - Temporary directory creation/cleanup
 * - Real Yii 2 Application lifecycle (web or console)
 * - Event handler cleanup between tests (prevents cross-test contamination)
 * - Container reset
 */
abstract class Yii2IntegrationTestCase extends TestCase
{
    protected string $storagePath;

    protected function setUp(): void
    {
        $this->storagePath = sys_get_temp_dir() . '/adp_yii2_test_' . bin2hex(random_bytes(8));
        mkdir($this->storagePath, 0o777, true);
        mkdir($this->storagePath . '/runtime', 0o777, true);
    }

    protected function tearDown(): void
    {
        \Yii::$app = null;
        \Yii::$container = new \yii\di\Container();

        // Clear all class-level event handlers to prevent cross-test contamination.
        // Yii 2 stores these in private static arrays on the Event class.
        $eventsRef = new \ReflectionProperty(Event::class, '_events');
        $eventsRef->setValue(null, []);

        $wildcardRef = new \ReflectionProperty(Event::class, '_eventWildcards');
        $wildcardRef->setValue(null, []);

        $this->removeDirectory($this->storagePath);
    }

    /**
     * Create a real Yii 2 web application with the ADP module bootstrapped.
     *
     * The module's bootstrap() method is called automatically by the Application
     * constructor, which registers all services, collectors, event listeners, and routes.
     *
     * @param array<string, mixed> $moduleConfig Override module configuration
     * @param array<string, mixed> $appConfig    Additional application configuration
     */
    protected function createWebApplication(array $moduleConfig = [], array $appConfig = []): \yii\web\Application
    {
        $config = array_replace_recursive([
            'id' => 'adp-test-app',
            'basePath' => $this->storagePath,
            'components' => [
                'request' => [
                    'cookieValidationKey' => 'adp-test-secret',
                ],
                'urlManager' => [
                    'enablePrettyUrl' => true,
                    'showScriptName' => false,
                ],
                'log' => [
                    'targets' => [],
                ],
            ],
            'modules' => [
                'adp' => array_merge([
                    'class' => Module::class,
                    'storagePath' => $this->storagePath . '/debug',
                    'historySize' => 10,
                ], $moduleConfig),
            ],
            'bootstrap' => ['adp'],
        ], $appConfig);

        // Module::hookErrorHandler() installs a custom exception handler during bootstrap.
        // Restore PHPUnit's handler afterward to avoid "risky test" warnings.
        $previousHandler = set_exception_handler(null);
        restore_exception_handler();

        $app = new \yii\web\Application($config);

        // Restore original handler (Module's handler is only needed for real error flows)
        set_exception_handler($previousHandler);

        return $app;
    }

    /**
     * Create a real Yii 2 console application with the ADP module bootstrapped.
     *
     * @param array<string, mixed> $moduleConfig Override module configuration
     */
    protected function createConsoleApplication(array $moduleConfig = []): \yii\console\Application
    {
        return new \yii\console\Application([
            'id' => 'adp-test-console',
            'basePath' => $this->storagePath,
            'components' => [
                'log' => [
                    'targets' => [],
                ],
            ],
            'modules' => [
                'adp' => array_merge([
                    'class' => Module::class,
                    'storagePath' => $this->storagePath . '/debug',
                    'historySize' => 10,
                ], $moduleConfig),
            ],
            'bootstrap' => ['adp'],
        ]);
    }

    /**
     * Get the bootstrapped ADP module from the current application.
     */
    protected function getModule(): Module
    {
        $module = \Yii::$app->getModule('adp');
        $this->assertInstanceOf(Module::class, $module);

        return $module;
    }

    protected function removeDirectory(string $path): void
    {
        if (!is_dir($path)) {
            return;
        }

        $items = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($path, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST,
        );

        foreach ($items as $item) {
            if ($item->isDir()) {
                rmdir($item->getPathname());
            } else {
                unlink($item->getPathname());
            }
        }

        rmdir($path);
    }
}
