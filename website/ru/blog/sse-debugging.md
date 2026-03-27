---
title: Отладка в реальном времени с Server-Sent Events
date: 2026-03-25
author: ADP Team
tags: [deep-dive, sse, architecture]
---

<script setup>
import BlogPost from '../../.vitepress/theme/components/BlogPost.vue';
</script>

<BlogPost
  title="Отладка в реальном времени с Server-Sent Events"
  date="2026-03-25"
  author="ADP Team"
  :tags="['deep-dive', 'sse', 'architecture']"
  readingTime="6 мин"
  :prev="{ title: 'Создание собственных коллекторов', url: '/ru/blog/custom-collectors' }"
  :next="{ title: 'AI-отладка с MCP-сервером', url: '/ru/blog/mcp-server-ai' }"
/>

Одна из самых мощных возможностей ADP — отладка в реальном времени. Данные отладки появляются в браузере в момент обработки запроса вашим приложением, без обновления страницы.

## Почему SSE?

Мы рассмотрели три подхода для обмена данными в реальном времени:

| Подход | Плюсы | Минусы |
|--------|-------|--------|
| Polling | Просто | Расточительно, задержки |
| WebSocket | Дуплексный | Сложно, избыточно |
| **SSE** | **Просто, авто-переподключение** | **Только в одну сторону** |

Данные отладки идут в одном направлении (сервер → браузер), поэтому SSE — идеальный выбор. Он работает поверх стандартного HTTP, проходит через прокси и автоматически переподключается при обрыве.

## Как это работает в ADP

### Серверная сторона

Когда Debugger сбрасывает данные коллекторов в хранилище, API-слой рассылает SSE-события:

```php
final class SseController
{
    public function stream(ServerRequestInterface $request): ResponseInterface
    {
        return new SseResponse(function (SseStream $stream) {
            while (true) {
                $entries = $this->storage->getNewEntries($lastId);
                foreach ($entries as $entry) {
                    $stream->event('debug-entry', json_encode([
                        'id' => $entry->getId(),
                        'collectors' => $entry->getCollectorNames(),
                        'timestamp' => $entry->getTimestamp(),
                    ]));
                }
                $stream->sleep(0.5);
            }
        });
    }
}
```

Ключевые решения:

- **Лёгкие payload** — SSE-события содержат только метаданные записи. Полные данные фронтенд запрашивает отдельно.
- **Интервал 0.5 сек** — Баланс между отзывчивостью и нагрузкой на сервер.
- **Отслеживание ID** — Каждый клиент помнит последний полученный ID, поэтому переподключения не теряют записи.

### Клиентская сторона

Фронтенд SDK предоставляет React-хук для потребления SSE:

```typescript
function useDebugStream() {
    useEffect(() => {
        const source = new EventSource('/debug/api/stream');

        source.addEventListener('debug-entry', (event) => {
            const entry = JSON.parse(event.data);
            dispatch(addEntry(entry));
        });

        source.onerror = () => {
            // EventSource переподключается автоматически
            console.warn('SSE connection lost, reconnecting...');
        };

        return () => source.close();
    }, []);
}
```

Нативный API `EventSource` браузера обрабатывает переподключение автоматически с экспоненциальным backoff.

## Архитектура

```
┌─────────────┐     flush      ┌──────────┐     SSE      ┌──────────┐
│  Debugger   │───────────────▶│  Storage  │─────────────▶│ Frontend │
│ (Collectors)│                │  (JSON)   │  /api/stream │ (React)  │
└─────────────┘                └──────────┘              └──────────┘
                                    │
                                    │ REST (по запросу)
                                    ▼
                              Полные данные записи
```

## Производительность

- **Лимит соединений**: Браузеры ограничивают количество одновременных SSE-соединений (обычно 6). ADP использует один мультиплексированный поток.
- **Память**: Записи хранятся как JSON-файлы, не в памяти. SSE-контроллер читает с диска.
- **Сжатие**: Ответы SSE сжимаются gzip, если клиент это поддерживает.

## Попробуйте

Запустите приложение с установленным адаптером ADP, откройте панель и сделайте запрос к приложению. Данные отладки появятся в панели мгновенно — без обновления страницы.
