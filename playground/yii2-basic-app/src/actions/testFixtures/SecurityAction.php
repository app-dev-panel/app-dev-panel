<?php

declare(strict_types=1);

namespace App\actions\testFixtures;

use AppDevPanel\Kernel\Collector\SecurityCollector;
use yii\base\Action;

final class SecurityAction extends Action
{
    public function run(): array
    {
        /** @var \AppDevPanel\Adapter\Yii2\Module $module */
        $module = \Yii::$app->getModule('debug-panel');

        /** @var SecurityCollector|null $securityCollector */
        $securityCollector = $module->getCollector(SecurityCollector::class);

        if ($securityCollector === null) {
            return ['fixture' => 'security:basic', 'status' => 'error', 'message' => 'SecurityCollector not found'];
        }

        $securityCollector->collectUser('admin@example.com', ['ROLE_ADMIN', 'ROLE_USER'], true);
        $securityCollector->collectFirewall('main');
        $securityCollector->collectToken('jwt', ['sub' => '123', 'iss' => 'app'], '2026-12-31T23:59:59Z');
        $securityCollector->collectGuard('web', 'users', ['driver' => 'session']);
        $securityCollector->collectRoleHierarchy(['ROLE_ADMIN' => ['ROLE_USER', 'ROLE_EDITOR']]);
        $securityCollector->collectEffectiveRoles(['ROLE_ADMIN', 'ROLE_USER', 'ROLE_EDITOR']);
        $securityCollector->collectAuthenticationEvent('login', 'form_login', 'success', ['ip' => '127.0.0.1']);

        $securityCollector->logAccessDecision(
            'ROLE_ADMIN',
            'App\\Entity\\User',
            'ACCESS_GRANTED',
            [['voter' => 'RoleVoter', 'result' => 'ACCESS_GRANTED']],
            0.002,
            ['route' => '/admin'],
        );
        $securityCollector->logAccessDecision(
            'EDIT',
            'App\\Entity\\Post',
            'ACCESS_DENIED',
            [['voter' => 'PostVoter', 'result' => 'ACCESS_DENIED']],
            0.001,
        );

        return ['fixture' => 'security:basic', 'status' => 'ok'];
    }
}
