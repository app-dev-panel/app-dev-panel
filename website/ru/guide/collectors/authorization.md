---
title: Коллектор авторизации
---

# Коллектор авторизации

Захватывает данные аутентификации и авторизации — идентификатор пользователя, роли, токены, решения о доступе, охранники, иерархию ролей и статус имперсонации.

![Панель коллектора авторизации](/images/collectors/authorization.png)

## Собираемые данные

| Поле | Описание |
|------|----------|
| `username` | Идентификатор аутентифицированного пользователя |
| `roles` | Назначенные роли |
| `effectiveRoles` | Роли после разрешения иерархии |
| `authenticated` | Аутентифицирован ли пользователь |
| `firewallName` | Имя активного файрвола/охранника |
| `token` | Детали токена аутентификации (тип, атрибуты, срок действия) |
| `impersonation` | Данные имперсонации (оригинальный и имперсонируемый пользователь) |
| `guards` | Зарегистрированные охранники аутентификации |
| `roleHierarchy` | Дерево наследования ролей |
| `authenticationEvents` | События входа/выхода/неудачи |
| `accessDecisions` | Результаты проверки авторизации (разрешено/отказано) |

## Схема данных

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

**Сводка** (отображается в списке отладочных записей):

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

## Контракт

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
<class>\AppDevPanel\Kernel\Collector\AuthorizationCollector</class> реализует <class>\AppDevPanel\Kernel\Collector\SummaryCollectorInterface</class>. Не имеет зависимостей от других коллекторов.
:::

## Как это работает

Адаптеры фреймворков извлекают состояние аутентификации из компонента безопасности:
- **Symfony**: Токен безопасности, файрвол, результаты голосования через слушатели событий
- **Laravel**: Охранники Auth, проверки авторизации Gate
- **Yii 3**: Интерфейс Identity и система RBAC

## Панель отладки

- **Идентификатор пользователя** — имя пользователя, статус аутентификации, роли
- **Решения о доступе** — список проверок авторизации с результатами разрешено/отказано
- **Иерархия ролей** — визуальное дерево наследования ролей
- **События аутентификации** — события входа, выхода и неудачных попыток
- **Детали токена** — тип токена, атрибуты и срок действия
