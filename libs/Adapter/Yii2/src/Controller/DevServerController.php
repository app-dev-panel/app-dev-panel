<?php

declare(strict_types=1);

declare(ticks=1);

namespace AppDevPanel\Adapter\Yii2\Controller;

use AppDevPanel\Kernel\DebugServer\Connection;
use AppDevPanel\Kernel\DebugServer\SocketReader;
use yii\console\Controller;
use yii\console\ExitCode;

/**
 * Start the ADP debug socket server for real-time Live Feed.
 *
 * Yii 2 equivalent of the Symfony Console-based DebugServerCommand from libs/Cli.
 */
final class DevServerController extends Controller
{
    public function actionIndex(): int
    {
        if (!extension_loaded('sockets')) {
            $this->stderr("The 'sockets' PHP extension is required to run the debug server.\n");
            return ExitCode::SOFTWARE;
        }

        $connection = Connection::create();
        $connection->bind();
        $uri = $connection->getUri();

        $this->stdout("ADP Debug Server\n");
        $this->stdout("Listening on {$uri}\n");
        $this->stdout("Press Ctrl+C to stop.\n\n");

        $running = true;
        if (function_exists('pcntl_signal')) {
            pcntl_signal(SIGINT, static function () use (&$running): void {
                $running = false;
            });
        }

        $reader = new SocketReader($connection->getSocket());

        foreach ($reader->read() as $message) {
            if (!$running) {
                break;
            }

            $typeLabel = match ($message['type']) {
                Connection::MESSAGE_TYPE_VAR_DUMPER => 'DUMP',
                Connection::MESSAGE_TYPE_LOGGER => 'LOG ',
                Connection::MESSAGE_TYPE_ENTRY_CREATED => 'NEW ',
                default => '??? ',
            };

            $this->stdout("[{$typeLabel}] {$message['data']}\n");
        }

        $connection->close();
        $this->stdout("\nServer stopped.\n");

        return ExitCode::OK;
    }
}
