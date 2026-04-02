---
title: Elasticsearch
description: "Захват запросов к Elasticsearch и инспекция кластера в реальном времени с коллектором и инспектором ADP."
---

# Elasticsearch

ADP предоставляет <class>AppDevPanel\Kernel\Collector\ElasticsearchCollector</class> для захвата запросов к Elasticsearch во время выполнения приложения и инспектор для инспекции кластера в реальном времени.

## Коллектор

<class>AppDevPanel\Kernel\Collector\ElasticsearchCollector</class> реализует <class>AppDevPanel\Kernel\Collector\SummaryCollectorInterface</class> и захватывает все запросы к Elasticsearch — поиск, индексацию, удаление, массовые операции.

### Паттерны сбора данных

Поддерживаются два паттерна:

**Парный паттерн** — для proxy-адаптеров, перехватывающих ES-клиент:

```php
$collector->collectRequestStart($id, 'GET', '/users/_search', $body, $line);
// ... выполняется запрос ...
$collector->collectRequestEnd($id, 200, $responseBody, $responseSize);
// или при ошибке:
$collector->collectRequestError($id, $exception);
```

**Простой паттерн** — для event-адаптеров, которые измеряют время самостоятельно:

```php
$collector->logRequest(new ElasticsearchRequestRecord(
    method: 'GET',
    endpoint: '/users/_search',
    body: '{"query":{"match_all":{}}}',
    line: __FILE__ . ':' . __LINE__,
    startTime: $start,
    endTime: $end,
    statusCode: 200,
    responseBody: '{"hits":{"total":{"value":42}}}',
    responseSize: 256,
));
```

### Собираемые данные

```php
[
    'requests' => [
        [
            'method' => 'GET',
            'endpoint' => '/users/_search',
            'index' => 'users',           // автоматически извлекается из endpoint
            'body' => '{"query":{...}}',
            'line' => '/src/Repo.php:42',
            'status' => 'success',         // success | error | initialized
            'startTime' => 1711900000.123,
            'endTime' => 1711900000.135,
            'duration' => 0.012,
            'statusCode' => 200,
            'responseBody' => '...',
            'responseSize' => 256,
            'hitsCount' => 42,             // извлекается из ответа (null для не-search запросов)
            'exception' => null,
        ],
    ],
    'duplicates' => [
        'groups' => [...],                 // повторяющиеся комбинации method+endpoint
        'totalDuplicatedCount' => 0,
    ],
]
```

### Summary

```php
[
    'elasticsearch' => [
        'total' => 3,
        'errors' => 0,
        'totalTime' => 0.045,
        'duplicateGroups' => 0,
        'totalDuplicatedCount' => 0,
    ],
]
```

### Возможности

- **Извлечение индекса** — парсит имя индекса из пути endpoint (например, `/users/_search` → `users`)
- **Подсчёт hits** — извлекает `hits.total.value` из ответов поиска
- **Обнаружение дубликатов** — выявляет повторяющиеся комбинации `method + endpoint` (детекция N+1 паттерна)
- **Интеграция с Timeline** — передаёт данные в <class>AppDevPanel\Kernel\Collector\TimelineCollector</class> для единой временной шкалы производительности

## Инспектор

Инспектор Elasticsearch предоставляет инспекцию кластера в реальном времени через <class>AppDevPanel\Api\Inspector\Elasticsearch\ElasticsearchProviderInterface</class>.

### API-эндпоинты

| Метод | Путь | Описание |
|-------|------|----------|
| GET | `/inspect/api/elasticsearch` | Здоровье кластера + список индексов |
| GET | `/inspect/api/elasticsearch/{name}` | Детали индекса (маппинги, настройки, статистика) |
| POST | `/inspect/api/elasticsearch/search` | Выполнение поискового запроса |
| POST | `/inspect/api/elasticsearch/query` | Выполнение произвольного запроса |

### Интерфейс провайдера

```php
interface ElasticsearchProviderInterface
{
    public function getHealth(): array;
    public function getIndices(): array;
    public function getIndex(string $name): array;
    public function search(string $index, array $query, int $limit, int $offset): array;
    public function executeQuery(string $method, string $endpoint, array $body): array;
}
```

По умолчанию: <class>AppDevPanel\Api\Inspector\Elasticsearch\NullElasticsearchProvider</class> возвращает пустые данные. Адаптеры предоставляют конкретные реализации, подключённые к реальному ES-клиенту.

## Фронтенд

### Debug-панель

`ElasticsearchPanel` отображает захваченные запросы:
- Бейджи метода и кода статуса (цветовое кодирование)
- Endpoint с извлечённым именем индекса
- Длительность и количество hits на запрос
- Раскрываемое тело запроса/ответа (рендеринг JSON)
- Фильтрация по endpoint, индексу, методу или содержимому тела
- Предупреждения об обнаружении дубликатов

### Страница Inspector

`ElasticsearchPage` показывает состояние кластера в реальном времени:
- Баннер здоровья кластера (чипы статуса green/yellow/red)
- Количество нод и шардов
- Таблица индексов с количеством документов, размером хранилища, здоровьем, шардами

## Интеграция с фреймворком

Зарегистрируйте <class>AppDevPanel\Kernel\Collector\ElasticsearchCollector</class> в DI вашего адаптера с <class>AppDevPanel\Kernel\Collector\TimelineCollector</class> как зависимость конструктора. Включите через флаг конфигурации `'elasticsearch' => true`.

Смотрите [Адаптеры](/ru/guide/adapters/symfony) для паттернов регистрации в конкретных фреймворках.
