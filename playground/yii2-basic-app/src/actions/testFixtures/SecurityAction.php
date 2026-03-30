<?php

declare(strict_types=1);

namespace App\actions\testFixtures;

use AppDevPanel\Kernel\Collector\AuthorizationCollector;
use yii\base\Action;

final class SecurityAction extends Action
{
    public function run(): array
    {
        /** @var \AppDevPanel\Adapter\Yii2\Module $module */
        $module = \Yii::$app->getModule('debug-panel');

        /** @var AuthorizationCollector|null $authorizationCollector */
        $authorizationCollector = $module->getCollector(AuthorizationCollector::class);

        if ($authorizationCollector === null) {
            return [
                'fixture' => 'security:basic',
                'status' => 'error',
                'message' => 'AuthorizationCollector not found',
            ];
        }

        $authorizationCollector->collectUser('admin@example.com', ['ROLE_ADMIN', 'ROLE_USER'], true);
        $authorizationCollector->collectFirewall('main');
        $authorizationCollector->collectToken('jwt', ['sub' => '123', 'iss' => 'app'], '2026-12-31T23:59:59Z');
        $authorizationCollector->collectGuard('web', 'users', ['driver' => 'session']);
        $authorizationCollector->collectRoleHierarchy(['ROLE_ADMIN' => ['ROLE_USER', 'ROLE_EDITOR']]);
        $authorizationCollector->collectEffectiveRoles(['ROLE_ADMIN', 'ROLE_USER', 'ROLE_EDITOR']);
        $authorizationCollector->collectAuthenticationEvent('login', 'form_login', 'success', ['ip' => '127.0.0.1']);

        $authorizationCollector->logAccessDecision(
            'ROLE_ADMIN',
            'App\\Entity\\User',
            'ACCESS_GRANTED',
            [['voter' => 'RoleVoter', 'result' => 'ACCESS_GRANTED']],
            0.002,
            ['route' => '/admin'],
        );
        $authorizationCollector->logAccessDecision(
            'EDIT',
            'App\\Entity\\Post',
            'ACCESS_DENIED',
            [['voter' => 'PostVoter', 'result' => 'ACCESS_DENIED']],
            0.001,
        );

        return ['fixture' => 'security:basic', 'status' => 'ok'];
    }
}
