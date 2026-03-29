# REST-эндпоинты

## Debug API

| Метод | Путь | Описание |
|-------|------|----------|
| GET | `/debug/api/` | Список всех записей отладки (сводки) |
| GET | `/debug/api/summary/{id}` | Сводка одной записи |
| GET | `/debug/api/view/{id}` | Полные данные записи (с фильтрацией по коллектору) |
| GET | `/debug/api/dump/{id}` | Дамп объектов для записи |
| GET | `/debug/api/object/{id}/{objectId}` | Конкретный объект из дампа |

### Пример: список записей

```
GET /debug/api/
```

```json
{
    "id": null,
    "data": [
        {
            "id": "abc123",
            "collectors": ["request", "log", "event"],
            "url": "/api/users",
            "method": "GET",
            "status": 200,
            "time": 1234567890
        }
    ],
    "error": null,
    "success": true,
    "status": 200
}
```

### Пример: просмотр записи

```
GET /debug/api/view/abc123?collector=log
```

Возвращает полные собранные данные для указанной записи, с возможностью фильтрации по одному коллектору.

## Ingestion API

| Метод | Путь | Описание |
|-------|------|----------|
| POST | `/debug/api/ingest/` | Загрузка одной записи отладки |
| POST | `/debug/api/ingest/batch` | Загрузка нескольких записей |
| POST | `/debug/api/ingest/log` | Сокращение: загрузка одной записи лога |
| GET | `/debug/api/ingest/openapi.json` | Спецификация OpenAPI 3.1 |

## Service Registry API

| Метод | Путь | Описание |
|-------|------|----------|
| POST | `/debug/api/services/register` | Регистрация внешнего сервиса |
| POST | `/debug/api/services/heartbeat` | Поддержание сервиса онлайн |
| GET | `/debug/api/services/` | Список зарегистрированных сервисов |
| DELETE | `/debug/api/services/{service}` | Удаление регистрации сервиса |
