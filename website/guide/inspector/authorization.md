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

| Adapter | Provider |
|---------|----------|
| Symfony | <class>AppDevPanel\Adapter\Symfony\Inspector\SymfonyConfigProvider</class> (reads `security.yaml` config) |
| Yii 3 | <class>AppDevPanel\Adapter\Yii3\Inspector\Yii3AuthorizationConfigProvider</class> (reads RBAC / User / Auth / Access services) |
| Others | <class>AppDevPanel\Api\Inspector\Authorization\NullAuthorizationConfigProvider</class> (returns empty) |

::: info
Authorization inspection requires framework-specific integration. The Yii 3 provider is installed automatically with `app-dev-panel/adapter-yii3`. Each section is filled in only when the corresponding Yii package is present in the container (all optional, listed under `suggest` in the adapter's `composer.json`):

| Section | Required package |
|---------|------------------|
| Guards | `yiisoft/auth` |
| Role hierarchy | `yiisoft/rbac` |
| Voters | `yiisoft/access` and/or `yiisoft/rbac` |
| Security config / current user | `yiisoft/user` |
:::
