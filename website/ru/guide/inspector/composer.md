---
title: Инспектор Composer
---

# Инспектор Composer

Просмотр установленных пакетов, информации о пакетах и установка новых зависимостей из панели.

![Инспектор Composer](/images/inspector/composer.png)

## Вкладки

### Пакеты

Список всех установленных Composer-пакетов с информацией о версиях. Показывает зависимости `require` и `require-dev`. Нажмите **Switch**, чтобы переключиться между production- и development-пакетами.

### composer.json

Просмотр содержимого `composer.json` в исходном виде.

### composer.lock

Просмотр содержимого `composer.lock` в исходном виде (если присутствует).

## Просмотр пакета

Нажмите на пакет, чтобы увидеть подробную информацию через `composer show --all --format=json`:
- Описание, домашняя страница, лицензия
- Все доступные версии
- Зависимости и конфликты
- Источник установки

## Установка пакетов

Устанавливайте новые пакеты прямо из панели:
- Укажите имя пакета и, при необходимости, ограничение версии
- Выберите между `--dev` и production-зависимостью
- Выполняется `composer require` в неинтерактивном режиме

## API-эндпоинты

| Метод | Путь | Описание |
|-------|------|----------|
| GET | `/inspect/api/composer` | Получить composer.json и composer.lock |
| GET | `/inspect/api/composer/inspect?package=vendor/name` | Подробности о пакете |
| POST | `/inspect/api/composer/require` | Установить пакет |

**Тело запроса на установку:**
```json
{
    "package": "vendor/package-name",
    "version": "^2.0",
    "isDev": false
}
```

::: warning
Установка пакетов изменяет `composer.json` и `composer.lock`. На сервере выполняется `composer require`.
:::
