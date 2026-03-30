---
title: Security & Authorization
---

# Security & Authorization

ADP captures authentication and authorization data from your application: user identity, roles, tokens, login/logout events, access decisions, and impersonation. The inspector also provides a live view of your security configuration.

## AuthorizationCollector

`AppDevPanel\Kernel\Collector\AuthorizationCollector` captures runtime security data. It implements `SummaryCollectorInterface` for summary display in the debug entry list.

### Collected Data

| Field | Type | Description |
|-------|------|-------------|
| `username` | `?string` | Authenticated user identifier |
| `roles` | `string[]` | Assigned roles |
| `effectiveRoles` | `string[]` | Resolved roles (from hierarchy) |
| `firewallName` | `?string` | Active firewall/guard name |
| `authenticated` | `bool` | Whether the user is authenticated |
| `token` | `?{type, attributes, expiresAt}` | Auth token info (JWT, session, API key) |
| `impersonation` | `?{originalUser, impersonatedUser}` | User switching data |
| `guards` | `array` | Guard/firewall configurations |
| `roleHierarchy` | `array<string, string[]>` | Role inheritance map |
| `authenticationEvents` | `array` | Login, logout, failure events with timing |
| `accessDecisions` | `array` | Authorization checks with voters and results |

### Collection Methods

```php
$collector->collectUser('admin@example.com', ['ROLE_ADMIN'], true);
$collector->collectFirewall('main');
$collector->collectToken('jwt', ['sub' => '123'], '2026-12-31T23:59:59Z');
$collector->collectImpersonation('admin', 'user@example.com');
$collector->collectGuard('web', 'users', ['driver' => 'session']);
$collector->collectRoleHierarchy(['ROLE_ADMIN' => ['ROLE_USER', 'ROLE_EDITOR']]);
$collector->collectEffectiveRoles(['ROLE_ADMIN', 'ROLE_USER', 'ROLE_EDITOR']);
$collector->collectAuthenticationEvent('login', 'form_login', 'success', ['ip' => '127.0.0.1']);
$collector->logAccessDecision('EDIT', 'App\\Entity\\Post', 'ACCESS_DENIED', $voters, 0.002, ['route' => '/admin']);
```

## Adapter Wiring

Each adapter automatically feeds AuthorizationCollector from the framework's native auth system.

### Symfony

`AuthorizationSubscriber` listens to Symfony Security events. Requires `symfony/security-http`.

| Event | Data Captured |
|-------|---------------|
| `LoginSuccessEvent` | User identity, roles, firewall, token type, impersonation |
| `LoginFailureEvent` | Failed auth event with exception details |
| `LogoutEvent` | Logout event |
| `SwitchUserEvent` | Impersonation data |
| `VoteEvent` | Access decisions with voter results |

::: tip
Enable in `config/packages/app_dev_panel.yaml`:
```yaml
app_dev_panel:
    collectors:
        security: true
```
:::

### Laravel

`AuthorizationListener` listens to Laravel Auth events.

| Event | Data Captured |
|-------|---------------|
| `Illuminate\Auth\Events\Authenticated` | User identity, guard name |
| `Illuminate\Auth\Events\Login` | Login event, remember flag |
| `Illuminate\Auth\Events\Logout` | Logout event |
| `Illuminate\Auth\Events\Failed` | Failed auth with credential keys |
| `Illuminate\Auth\Events\OtherDeviceLogout` | Other device logout |

### Yii 2

`AuthorizationListener` hooks into `yii\web\User` events.

| Event | Data Captured |
|-------|---------------|
| `User::EVENT_AFTER_LOGIN` | User ID, duration, cookie-based flag |
| `User::EVENT_AFTER_LOGOUT` | Logout event with user ID |
| `EVENT_BEFORE_REQUEST` | Current session user on each request |

### Yiisoft (Yii 3)

AuthorizationCollector is registered in DI but requires manual calls — Yii 3 has no standardized auth event system.

## Authorization Inspector

The inspector provides a live view of security configuration via `GET /inspect/api/authorization`.

### Response

```json
{
  "guards": [
    {"name": "web", "provider": "users", "config": {"driver": "session"}}
  ],
  "roleHierarchy": {
    "ROLE_ADMIN": ["ROLE_USER", "ROLE_EDITOR"]
  },
  "voters": [
    {"name": "RoleVoter", "type": "voter", "priority": 255}
  ],
  "config": {
    "access_decision_manager": {"strategy": "affirmative"}
  }
}
```

Adapters implement `AuthorizationConfigProviderInterface` to supply this data. Default: `NullAuthorizationConfigProvider` (empty arrays).

## Frontend

### AuthorizationPanel (Debug)

Displays per-request security data in the debug view:

- User identity card (username, status, firewall, roles, effective roles, token)
- Impersonation banner (when active)
- Authentication events timeline (login, logout, failure)
- Access decisions table (expandable, shows voters and context)

### AuthorizationPage (Inspector)

Located at `/inspector/authorization`. Displays live security configuration:

- Guards table (name, provider, config)
- Role hierarchy tree (role → inherited roles)
- Voters/policies table (name, type, priority)
- Security configuration JSON
