---
title: Коллектор маршрутизации
---

# Коллектор маршрутизации

Собирает данные HTTP-маршрутизации — совпавший маршрут, паттерн, аргументы, время сопоставления и полное дерево маршрутов.

![Панель коллектора маршрутизации](/images/collectors/router.png)

## Что собирает

| Поле | Описание |
|------|----------|
| `currentRoute.name` | Имя маршрута (если именованный) |
| `currentRoute.pattern` | URL-паттерн маршрута |
| `currentRoute.arguments` | Совпавшие параметры маршрута |
| `currentRoute.host` | Ограничение по хосту (если есть) |
| `currentRoute.uri` | Фактический совпавший URI |
| `currentRoute.action` | Обработчик контроллера/действия |
| `currentRoute.middlewares` | Стек мидлваров маршрута |
| `currentRoute.matchTime` | Время сопоставления маршрута (секунды) |
| `routes` | Полная таблица маршрутов |
| `routesTree` | Древовидная структура маршрутов |

## Схема данных

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

**Сводка** (отображается в списке отладочных записей):

```json
{
    "router": {
        "matchTime": 0.00012,
        "name": "user.show",
        "pattern": "/users/{id}"
    }
}
```

## Контракт

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
<class>\AppDevPanel\Kernel\Collector\RouterCollector</class> реализует <class>\AppDevPanel\Kernel\Collector\SummaryCollectorInterface</class>. Не зависит от других коллекторов.
:::

## Как это работает

Каждый адаптер фреймворка имеет `RouterDataExtractor`, который нормализует специфичные для фреймворка данные маршрутизации в общий формат:
- **Symfony**: извлекает из `RouterInterface` и атрибутов запроса
- **Laravel**: извлекает из фасада <class>Illuminate\Routing\Router</class> и совпавшего объекта `Route`
- **Yii 3**: извлекает из результата <class>Symfony\Component\Routing\Matcher\UrlMatcherInterface</class>

## Панель отладки

- **Совпавший маршрут** — паттерн текущего маршрута, имя и совпавшие параметры
- **Аргументы маршрута** — пары ключ-значение разрешённых параметров
- **Обработчик действия** — класс контроллера и метод
- **Тайминг сопоставления** — сколько времени заняло сопоставление маршрута
