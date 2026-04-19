---
title: Инспектор авторизации
---

# Инспектор авторизации

Инспекция конфигурации безопасности и авторизации вашего приложения.

![Инспектор авторизации](/images/inspector/authorization.png)

## Что отображается

| Раздел | Описание |
|--------|----------|
| Guards | Конфигурация охранников безопасности/файрволов |
| Иерархия ролей | Дерево наследования ролей |
| Voters | Зарегистрированные voters/политики авторизации |
| Конфигурация безопасности | Полный дамп конфигурации безопасности |

## Эндпоинты API

| Метод | Путь | Описание |
|-------|------|----------|
| GET | `/inspect/api/authorization` | Guards, иерархия ролей, voters, конфигурация безопасности |

## Поддержка адаптеров

| Адаптер | Провайдер |
|---------|-----------|
| Symfony | <class>AppDevPanel\Adapter\Symfony\Inspector\SymfonyConfigProvider</class> (читает конфигурацию `security.yaml`) |
| Yii 3 | <class>AppDevPanel\Adapter\Yii3\Inspector\Yii3AuthorizationConfigProvider</class> (читает сервисы RBAC / User / Auth / Access) |
| Другие | <class>AppDevPanel\Api\Inspector\Authorization\NullAuthorizationConfigProvider</class> (возвращает пустое значение) |

::: info
Инспекция авторизации требует интеграции, специфичной для фреймворка. Провайдер для Yii 3 устанавливается автоматически вместе с `app-dev-panel/adapter-yii3`. Каждый раздел заполняется только при наличии соответствующего пакета Yii в контейнере (все опциональны и перечислены в `suggest` в `composer.json` адаптера):

| Раздел | Необходимый пакет |
|--------|-------------------|
| Guards | `yiisoft/auth` |
| Иерархия ролей | `yiisoft/rbac` |
| Voters | `yiisoft/access` и/или `yiisoft/rbac` |
| Конфигурация безопасности / текущий пользователь | `yiisoft/user` |
:::
