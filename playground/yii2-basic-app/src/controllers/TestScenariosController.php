<?php

declare(strict_types=1);

namespace App\controllers;

use yii\web\Controller;
use yii\web\Response;

/**
 * ADP Testing Scenarios — routes to standalone action classes.
 * Each action lives in App\actions\testScenarios\*Action.
 */
final class TestScenariosController extends Controller
{
    public function beforeAction($action): bool
    {
        \Yii::$app->response->format = Response::FORMAT_JSON;
        return parent::beforeAction($action);
    }

    public function actions(): array
    {
        return [
            'logs' => \App\actions\testScenarios\LogsAction::class,
            'logs-context' => \App\actions\testScenarios\LogsContextAction::class,
            'events' => \App\actions\testScenarios\EventsAction::class,
            'dump' => \App\actions\testScenarios\DumpAction::class,
            'timeline' => \App\actions\testScenarios\TimelineAction::class,
            'request-info' => \App\actions\testScenarios\RequestInfoAction::class,
            'exception' => \App\actions\testScenarios\ExceptionAction::class,
            'exception-chained' => \App\actions\testScenarios\ExceptionChainedAction::class,
            'multi' => \App\actions\testScenarios\MultiAction::class,
            'logs-heavy' => \App\actions\testScenarios\LogsHeavyAction::class,
            'http-client' => \App\actions\testScenarios\HttpClientAction::class,
            'filesystem' => \App\actions\testScenarios\FilesystemAction::class,
        ];
    }
}
