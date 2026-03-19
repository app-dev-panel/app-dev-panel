<?php

declare(strict_types=1);

namespace AppDevPanel\Adapter\Yii2\Controller;

use AppDevPanel\Kernel\Debugger;
use AppDevPanel\Kernel\Storage\StorageInterface;
use yii\console\Controller;
use yii\console\ExitCode;
use yii\helpers\Console;

/**
 * Clear debug storage data.
 *
 * Yii 2 equivalent of the Symfony Console-based DebugResetCommand from libs/Cli.
 */
final class DebugResetController extends Controller
{
    public function __construct(
        $id,
        $module,
        private readonly StorageInterface $storage,
        private readonly Debugger $debugger,
        $config = [],
    ) {
        parent::__construct($id, $module, $config);
    }

    /**
     * Clear all stored debug data.
     */
    public function actionIndex(): int
    {
        $this->debugger->stop();
        $this->storage->clear();

        Console::stdout("Debug storage cleared.\n");

        return ExitCode::OK;
    }
}
