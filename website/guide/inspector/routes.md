---
title: Routes Inspector
---

# Routes Inspector

Browse and test all registered HTTP routes in your application.

![Routes Inspector](/images/inspector/routes.png)

## What It Shows

| Field | Description |
|-------|-------------|
| Name | Route name (e.g., `app_users_index`) |
| Pattern | URL pattern with placeholders (e.g., `/api/users/{id}`) |
| Methods | Allowed HTTP methods (GET, POST, etc.) |
| Middlewares | Controller/action or middleware stack handling the route |

## Route Testing

Test whether a URL matches any registered route directly in the panel. Enter a path like `GET /api/users/42` and see:
- Whether it matches a route
- Which controller/action handles it

Supports inline HTTP method specification: `POST /api/users` (defaults to GET if omitted).

## API Endpoints

| Method | Path | Description |
|--------|------|-------------|
| GET | `/inspect/api/routes` | List all registered routes |
| GET | `/inspect/api/route/check?route=GET /path` | Test if a path matches a route |

## Adapter Support

| Adapter | Supported |
|---------|-----------|
| Symfony | Yes (via <class>\AppDevPanel\Adapter\Symfony\Inspector\SymfonyRouteCollectionAdapter</class>) |
| Laravel | Yes |
| Yii 3 | Yes |
| Yii 2 | Yes |

::: tip
Routes include middleware definitions where available, so you can see the full request pipeline for each endpoint.
:::
