<?php

declare(strict_types=1);

namespace AppDevPanel\Adapter\Yii2\EventListener;

use AppDevPanel\Kernel\Collector\Console\CommandCollector;
use AppDevPanel\Kernel\Collector\Console\ConsoleAppInfoCollector;
use AppDevPanel\Kernel\Collector\ExceptionCollector;
use AppDevPanel\Kernel\Debugger;
use AppDevPanel\Kernel\StartupContext;

/**
 * Maps Yii 2 console application events to the ADP Debugger lifecycle.
 *
 * EVENT_BEFORE_REQUEST -> Debugger::startup() + CommandCollector
 * EVENT_AFTER_REQUEST  -> CommandCollector + Debugger::shutdown()
 *
 * Yii 2 console uses EVENT_BEFORE_REQUEST / EVENT_AFTER_REQUEST
 * (same as web), not separate console events like Symfony.
 */
final class ConsoleListener
{
    private ?string $currentCommand = null;

    public function __construct(
        private readonly Debugger $debugger,
        private readonly ?CommandCollector $commandCollector = null,
        private readonly ?ConsoleAppInfoCollector $consoleAppInfoCollector = null,
        private readonly ?ExceptionCollector $exceptionCollector = null,
    ) {}

    public function onBeforeRequest(\yii\base\Event $event): void
    {
        $app = $event->sender;
        if (!$app instanceof \yii\console\Application) {
            return;
        }

        // Extract command name from request params
        $request = $app->getRequest();
        $params = $request->getParams();
        $this->currentCommand = $params[0] ?? 'unknown';

        $this->debugger->startup(StartupContext::forCommand($this->currentCommand));

        $this->consoleAppInfoCollector?->collect($event);
        $this->commandCollector?->collect($event);
    }

    public function onAfterRequest(\yii\base\Event $event): void
    {
        $app = $event->sender;
        if (!$app instanceof \yii\console\Application) {
            return;
        }

        // Capture any exception from error handler
        $errorHandler = $app->getErrorHandler();
        if ($errorHandler->exception !== null) {
            $this->exceptionCollector?->collect($errorHandler->exception);
        }

        $this->consoleAppInfoCollector?->collect($event);
        $this->commandCollector?->collect($event);

        // Force-flush Yii's Logger so buffered messages reach DebugLogTarget before storage flush.
        \Yii::getLogger()->flush(true);

        $this->debugger->shutdown();
    }
}
