<?php

declare(strict_types=1);

namespace App\controllers;

use yii\web\Controller;
use yii\web\Response;

/**
 * ADP Testing Scenarios — endpoints that trigger specific collector behaviors.
 * Defined centrally in libs/Testing, implemented per-playground.
 */
final class TestScenariosController extends Controller
{
    public function beforeAction($action): bool
    {
        \Yii::$app->response->format = Response::FORMAT_JSON;
        return parent::beforeAction($action);
    }

    /**
     * Scenario: logs:basic — Emit info, warning, error logs.
     */
    public function actionLogs(): array
    {
        \Yii::info('Test log: info level message', 'application');
        \Yii::warning('Test log: warning level message', 'application');
        \Yii::error('Test log: error level message', 'application');

        return ['scenario' => 'logs:basic', 'status' => 'ok'];
    }

    /**
     * Scenario: logs:context — Log with structured context.
     */
    public function actionLogsContext(): array
    {
        \Yii::info('User action', 'application');

        return ['scenario' => 'logs:context', 'status' => 'ok'];
    }

    /**
     * Scenario: events:basic — Trigger an event.
     */
    public function actionEvents(): array
    {
        $event = new \yii\base\Event();
        $event->data = ['scenario' => 'events:basic'];
        \yii\base\Event::trigger(\yii\base\Application::class, 'adp.test.scenario', $event);

        return ['scenario' => 'events:basic', 'status' => 'ok'];
    }

    /**
     * Scenario: var-dumper:basic — Trigger a var dump.
     */
    public function actionDump(): array
    {
        // Yii2's VarDumper
        \yii\helpers\VarDumper::dump(['scenario' => 'var-dumper:basic', 'nested' => ['key' => 'value']], 10, false);

        return ['scenario' => 'var-dumper:basic', 'status' => 'ok'];
    }

    /**
     * Scenario: timeline:basic — Trigger timeline entries (logs produce timeline events).
     */
    public function actionTimeline(): array
    {
        \Yii::info('Timeline step 1: start', 'application');
        usleep(10_000);
        \Yii::info('Timeline step 2: processing', 'application');
        usleep(10_000);
        \Yii::info('Timeline step 3: done', 'application');

        return ['scenario' => 'timeline:basic', 'status' => 'ok'];
    }

    /**
     * Scenario: request:basic — Normal request (request collector captures automatically).
     */
    public function actionRequestInfo(): array
    {
        return ['scenario' => 'request:basic', 'status' => 'ok'];
    }

    /**
     * Scenario: exception:runtime — Throw a RuntimeException.
     */
    public function actionException(): never
    {
        throw new \RuntimeException('ADP test scenario exception');
    }

    /**
     * Scenario: exception:chained — Throw with a previous cause.
     */
    public function actionExceptionChained(): never
    {
        try {
            throw new \InvalidArgumentException('Original cause');
        } catch (\InvalidArgumentException $e) {
            throw new \RuntimeException('Wrapper exception', 0, $e);
        }
    }

    /**
     * Scenario: multi:logs-and-events — Multiple collectors in one request.
     */
    public function actionMulti(): array
    {
        \Yii::info('Multi scenario: log entry 1', 'application');
        $event = new \yii\base\Event();
        $event->data = ['scenario' => 'multi:step'];
        \yii\base\Event::trigger(\yii\base\Application::class, 'adp.test.multi', $event);
        \Yii::info('Multi scenario: log entry 2', 'application');

        return ['scenario' => 'multi:logs-and-events', 'status' => 'ok'];
    }

    /**
     * Scenario: logs:heavy — Many log entries in one request.
     */
    public function actionLogsHeavy(): array
    {
        for ($i = 1; $i <= 100; $i++) {
            \Yii::info(sprintf('Heavy log entry #%d', $i), 'application');
        }

        return ['scenario' => 'logs:heavy', 'status' => 'ok', 'count' => 100];
    }

    /**
     * Scenario: http-client:basic — HTTP client request (stub).
     */
    public function actionHttpClient(): array
    {
        \Yii::info('HTTP client scenario: would make external request', 'application');

        return ['scenario' => 'http-client:basic', 'status' => 'ok'];
    }

    /**
     * Scenario: filesystem:basic — Trigger filesystem operations.
     */
    public function actionFilesystem(): array
    {
        $tmpFile = sys_get_temp_dir() . '/adp-test-scenario-' . uniqid() . '.txt';
        file_put_contents($tmpFile, 'ADP filesystem test scenario');
        file_get_contents($tmpFile);
        unlink($tmpFile);

        return ['scenario' => 'filesystem:basic', 'status' => 'ok'];
    }
}
