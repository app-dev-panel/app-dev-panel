<?php

declare(strict_types=1);

namespace AppDevPanel\Adapter\Yii3\Tests\Unit\Config;

use AppDevPanel\Kernel\Collector\Web\WebAppInfoCollector;
use AppDevPanel\Kernel\Debugger;
use AppDevPanel\Kernel\StartupContext;
use PHPUnit\Framework\TestCase;
use Yiisoft\Yii\Http\Event\AfterEmit;
use Yiisoft\Yii\Http\Event\ApplicationStartup;
use Yiisoft\Yii\Http\Event\BeforeRequest;

/**
 * Tests that events-web.php event mapping is correct.
 *
 * Critical invariant: ApplicationStartup must NOT call Debugger::startup(generic).
 * If it did, debug API requests intercepted by YiiApiMiddleware would still be
 * collected, because BeforeRequest never fires for middleware-intercepted requests,
 * so skipCollect would remain false when AfterEmit triggers Debugger::shutdown().
 */
final class EventsWebConfigTest extends TestCase
{
    private array $events;

    protected function setUp(): void
    {
        $params = [
            'app-dev-panel/yii3' => [
                'enabled' => true,
            ],
        ];
        $this->events = (static function () use ($params): array {
            return require dirname(__DIR__, 3) . '/config/events-web.php';
        })();
    }

    public function testApplicationStartupDoesNotCallDebuggerStartup(): void
    {
        $this->assertArrayHasKey(ApplicationStartup::class, $this->events);
        $handlers = $this->events[ApplicationStartup::class];

        foreach ($handlers as $handler) {
            if (is_array($handler) && $handler[0] === Debugger::class) {
                $this->fail(
                    'ApplicationStartup must not reference Debugger — startup should only happen in BeforeRequest',
                );
            }

            if ($handler instanceof \Closure) {
                $ref = new \ReflectionFunction($handler);
                $params = $ref->getParameters();
                foreach ($params as $param) {
                    $type = $param->getType();
                    if ($type instanceof \ReflectionNamedType && $type->getName() === Debugger::class) {
                        $this->fail('ApplicationStartup closure must not inject Debugger — '
                        . 'generic startup causes debug API requests to be collected');
                    }
                }
            }
        }

        $this->assertTrue(true, 'ApplicationStartup correctly avoids Debugger::startup()');
    }

    public function testApplicationStartupMarksApplicationStarted(): void
    {
        $handlers = $this->events[ApplicationStartup::class];

        $injectsWebAppInfoCollector = false;
        foreach ($handlers as $handler) {
            if ($handler instanceof \Closure) {
                $ref = new \ReflectionFunction($handler);
                foreach ($ref->getParameters() as $param) {
                    $type = $param->getType();
                    if ($type instanceof \ReflectionNamedType && $type->getName() === WebAppInfoCollector::class) {
                        $injectsWebAppInfoCollector = true;
                        break 2;
                    }
                }
            }
        }

        $this->assertTrue(
            $injectsWebAppInfoCollector,
            'ApplicationStartup must still call WebAppInfoCollector::markApplicationStarted()',
        );
    }

    public function testBeforeRequestCallsDebuggerStartupWithRequestContext(): void
    {
        $this->assertArrayHasKey(BeforeRequest::class, $this->events);
        $handlers = $this->events[BeforeRequest::class];

        $injectsDebugger = false;
        foreach ($handlers as $handler) {
            if ($handler instanceof \Closure) {
                $ref = new \ReflectionFunction($handler);
                foreach ($ref->getParameters() as $param) {
                    $type = $param->getType();
                    if ($type instanceof \ReflectionNamedType && $type->getName() === Debugger::class) {
                        $injectsDebugger = true;
                        break 2;
                    }
                }
            }
        }

        $this->assertTrue($injectsDebugger, 'BeforeRequest must call Debugger::startup() with request context');
    }

    public function testAfterEmitCallsDebuggerShutdown(): void
    {
        $this->assertArrayHasKey(AfterEmit::class, $this->events);
        $handlers = $this->events[AfterEmit::class];

        $callsShutdown = false;
        foreach ($handlers as $handler) {
            if (is_array($handler) && $handler[0] === Debugger::class && $handler[1] === 'shutdown') {
                $callsShutdown = true;
                break;
            }
        }

        $this->assertTrue($callsShutdown, 'AfterEmit must call Debugger::shutdown()');
    }

    public function testDisabledPanelReturnsEmptyEvents(): void
    {
        $params = [
            'app-dev-panel/yii3' => [
                'enabled' => false,
            ],
        ];
        $events = (static function () use ($params): array {
            return require dirname(__DIR__, 3) . '/config/events-web.php';
        })();

        $this->assertSame([], $events);
    }
}
