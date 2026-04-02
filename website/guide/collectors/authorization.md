---
title: Authorization Collector
description: "ADP Authorization Collector records access decisions, voter results, roles, and permission checks."
---

# Authorization Collector

Captures authentication and authorization data — user identity, roles, tokens, access decisions, guards, role hierarchy, and impersonation status.

![Authorization Collector panel](/images/collectors/authorization.png)

## What It Captures

| Field | Description |
|-------|-------------|
| `username` | Authenticated user identifier |
| `roles` | Assigned roles |
| `effectiveRoles` | Roles after hierarchy resolution |
| `authenticated` | Whether the user is authenticated |
| `firewallName` | Active firewall/guard name |
| `token` | Auth token details (type, attributes, expiration) |
| `impersonation` | Impersonation data (original and impersonated user) |
| `guards` | Registered authentication guards |
| `roleHierarchy` | Role inheritance tree |
| `authenticationEvents` | Login/logout/failure events |
| `accessDecisions` | Authorization check results (granted/denied) |

## Data Schema

```json
{
    "username": "admin@example.com",
    "roles": ["ROLE_ADMIN"],
    "effectiveRoles": ["ROLE_ADMIN", "ROLE_USER"],
    "firewallName": "main",
    "authenticated": true,
    "token": {
        "type": "Bearer",
        "attributes": {},
        "expiresAt": "2026-03-31T23:59:59+00:00"
    },
    "impersonation": null,
    "guards": [
        {"name": "main", "provider": "users", "config": {}}
    ],
    "roleHierarchy": {"ROLE_ADMIN": ["ROLE_USER"]},
    "authenticationEvents": [
        {"type": "login", "provider": "form", "result": "success", "time": 1711878000.1, "details": {}}
    ],
    "accessDecisions": [
        {"attribute": "ROLE_ADMIN", "subject": "route:/admin", "result": "granted", "voters": [...], "duration": 0.0001, "context": {}}
    ]
}
```

**Summary** (shown in debug entry list):

```json
{
    "authorization": {
        "username": "admin@example.com",
        "authenticated": true,
        "roles": ["ROLE_ADMIN"],
        "accessDecisions": {"total": 3, "granted": 3, "denied": 0},
        "authEvents": 1
    }
}
```

## Contract

```php
use AppDevPanel\Kernel\Collector\AuthorizationCollector;

$collector->collectUser(
    username: 'admin@example.com',
    roles: ['ROLE_ADMIN'],
    authenticated: true,
);
$collector->collectFirewall(firewallName: 'main');
$collector->collectToken(type: 'Bearer', attributes: [], expiresAt: '2026-03-31T23:59:59+00:00');
$collector->collectRoleHierarchy(hierarchy: ['ROLE_ADMIN' => ['ROLE_USER']]);
$collector->collectEffectiveRoles(effectiveRoles: ['ROLE_ADMIN', 'ROLE_USER']);

$collector->logAccessDecision(
    attribute: 'ROLE_ADMIN',
    subject: 'route:/admin',
    result: 'granted',
    voters: [...],
);
```

::: info
<class>\AppDevPanel\Kernel\Collector\AuthorizationCollector</class> implements <class>\AppDevPanel\Kernel\Collector\SummaryCollectorInterface</class>. It has no dependencies on other collectors.
:::

## How It Works

Framework adapters extract authentication state from the security component:
- **Symfony**: Security token, firewall, voter results via event listeners
- **Laravel**: Auth guards, Gate authorization checks
- **Yii 3**: Identity interface and RBAC system

## Debug Panel

- **User identity** — username, authentication status, roles
- **Access decisions** — list of authorization checks with granted/denied results
- **Role hierarchy** — visual role inheritance tree
- **Auth events** — login, logout, and failure events
- **Token details** — token type, attributes, and expiration
