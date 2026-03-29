<?php

declare(strict_types=1);

namespace AppDevPanel\Adapter\Yii2\EventListener;

use AppDevPanel\Kernel\Collector\SecurityCollector;
use yii\base\Event;
use yii\web\User;
use yii\web\UserEvent;

/**
 * Listens for Yii 2 User component events and feeds SecurityCollector.
 *
 * Captures:
 * - afterLogin → user identity + authentication event
 * - afterLogout → authentication event
 * - afterLogin with identity → impersonation detection (via switchIdentity)
 *
 * Registered via Event::on() in Module::registerSecurityListeners().
 */
final class SecurityListener
{
    public function __construct(
        private readonly SecurityCollector $collector,
    ) {}

    public function onAfterLogin(UserEvent $event): void
    {
        $identity = $event->identity;

        if ($identity === null) {
            return;
        }

        $userId = (string) $identity->getId();
        $this->collector->collectUser($userId, [], true);
        $this->collector->collectAuthenticationEvent('login', 'yii2_user', 'success', [
            'duration' => $event->duration,
            'cookieBased' => $event->cookieBased ?? false,
        ]);
    }

    public function onAfterLogout(UserEvent $event): void
    {
        $identity = $event->identity;
        $userId = $identity !== null ? (string) $identity->getId() : null;

        $this->collector->collectAuthenticationEvent('logout', 'yii2_user', 'success', ['user' => $userId]);
    }

    /**
     * Collect user identity on each request (session-based authentication).
     *
     * Called from WebListener after request initialization, when the user
     * has already been authenticated via session/cookie.
     */
    public function collectCurrentUser(User $user): void
    {
        $identity = $user->getIdentity(false);
        if ($identity === null) {
            $this->collector->collectUser(null, [], false);
            return;
        }

        $userId = (string) $identity->getId();
        $this->collector->collectUser($userId, [], true);
    }

    public function register(): void
    {
        Event::on(User::class, User::EVENT_AFTER_LOGIN, [$this, 'onAfterLogin']);
        Event::on(User::class, User::EVENT_AFTER_LOGOUT, [$this, 'onAfterLogout']);
    }
}
