<?php

declare(strict_types=1);

namespace App\actions\testFixtures;

use AppDevPanel\Kernel\Collector\RouterCollector;
use yii\base\Action;

final class RouterAction extends Action
{
    public function run(): array
    {
        /** @var \AppDevPanel\Adapter\Yii2\Module $module */
        $module = \Yii::$app->getModule('debug-panel');

        /** @var RouterCollector|null $routerCollector */
        $routerCollector = $module->getCollector(RouterCollector::class);

        if ($routerCollector === null) {
            return ['fixture' => 'router:basic', 'status' => 'error', 'message' => 'RouterCollector not found'];
        }

        $routerCollector->collectMatchedRoute([
            'name' => 'test-router',
            'pattern' => '/test/fixtures/router',
            'arguments' => [],
            'uri' => '/test/fixtures/router',
            'host' => null,
            'action' => self::class,
            'middlewares' => [],
            'matchTime' => 0.123,
        ]);
        $routerCollector->collectRoutes(routes: [
            ['name' => 'home', 'pattern' => '/', 'methods' => ['GET']],
            ['name' => 'test-router', 'pattern' => '/test/fixtures/router', 'methods' => ['GET']],
            ['name' => 'test-logs', 'pattern' => '/test/fixtures/logs', 'methods' => ['GET']],
        ]);

        return ['fixture' => 'router:basic', 'status' => 'ok'];
    }
}
