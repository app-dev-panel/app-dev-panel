---
title: Router Collector
---

# Router Collector

Captures HTTP route matching data — matched route, pattern, arguments, match timing, and the full route tree.

![Router Collector panel](/images/collectors/router.png)

## What It Captures

| Field | Description |
|-------|-------------|
| `currentRoute.name` | Route name (if named) |
| `currentRoute.pattern` | Route URL pattern |
| `currentRoute.arguments` | Matched route parameters |
| `currentRoute.host` | Host constraint (if any) |
| `currentRoute.uri` | Actual matched URI |
| `currentRoute.action` | Controller/action handler |
| `currentRoute.middlewares` | Route-level middleware stack |
| `currentRoute.matchTime` | Time to match the route (seconds) |
| `routes` | Full route table |
| `routesTree` | Route tree structure |

## Data Schema

```json
{
    "currentRoute": {
        "matchTime": 0.00012,
        "name": "user.show",
        "pattern": "/users/{id}",
        "arguments": {"id": "42"},
        "host": null,
        "uri": "/users/42",
        "action": "App\\Controller\\UserController::show",
        "middlewares": ["auth", "throttle"]
    },
    "routes": [...],
    "routesTree": [...],
    "routeTime": 0.00012
}
```

**Summary** (shown in debug entry list):

```json
{
    "router": {
        "matchTime": 0.00012,
        "name": "user.show",
        "pattern": "/users/{id}"
    }
}
```

## Contract

```php
use AppDevPanel\Kernel\Collector\RouterCollector;

$collector->collectMatchedRoute([
    'name' => 'user.show',
    'pattern' => '/users/{id}',
    'arguments' => ['id' => '42'],
    'host' => null,
    'uri' => '/users/42',
    'action' => 'App\\Controller\\UserController::show',
    'middlewares' => ['auth', 'throttle'],
]);
$collector->collectMatchTime(matchTime: 0.00012);
$collector->collectRoutes(routes: $allRoutes, routesTree: $routeTree);
```

::: info
<class>\AppDevPanel\Kernel\Collector\RouterCollector</class> implements <class>\AppDevPanel\Kernel\Collector\SummaryCollectorInterface</class>. It has no dependencies on other collectors.
:::

## How It Works

Each framework adapter has a <class>\AppDevPanel\Adapter\Laravel\Collector\RouterDataExtractor</class> that normalizes framework-specific route data into the common format:
- **Symfony**: Extracts from `RouterInterface` and request attributes
- **Laravel**: Extracts from <class>\AppDevPanel\Api\Router\Router</class> facade and matched <class>\AppDevPanel\Api\Router\Route</class> object
- **Yii 3**: Extracts from `UrlMatcherInterface` result

## Debug Panel

- **Matched route** — current route pattern, name, and matched parameters
- **Route arguments** — key-value pairs of resolved parameters
- **Action handler** — controller class and method
- **Match timing** — how long route matching took
