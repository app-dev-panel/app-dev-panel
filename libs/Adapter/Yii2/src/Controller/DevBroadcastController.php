<?php

declare(strict_types=1);

namespace AppDevPanel\Adapter\Yii2\Controller;

use AppDevPanel\Kernel\DebugServer\Broadcaster;
use AppDevPanel\Kernel\DebugServer\Connection;
use yii\console\Controller;
use yii\console\ExitCode;

/**
 * Broadcast test messages to debug server clients for Live Feed testing.
 *
 * Yii 2 equivalent of the Symfony Console-based DebugServerBroadcastCommand from libs/Cli.
 */
final class DevBroadcastController extends Controller
{
    public string $message = 'Test message';

    public function options($actionID): array
    {
        return [...parent::options($actionID), 'message'];
    }

    public function optionAliases(): array
    {
        return [...parent::optionAliases(), 'm' => 'message'];
    }

    public function actionIndex(): int
    {
        $broadcaster = new Broadcaster();

        $this->stdout("ADP Debug Server — Broadcasting\n");

        $broadcaster->broadcast(Connection::MESSAGE_TYPE_LOGGER, $this->message);
        $broadcaster->broadcast(Connection::MESSAGE_TYPE_VAR_DUMPER, json_encode([
            '$data' => $this->message,
        ], JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE));

        $this->stdout("Broadcast complete: {$this->message}\n");

        return ExitCode::OK;
    }
}
