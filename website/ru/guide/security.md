---
title: Безопасность и авторизация
---

# Безопасность и авторизация

ADP собирает данные аутентификации и авторизации из вашего приложения: идентификацию пользователя, роли, токены, события входа/выхода, решения по доступу и имперсонацию. Инспектор также предоставляет просмотр конфигурации безопасности в реальном времени.

## AuthorizationCollector

<class>AppDevPanel\Kernel\Collector\AuthorizationCollector</class> собирает данные безопасности во время выполнения. Реализует <class>AppDevPanel\Kernel\Collector\SummaryCollectorInterface</class> для отображения в списке debug-записей.

### Собираемые данные

| Поле | Тип | Описание |
|------|-----|----------|
| `username` | `?string` | Идентификатор аутентифицированного пользователя |
| `roles` | `string[]` | Назначенные роли |
| `effectiveRoles` | `string[]` | Вычисленные роли (из иерархии) |
| `firewallName` | `?string` | Активный firewall/guard |
| `authenticated` | `bool` | Аутентифицирован ли пользователь |
| `token` | `?{type, attributes, expiresAt}` | Информация о токене (JWT, session, API key) |
| `impersonation` | `?{originalUser, impersonatedUser}` | Данные подмены пользователя |
| `guards` | `array` | Конфигурации guard/firewall |
| `roleHierarchy` | `array<string, string[]>` | Карта наследования ролей |
| `authenticationEvents` | `array` | События входа, выхода, ошибок с таймингом |
| `accessDecisions` | `array` | Проверки авторизации с voters и результатами |

### Методы сбора

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

## Интеграция с адаптерами

Каждый адаптер автоматически передаёт данные в <class>AppDevPanel\Kernel\Collector\AuthorizationCollector</class> из нативной системы аутентификации фреймворка.

### Symfony

<class>AppDevPanel\Adapter\Symfony\EventSubscriber\AuthorizationSubscriber</class> слушает события Symfony Security. Требуется `symfony/security-http`.

| Событие | Собираемые данные |
|---------|-------------------|
| `LoginSuccessEvent` | Идентификация пользователя, роли, firewall, тип токена, имперсонация |
| `LoginFailureEvent` | Событие неудачной аутентификации с деталями исключения |
| `LogoutEvent` | Событие выхода |
| `SwitchUserEvent` | Данные имперсонации |
| `VoteEvent` | Решения по доступу с результатами voters |

::: tip
Включите в `config/packages/app_dev_panel.yaml`:
```yaml
app_dev_panel:
    collectors:
        security: true
```
:::

### Laravel

<class>AppDevPanel\Adapter\Laravel\EventListener\AuthorizationListener</class> слушает события Laravel Auth.

| Событие | Собираемые данные |
|---------|-------------------|
| `Illuminate\Auth\Events\Authenticated` | Идентификация пользователя, имя guard |
| `Illuminate\Auth\Events\Login` | Событие входа, флаг remember |
| `Illuminate\Auth\Events\Logout` | Событие выхода |
| `Illuminate\Auth\Events\Failed` | Неудачная аутентификация с ключами credentials |
| `Illuminate\Auth\Events\OtherDeviceLogout` | Выход на другом устройстве |

### Yii 2

<class>AppDevPanel\Adapter\Yii2\EventListener\AuthorizationListener</class> подключается к событиям `yii\web\User`.

| Событие | Собираемые данные |
|---------|-------------------|
| `User::EVENT_AFTER_LOGIN` | ID пользователя, duration, cookie-based флаг |
| `User::EVENT_AFTER_LOGOUT` | Событие выхода с ID пользователя |
| `EVENT_BEFORE_REQUEST` | Текущий пользователь сессии на каждый запрос |

### Yii 3

AuthorizationCollector зарегистрирован в DI, но требует ручных вызовов — в Yii 3 нет стандартизированной системы событий аутентификации.

## Инспектор авторизации

Инспектор предоставляет просмотр конфигурации безопасности в реальном времени через `GET /inspect/api/authorization`.

### Ответ

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

Адаптеры реализуют `AuthorizationConfigProviderInterface` для предоставления этих данных. По умолчанию: <class>AppDevPanel\Api\Inspector\Authorization\NullAuthorizationConfigProvider</class> (пустые массивы).

## Frontend

### AuthorizationPanel (Debug)

Отображает данные безопасности для каждого запроса в debug-панели:

- Карточка пользователя (username, статус, firewall, роли, эффективные роли, токен)
- Баннер имперсонации (при активности)
- Таймлайн событий аутентификации (вход, выход, ошибка)
- Таблица решений по доступу (раскрываемая, показывает voters и контекст)

### AuthorizationPage (Inspector)

Расположена по адресу `/inspector/authorization`. Отображает конфигурацию безопасности в реальном времени:

- Таблица guards (имя, provider, конфигурация)
- Дерево иерархии ролей (роль → унаследованные роли)
- Таблица voters/policies (имя, тип, приоритет)
- JSON конфигурации безопасности
