<?php

declare(strict_types=1);

namespace App\controllers;

use yii\web\Controller;
use yii\web\Response;

/**
 * ADP Testing Scenarios — routes to standalone action classes.
 * Each action lives in App\actions\testFixtures\*Action.
 */
final class TestFixturesController extends Controller
{
    public function beforeAction($action): bool
    {
        \Yii::$app->response->format = Response::FORMAT_JSON;
        return parent::beforeAction($action);
    }

    public function actions(): array
    {
        return [
            'logs' => \App\actions\testFixtures\LogsAction::class,
            'logs-context' => \App\actions\testFixtures\LogsContextAction::class,
            'events' => \App\actions\testFixtures\EventsAction::class,
            'dump' => \App\actions\testFixtures\DumpAction::class,
            'timeline' => \App\actions\testFixtures\TimelineAction::class,
            'request-info' => \App\actions\testFixtures\RequestInfoAction::class,
            'exception' => \App\actions\testFixtures\ExceptionAction::class,
            'exception-chained' => \App\actions\testFixtures\ExceptionChainedAction::class,
            'multi' => \App\actions\testFixtures\MultiAction::class,
            'logs-heavy' => \App\actions\testFixtures\LogsHeavyAction::class,
            'http-client' => \App\actions\testFixtures\HttpClientAction::class,
            'filesystem' => \App\actions\testFixtures\FilesystemAction::class,
            'filesystem-streams' => \App\actions\testFixtures\FileStreamAction::class,
            'database' => \App\actions\testFixtures\DatabaseAction::class,
            'mailer' => \App\actions\testFixtures\MailerAction::class,
            'cache' => \App\actions\testFixtures\CacheAction::class,
            'cache-heavy' => \App\actions\testFixtures\CacheHeavyAction::class,
            'router' => \App\actions\testFixtures\RouterAction::class,
            'validator' => \App\actions\testFixtures\ValidatorAction::class,
            'messenger' => \App\actions\testFixtures\MessengerAction::class,
            'opentelemetry' => \App\actions\testFixtures\OpenTelemetryAction::class,
            'reset' => \App\actions\testFixtures\ResetAction::class,
            'reset-cli' => \App\actions\testFixtures\ResetCliAction::class,
        ];
    }
}
