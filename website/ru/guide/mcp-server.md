---
title: MCP-сервер
---

# MCP-сервер

ADP включает MCP-сервер (Model Context Protocol), который предоставляет отладочные данные AI-ассистентам -- Claude, Cursor и другим MCP-совместимым клиентам.

## Транспорты

ADP поддерживает два режима транспорта для MCP-коммуникации:

| Транспорт | Применение | Точка входа |
|-----------|------------|-------------|
| **stdio** | AI-клиенты, запускающие локальный процесс (Claude Code, Cursor) | `php vendor/bin/adp-mcp` |
| **HTTP** | AI-клиенты, подключающиеся к работающему серверу | `POST /inspect/api/mcp` |

### Транспорт stdio

Настройте AI-клиент для запуска бинарного файла ADP MCP:

```json
{
  "mcpServers": {
    "adp": {
      "command": "php",
      "args": ["vendor/bin/adp-mcp", "--storage=/path/to/debug-data"]
    }
  }
}
```

Также принимается переменная окружения `ADP_STORAGE_PATH`.

### Транспорт HTTP

Доступен автоматически при запущенном сервере ADP. Для клиентов с поддержкой HTTP URL:

```json
{
  "mcpServers": {
    "AppDevPanel": {
      "url": "http://localhost:8080/inspect/api/mcp"
    }
  }
}
```

Для клиентов, поддерживающих только stdio (например, Claude Desktop), используйте прокси `mcp-remote`:

```json
{
  "mcpServers": {
    "AppDevPanel": {
      "command": "npx",
      "args": ["-y", "mcp-remote", "http://localhost:8080/inspect/api/mcp"]
    }
  }
}
```

## Доступные инструменты

MCP-сервер предоставляет шесть инструментов отладки:

| Инструмент | Описание |
|------------|----------|
| `list_debug_entries` | Список последних отладочных записей со сводной информацией |
| `view_debug_entry` | Полные данные коллекторов для конкретной записи |
| `search_logs` | Поиск сообщений логов по запросу и уровню |
| `analyze_exception` | Детали исключения со стектрейсом и контекстом |
| `view_database_queries` | SQL-запросы с таймингом и обнаружением N+1 |
| `view_timeline` | Временная шкала производительности от всех коллекторов |

## Включение и отключение

MCP-сервер можно переключить через настройки во фронтенде или через API:

```bash
# Проверить статус
curl http://localhost:8080/inspect/api/mcp/settings

# Включить
curl -X PUT http://localhost:8080/inspect/api/mcp/settings \
  -H "Content-Type: application/json" -d '{"enabled": true}'
```

При отключении MCP-эндпоинт возвращает JSON-RPC ошибку с кодом `-32000`.

## Протокол

<class>AppDevPanel\McpServer\McpServer</class> реализует спецификацию MCP версии `2024-11-05` на основе JSON-RPC 2.0. Поддерживаемые методы: `initialize`, `initialized`, `ping`, `tools/list`, `tools/call`.
