---
title: Authorization Inspector
description: "ADP Authorization Inspector shows security configuration: roles, voters, firewalls, and access rules."
---

# Authorization Inspector

Inspect the security and authorization configuration of your application.

![Authorization Inspector](/images/inspector/authorization.png)

## What It Shows

| Section | Description |
|---------|-------------|
| Guards | Security guards/firewalls configuration |
| Role hierarchy | Role inheritance tree |
| Voters | Authorization voters/policies registered |
| Security config | Full security configuration dump |

## API Endpoints

| Method | Path | Description |
|--------|------|-------------|
| GET | `/inspect/api/authorization` | Guards, role hierarchy, voters, security config |

## Adapter Support

| Adapter | Provider | Notes |
|---------|----------|-------|
| Yii 3 | <class>AppDevPanel\Adapter\Yii3\Inspector\Yii3AuthorizationConfigProvider</class> | Reads RBAC / User / Auth / Access services (all optional) |
| Symfony | <class>AppDevPanel\Adapter\Symfony\Inspector\SymfonyAuthorizationConfigProvider</class> | Reads `security.firewalls`, `security.role_hierarchy`, `security.voter`-tagged services; registered automatically when `symfony/security-bundle` is installed |
| Laravel | <class>AppDevPanel\Adapter\Laravel\Inspector\LaravelAuthorizationConfigProvider</class> | Reads `config/auth.php` guards and providers; lists `Gate` abilities and policies via reflection |
| Yii 2 | <class>AppDevPanel\Adapter\Yii2\Inspector\Yii2AuthorizationConfigProvider</class> | Reads the `user` component (identity class, login/session settings) and, when configured, `authManager` roles/permissions/rules |
| Cycle | <class>AppDevPanel\Api\Inspector\Authorization\NullAuthorizationConfigProvider</class> | ORM-only adapter — returns an empty configuration |

### Yii 3 — per-section requirements

Each section of the Yii 3 response is populated only when the relevant package is in the container (listed under `suggest` in the adapter's `composer.json`):

| Section | Required package |
|---------|------------------|
| Guards | `yiisoft/auth` |
| Role hierarchy | `yiisoft/rbac` |
| Voters | `yiisoft/access` and/or `yiisoft/rbac` |
| Security config / current user | `yiisoft/user` |

### Symfony — per-section requirements

Requires `symfony/security-bundle`. Sections map to Symfony security primitives:

| Section | Source |
|---------|--------|
| Guards | Parameter `security.firewalls` + all collected `security.firewall.map.config.{name}.*` sub-parameters |
| Role hierarchy | Parameter `security.role_hierarchy.roles` |
| Voters | Services tagged `security.voter` |
| Security config | `security.access_control`, `security.access.decision_manager.strategy`, providers from `security.user.provider.concrete.*` |

### Laravel — per-section requirements

Built-in Laravel authorization is always available; optional packages extend it:

| Section | Source |
|---------|--------|
| Guards | `config('auth.guards')` with provider class resolved from `config('auth.providers.*.model')` or `driver` |
| Role hierarchy | Empty by default; populated from `Spatie\Permission\Models\Role::with('permissions')` when `spatie/laravel-permission` is installed |
| Voters | `Illuminate\Contracts\Auth\Access\Gate`: abilities defined via `Gate::define()` and policies registered via `Gate::policy()` (read through reflection) |
| Security config | `config('auth.defaults')`, `config('auth.providers')`, `config('auth.passwords')`, `config('auth.password_timeout')` |

### Yii 2 — per-section requirements

Session-based auth is always available; RBAC is optional:

| Section | Source |
|---------|--------|
| Guards | Single `user` guard with `provider = identityClass` and login/session config (`loginUrl`, `enableSession`, `authTimeout`, `absoluteAuthTimeout`, `enableAutoLogin`) |
| Role hierarchy | `Yii::$app->authManager->getRoles()` + `getChildren($role)` — empty when the `authManager` component is not configured |
| Voters | `authManager` roles + permissions + rules (each typed accordingly) |
| Security config | `user` snapshot (`identityClass`, `isGuest`, `id`) + `authManager` class and `defaultRoles` |
