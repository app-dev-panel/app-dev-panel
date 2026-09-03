<?php

declare(strict_types=1);

namespace AppDevPanel\Tests\Extension;

use AppDevPanel\Kernel\Collector\Stream\FilesystemStreamProxy;
use AppDevPanel\Kernel\Collector\Stream\HttpStreamProxy;
use PHPUnit\Event\Test\Finished;
use PHPUnit\Event\Test\FinishedSubscriber;
use PHPUnit\Runner\Extension\Extension;
use PHPUnit\Runner\Extension\Facade;
use PHPUnit\Runner\Extension\ParameterCollection;
use PHPUnit\TextUI\Configuration\Configuration;
use RuntimeException;

/**
 * Fails the run when a test leaves ADP's global stream proxies (`file`, `http`, `https`)
 * registered. A leaked proxy silently changes filesystem semantics for every later test
 * (random order made this show up as unrelated symlink/rmdir failures), so the leak itself
 * is the defect: the guard restores the native wrappers and names the offending test.
 */
final class StreamWrapperLeakGuard implements Extension
{
    public function bootstrap(Configuration $configuration, Facade $facade, ParameterCollection $parameters): void
    {
        $facade->registerSubscriber(new class implements FinishedSubscriber {
            public function notify(Finished $event): void
            {
                $leaked = [];
                if (FilesystemStreamProxy::$registered) {
                    FilesystemStreamProxy::unregister();
                    $leaked[] = 'file';
                }
                if (HttpStreamProxy::$registered) {
                    HttpStreamProxy::unregister();
                    $leaked[] = 'http/https';
                }
                if ($leaked !== []) {
                    throw new RuntimeException(sprintf(
                        '%s left the ADP stream proxy registered for: %s. Call shutdown()/unregister() in a finally block.',
                        $event->test()->id(),
                        implode(', ', $leaked),
                    ));
                }
            }
        });
    }
}
