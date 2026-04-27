<?php

declare(strict_types=1);

namespace AppDevPanel\Adapter\Yii2\Tests\Unit;

use AppDevPanel\Adapter\Yii2\Bootstrap;
use AppDevPanel\Adapter\Yii2\Module;
use PHPUnit\Framework\TestCase;
use yii\log\Dispatcher;
use yii\log\Logger;
use yii\log\Target;
use yii\web\Application;
use yii\web\UrlManager;

/**
 * Regression tests covering the install-time warnings emitted by `Bootstrap`.
 *
 * We do NOT mutate the application config; we only assert that users running
 * into common misconfigurations get a clear message in the logs instead of
 * a silent failure (see `demo/INSTALL_REPORT.md`).
 */
final class BootstrapWarningTest extends TestCase
{
    /**
     * @var list<array{0:string,1:int,2:string}>
     */
    private array $messages = [];
    private ?Logger $originalLogger = null;

    protected function setUp(): void
    {
        $this->messages = [];
        $this->originalLogger = \Yii::getLogger();

        $target = new class($this->messages) extends Target {
            public function __construct(
                private array &$store,
            ) {
                parent::__construct();
                $this->levels = Logger::LEVEL_WARNING;
            }

            public function export(): void
            {
                foreach ($this->messages as $message) {
                    $this->store[] = [$message[0], $message[1], $message[2]];
                }
            }
        };

        $logger = new Logger();
        $logger->dispatcher = new Dispatcher([
            'targets' => [$target],
            'logger' => $logger,
        ]);
        \Yii::setLogger($logger);
    }

    protected function tearDown(): void
    {
        if ($this->originalLogger !== null) {
            \Yii::setLogger($this->originalLogger);
        }
    }

    public function testWarnsWhenYii2DebugIsRegistered(): void
    {
        $app = $this->createWebApp(
            hasDebugModule: true,
            debugModuleConfig: ['class' => 'yii\\debug\\Module'],
            prettyUrls: true,
        );

        new Bootstrap()->bootstrap($app);
        \Yii::getLogger()->flush(true);

        $this->assertWarningContains('yiisoft/yii2-debug');
    }

    public function testDoesNotWarnWhenDebugModuleIsCustom(): void
    {
        $app = $this->createWebApp(
            hasDebugModule: true,
            debugModuleConfig: ['class' => 'my\\custom\\DebugModule'],
            prettyUrls: true,
        );

        new Bootstrap()->bootstrap($app);
        \Yii::getLogger()->flush(true);

        $this->assertWarningNotContains('yiisoft/yii2-debug');
    }

    public function testWarnsWhenPrettyUrlsDisabled(): void
    {
        $app = $this->createWebApp(hasDebugModule: false, debugModuleConfig: null, prettyUrls: false);

        new Bootstrap()->bootstrap($app);
        \Yii::getLogger()->flush(true);

        $this->assertWarningContains('enablePrettyUrl');
    }

    public function testDoesNotWarnWhenPrettyUrlsEnabled(): void
    {
        $app = $this->createWebApp(hasDebugModule: false, debugModuleConfig: null, prettyUrls: true);

        new Bootstrap()->bootstrap($app);
        \Yii::getLogger()->flush(true);

        $this->assertWarningNotContains('enablePrettyUrl');
    }

    /**
     * @param array<string,mixed>|null $debugModuleConfig
     */
    private function createWebApp(bool $hasDebugModule, ?array $debugModuleConfig, bool $prettyUrls): Application
    {
        $urlManager = new UrlManager(['enablePrettyUrl' => $prettyUrls, 'showScriptName' => false]);

        $modules = $hasDebugModule && $debugModuleConfig !== null ? ['debug' => $debugModuleConfig] : [];

        $app = $this
            ->getMockBuilder(Application::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['hasModule', 'getModules', 'getModule', 'setModule', 'get'])
            ->getMock();

        $app->method('hasModule')->willReturnCallback(static fn(string $id): bool => $id === 'debug'
            ? $hasDebugModule
            : false);
        $app->method('getModules')->willReturn($modules);
        $app->method('get')->willReturnCallback(static function (string $id, bool $throw = true) use ($urlManager) {
            return $id === 'urlManager' ? $urlManager : null;
        });
        $fakeModule = $this->createMock(Module::class);
        $fakeModule->method('bootstrap');
        $app->method('getModule')->willReturn($fakeModule);

        return $app;
    }

    private function assertWarningContains(string $needle): void
    {
        $messages = array_filter($this->messages, static fn(array $m): bool => $m[1] === Logger::LEVEL_WARNING);

        foreach ($messages as $message) {
            if (str_contains($message[0], $needle)) {
                $this->addToAssertionCount(1);
                return;
            }
        }

        $this->fail(sprintf(
            'Expected a warning containing "%s"; got: %s',
            $needle,
            json_encode(array_column($messages, 0), JSON_UNESCAPED_SLASHES),
        ));
    }

    private function assertWarningNotContains(string $needle): void
    {
        foreach ($this->messages as $message) {
            if ($message[1] === Logger::LEVEL_WARNING && str_contains($message[0], $needle)) {
                $this->fail(sprintf('Unexpected warning containing "%s": %s', $needle, $message[0]));
            }
        }
        $this->addToAssertionCount(1);
    }
}
