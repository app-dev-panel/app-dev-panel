<?php

declare(strict_types=1);

namespace AppDevPanel\Adapter\Laravel\EventListener;

use AppDevPanel\Kernel\Collector\SecurityCollector;
use Illuminate\Auth\Events\Authenticated;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Auth\Events\OtherDeviceLogout;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Events\Dispatcher;

/**
 * Listens for Laravel Auth events and feeds the SecurityCollector.
 *
 * Captures:
 * - Login → user identity + authentication event
 * - Logout → authentication event
 * - Failed → failed authentication event
 * - Authenticated → user identity (session-based)
 * - OtherDeviceLogout → authentication event
 */
final class SecurityListener
{
    /** @var \Closure(): SecurityCollector */
    private \Closure $collectorFactory;

    /**
     * @param \Closure(): SecurityCollector $collectorFactory
     */
    public function __construct(\Closure $collectorFactory)
    {
        $this->collectorFactory = $collectorFactory;
    }

    public function register(Dispatcher $events): void
    {
        $events->listen(Authenticated::class, function (Authenticated $event): void {
            $collector = ($this->collectorFactory)();
            /** @var Authenticatable $user */
            $user = $event->user;
            $collector->collectUser($this->getUserIdentifier($user), $this->getUserRoles($user), true);
            $collector->collectFirewall((string) $event->guard);
        });

        $events->listen(Login::class, function (Login $event): void {
            $collector = ($this->collectorFactory)();
            /** @var Authenticatable $user */
            $user = $event->user;
            $guard = (string) $event->guard;
            $collector->collectUser($this->getUserIdentifier($user), $this->getUserRoles($user), true);
            $collector->collectFirewall($guard);
            $collector->collectAuthenticationEvent('login', $guard, 'success', ['remember' => $event->remember]);
        });

        $events->listen(Logout::class, function (Logout $event): void {
            $collector = ($this->collectorFactory)();
            $collector->collectAuthenticationEvent(
                'logout',
                (string) $event->guard,
                'success',
                ['user' => $this->getUserIdentifier($event->user)],
            );
        });

        $events->listen(Failed::class, function (Failed $event): void {
            $collector = ($this->collectorFactory)();
            $collector->collectAuthenticationEvent(
                'login',
                (string) $event->guard,
                'failure',
                ['credentials' => array_keys($event->credentials ?? [])],
            );
        });

        if (class_exists(OtherDeviceLogout::class)) {
            $events->listen(OtherDeviceLogout::class, function (OtherDeviceLogout $event): void {
                $collector = ($this->collectorFactory)();
                $collector->collectAuthenticationEvent(
                    'other_device_logout',
                    (string) $event->guard,
                    'success',
                    ['user' => $this->getUserIdentifier($event->user)],
                );
            });
        }
    }

    private function getUserIdentifier(?Authenticatable $user): ?string
    {
        if ($user === null) {
            return null;
        }

        return (string) $user->getAuthIdentifier();
    }

    /**
     * @return string[]
     */
    private function getUserRoles(?Authenticatable $user): array
    {
        if ($user === null) {
            return [];
        }

        // Laravel doesn't have a standard roles interface, but many implementations
        // use a `getRoleNames()` or `roles` relationship.
        if (method_exists($user, 'getRoleNames')) {
            return $user->getRoleNames()->toArray();
        }

        return [];
    }
}
