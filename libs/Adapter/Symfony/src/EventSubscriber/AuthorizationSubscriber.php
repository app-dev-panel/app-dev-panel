<?php

declare(strict_types=1);

namespace AppDevPanel\Adapter\Symfony\EventSubscriber;

use AppDevPanel\Kernel\Collector\AuthorizationCollector;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Security\Core\Authentication\Token\SwitchUserToken;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;
use Symfony\Component\Security\Core\Event\VoteEvent;
use Symfony\Component\Security\Http\Event\LoginFailureEvent;
use Symfony\Component\Security\Http\Event\LoginSuccessEvent;
use Symfony\Component\Security\Http\Event\LogoutEvent;
use Symfony\Component\Security\Http\Event\SwitchUserEvent;
use Symfony\Component\Security\Http\Event\TokenDeauthenticatedEvent;

/**
 * Listens to Symfony Security events and feeds AuthorizationCollector.
 *
 * Captures:
 * - Login success/failure → authentication events + user identity
 * - Logout → authentication events
 * - Switch user (impersonation) → impersonation data
 * - Authorization votes → access decisions
 *
 * Requires symfony/security-bundle. When not installed, the subscriber
 * is not registered (guarded by class_exists check in AppDevPanelExtension).
 */
final class AuthorizationSubscriber implements EventSubscriberInterface
{
    /** @var array<string, array{attribute: string, subject: string, voters: list<array{voter: string, result: string}>}> */
    private array $pendingDecisions = [];

    public function __construct(
        private readonly AuthorizationCollector $collector,
    ) {}

    public static function getSubscribedEvents(): array
    {
        return [
            LoginSuccessEvent::class => ['onLoginSuccess', 0],
            LoginFailureEvent::class => ['onLoginFailure', 0],
            LogoutEvent::class => ['onLogout', 0],
            SwitchUserEvent::class => ['onSwitchUser', 0],
            'debug.security.authorization.vote' => ['onVote', 0],
        ];
    }

    public function onLoginSuccess(LoginSuccessEvent $event): void
    {
        $token = $event->getAuthenticatedToken();
        $user = $token->getUserIdentifier();
        $roles = array_map(static fn($role): string => $role instanceof \Stringable
            ? (string) $role
            : $role, $token->getRoleNames());

        $this->collector->collectUser($user, $roles, true);
        $this->collector->collectFirewall($event->getFirewallName());
        $this->collector->collectAuthenticationEvent('login', $event->getFirewallName(), 'success', [
            'authenticator' => $event->getAuthenticator()::class,
        ]);

        $tokenClass = new \ReflectionClass($token)->getShortName();
        $this->collector->collectToken($tokenClass, [
            'class' => $token::class,
        ]);

        if ($token instanceof SwitchUserToken) {
            $originalUser = $token->getOriginalToken()->getUserIdentifier();
            $this->collector->collectImpersonation($originalUser, $user);
        }
    }

    public function onLoginFailure(LoginFailureEvent $event): void
    {
        $this->collector->collectAuthenticationEvent('login', $event->getFirewallName(), 'failure', [
            'exception' => $event->getException()::class,
            'message' => $event->getException()->getMessage(),
        ]);
    }

    public function onLogout(LogoutEvent $event): void
    {
        $token = $event->getToken();
        $firewallName = $event->getFirewallName();

        $this->collector->collectAuthenticationEvent(
            'logout',
            $firewallName,
            'success',
            ['user' => $token?->getUserIdentifier()],
        );
    }

    public function onSwitchUser(SwitchUserEvent $event): void
    {
        $targetUser = $event->getTargetUser()->getUserIdentifier();
        $token = $event->getToken();
        $originalUser = $token?->getUserIdentifier() ?? 'unknown';

        $this->collector->collectImpersonation($originalUser, $targetUser);
        $this->collector->collectAuthenticationEvent('switch_user', 'security', 'success', [
            'original' => $originalUser,
            'target' => $targetUser,
        ]);
    }

    public function onVote(VoteEvent $event): void
    {
        $attribute = (string) ($event->getAttributes()[0] ?? 'unknown');
        $subject = $event->getSubject();
        $subjectStr = $this->describeSubject($subject);
        $voter = $event->getVoter();
        $vote = $event->getVote();

        $resultStr = match ($vote) {
            VoterInterface::ACCESS_GRANTED => 'ACCESS_GRANTED',
            VoterInterface::ACCESS_DENIED => 'ACCESS_DENIED',
            default => 'ACCESS_ABSTAIN',
        };

        $key = $attribute . '|' . $subjectStr;

        if (!isset($this->pendingDecisions[$key])) {
            $this->pendingDecisions[$key] = [
                'attribute' => $attribute,
                'subject' => $subjectStr,
                'voters' => [],
            ];
        }

        $this->pendingDecisions[$key]['voters'][] = [
            'voter' => $voter::class,
            'result' => $resultStr,
        ];

        // Flush once a voter grants or denies
        if ($vote !== VoterInterface::ACCESS_ABSTAIN) {
            $decision = $this->pendingDecisions[$key];
            $this->collector->logAccessDecision(
                $decision['attribute'],
                $decision['subject'],
                $resultStr,
                $decision['voters'],
            );
            unset($this->pendingDecisions[$key]);
        }
    }

    private function describeSubject(mixed $subject): string
    {
        if ($subject === null) {
            return 'null';
        }

        if (is_object($subject)) {
            return $subject::class;
        }

        if (is_string($subject)) {
            return $subject;
        }

        return get_debug_type($subject);
    }
}
