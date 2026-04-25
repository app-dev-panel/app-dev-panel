<?php

declare(strict_types=1);

namespace App\commands;

use yii\console\Controller;
use yii\console\ExitCode;

/**
 * Test command that logs messages at various levels to verify ADP log collection.
 */
final class TestLoggingController extends Controller
{
    public function actionIndex(): int
    {
        $this->stdout("Logging test messages...\n");

        \Yii::info('This is an info message from the test command', 'application');
        \Yii::warning('This is a warning message from the test command', 'application');
        \Yii::error('This is an error message from the test command', 'application');

        $this->stdout("Done! Check the debug panel at /debug/entries\n");

        return ExitCode::OK;
    }
}
