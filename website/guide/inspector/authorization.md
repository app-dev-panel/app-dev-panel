---
title: Authorization Inspector
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
| Symfony | <class>\AppDevPanel\Adapter\Symfony\Inspector\SymfonyConfigProvider</class> (reads `security.yaml` config) |
| Others | <class>\AppDevPanel\Api\Inspector\Authorization\NullAuthorizationConfigProvider</class> (returns empty) |

::: info
Authorization inspection requires framework-specific integration. Currently, only the Symfony adapter provides full security config introspection.
:::
