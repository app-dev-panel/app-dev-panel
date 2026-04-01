---
title: Поток данных
---

# Поток данных

На этой странице описано, как отладочные данные проходят от вашего приложения к панели ADP.

## Обзор

```
Приложение → Адаптер → Прокси → Коллекторы → Debugger → Хранилище → API → Фронтенд
```

## Жизненный цикл запроса

### Фаза 1: Запуск

Когда приложение получает HTTP-запрос (или CLI-команду):

1. Обработчик событий адаптера перехватывает событие запуска фреймворка
2. Вызывается <class>AppDevPanel\Kernel\Debugger</class>`::startup()`, который:
   - Регистрирует shutdown-функцию
   - Проверяет, нужно ли игнорировать запрос/команду (через паттерны `$ignoredRequests` / `$ignoredCommands`, заголовок `X-Debug-Ignore`)
   - Если не игнорируется: вызывает `startup()` на всех зарегистрированных коллекторах

### Фаза 2: Сбор данных

Во время обработки запроса прокси перехватывают вызовы и передают данные коллекторам:

```
Код приложения
      │
      ├──▶  Logger::log()              ──▶  LoggerInterfaceProxy          ──▶  LogCollector
      ├──▶  EventDispatcher::dispatch() ──▶  EventDispatcherInterfaceProxy ──▶  EventCollector
      ├──▶  HttpClient::sendRequest()   ──▶  HttpClientInterfaceProxy      ──▶  HttpClientCollector
      ├──▶  Container::get()            ──▶  ContainerInterfaceProxy       ──▶  ServiceCollector
      ├──▶  VarDumper::dump()           ──▶  VarDumperHandlerInterfaceProxy──▶  VarDumperCollector
      └──▶  throw Exception             ──▶  ExceptionHandler              ──▶  ExceptionCollector
```

Каждый коллектор накапливает данные в памяти на протяжении запроса. Прокси записывают метаданные: временные метки, информацию файл:строка (через `debug_backtrace()`), уникальные ID для корреляции.

### Фаза 3: Завершение и сброс

Когда запрос завершается (или консольная команда заканчивается), <class>AppDevPanel\Kernel\Debugger</class> инициирует завершение:

1. Вызывает `shutdown()` на всех коллекторах (сбрасывает внутреннее состояние)
2. Вызывает `getCollected()` для получения накопленных данных
3. Сериализует объекты через <class>AppDevPanel\Kernel\Dumper</class> (ограничение глубины — 30 уровней, обнаружение циклических ссылок)
4. Вызывает `flush()` на хранилище

<class>AppDevPanel\Kernel\Storage\FileStorage</class> записывает три JSON-файла на каждую отладочную запись:

| Файл | Содержимое |
|------|------------|
| `{id}/summary.json` | Метаданные записи (время, URL, статус, сводки коллекторов) |
| `{id}/data.json` | Полные данные коллекторов |
| `{id}/objects.json` | Извлечённые уникальные PHP-объекты для глубокой инспекции |

Все записи используют `file_put_contents()` с `LOCK_EX` для атомарности.

### Фаза 4: Сборка мусора

После каждого сброса хранилище запускает сборку мусора:

- Захватывает неблокирующую блокировку `.gc.lock` (пропускает, если другой процесс удерживает)
- Удаляет записи сверх `historySize` (по умолчанию 50), отсортированные по времени модификации

## Формат хранилища

```
runtime/debug/
├── YYYY-MM-DD/
│   ├── {entryId}/
│   │   ├── summary.json
│   │   ├── data.json
│   │   └── objects.json
│   ├── {entryId}/
│   │   └── ...
│   └── .gc.lock
└── .services.json
```

### Формат сводки

```json
{
  "id": "1710520800123456",
  "collectors": ["LogCollector", "EventCollector", "RequestCollector"],
  "logger": {"total": 5},
  "event": {"total": 12},
  "http": {"count": 2, "totalTime": 0.45},
  "request": {"url": "/api/users", "method": "GET", "status": 200},
  "exception": null
}
```

Коллекторы, реализующие <class>AppDevPanel\Kernel\Collector\SummaryCollectorInterface</class>, предоставляют свои ключи сводки (например, `logger`, `event`, `http`) для отображения в списке записей без загрузки полных данных.

## Обслуживание API

API предоставляет сохранённые данные через цепочку middleware:

```
Запрос фронтенда
      │
      ▼
  Цепочка middleware API
  ┌────────────────────────────┐
  │ 1. CorsAllowAll            │
  │ 2. IpFilter                │
  │ 3. TokenAuthMiddleware     │
  │ 4. FormatDataResponseAsJson│
  │ 5. ResponseDataWrapper     │
  └────────────┬───────────────┘
               │
               ▼
  CollectorRepository
  ├── .getSummary()    → summary.json
  ├── .getDetail(id)   → data.json
  └── .getObject(id)   → objects.json
               │
               ▼
  JSON-ответ: {id, data, error, success, status}
```

## Обновления в реальном времени (SSE)

Фронтенд использует **SSE** (Server-Sent Events) для обнаружения новых записей в реальном времени:

1. Фронтенд подписывается на `GET /debug/api/event-stream`
2. API опрашивает хранилище каждую секунду, вычисляя MD5-хеш текущих сводок
3. При появлении новой записи хеш меняется и отправляется событие: `{"type": "debug-updated"}`
4. Фронтенд загружает обновлённый список записей

`ServerSentEventsObserver` на фронтенде использует экспоненциальную задержку при сбоях: базовая задержка 1с, удваивается с каждой попыткой, максимум 30с, сбрасывается при успешном подключении.

## API приёма данных (внешние приложения)

Не-PHP приложения могут отправлять отладочные данные через **Ingestion API**, минуя пайплайн прокси/коллекторов. Данные записываются напрямую в хранилище и отображаются в панели наряду с PHP-записями.

| Эндпоинт | Описание |
|----------|----------|
| `POST /debug/api/ingest` | Одна запись с коллекторами + опциональные context/summary |
| `POST /debug/api/ingest/batch` | Несколько записей (макс. 100). Возвращает `{ids: [...], count}` |
| `POST /debug/api/ingest/log` | Сокращение для одной лог-записи: `{level, message, context?}` |
| `GET /debug/api/openapi.json` | OpenAPI-спецификация Ingestion API |

Формат тела запроса для одной записи:

```json
{
  "collectors": {
    "LogCollector": [{"level": "info", "message": "Hello"}]
  },
  "summary": {},
  "context": {}
}
```

## Прокси инспектора (мульти-приложения)

Прокси инспектора позволяет фронтенду инспектировать внешние приложения (Python, Node.js, Go и др.) через единый API.

```
Фронтенд: /inspect/api/routes?service=python-app
      │
      ▼
InspectorProxyMiddleware
      ├── Извлекает имя сервиса из ?service=
      ├── Разрешает через ServiceRegistry → ServiceDescriptor
      ├── Проверяет онлайн-статус (lastSeenAt в пределах 60с)
      ├── Сопоставляет путь с capability (напр., /routes → "routes")
      ├── Проверяет поддержку capability сервисом
      │
      ▼ (все проверки пройдены)
Проксирует запрос на: {inspectorUrl}/inspect/api/routes
```

### Карта capabilities

| Префикс пути | Capability |
|--------------|-----------|
| `/config`, `/params` | `config` |
| `/routes`, `/route/check` | `routes` |
| `/files` | `files` |
| `/cache` | `cache` |
| `/table` | `database` |
| `/translations` | `translations` |
| `/events` | `events` |
| `/command` | `commands` |
| `/git` | `git` |
| `/composer` | `composer` |
| `/phpinfo` | `phpinfo` |
| `/opcache` | `opcache` |

### Ответы об ошибках

| Условие | Статус |
|---------|--------|
| Сервис не найден | 404 |
| Сервис офлайн (таймаут heartbeat) | 503 |
| Capability не поддерживается | 501 |
| URL инспектора не настроен | 502 |
| Отказ соединения / хост не найден | 502 |
| Таймаут запроса | 504 |

## Реестр сервисов

Внешние приложения регистрируются в ADP и отправляют периодические heartbeat-запросы для отображения как онлайн:

```
Внешнее приложение                        ADP
     │                                     │
     │  POST /debug/api/services/register  │
     │  {service, language, inspectorUrl,  │
     │   capabilities}                     │
     │ ──────────────────────────────────▶ │
     │                                     │
     │  POST /debug/api/services/heartbeat │
     │  {service}  (каждые <60с)           │
     │ ──────────────────────────────────▶ │
     │                                     │
     │  GET /debug/api/services/           │
     │ ◀────────────────────────────────── │ (список с online/offline)
```

<class>AppDevPanel\Kernel\Service\ServiceDescriptor</class> содержит: `service`, `language`, `inspectorUrl`, `capabilities[]`, `registeredAt`, `lastSeenAt`. Сервис считается онлайн, если `now() - lastSeenAt < 60с`.

## Консольные команды

Консольные команды следуют тому же жизненному циклу, что и веб-запросы, с отличиями:

- <class>AppDevPanel\Kernel\Collector\Console\ConsoleAppInfoCollector</class> заменяет <class>AppDevPanel\Kernel\Collector\Web\WebAppInfoCollector</class>
- <class>AppDevPanel\Kernel\Collector\Console\CommandCollector</class> заменяет <class>AppDevPanel\Kernel\Collector\Web\RequestCollector</class> (оба отображаются в единой панели "Request" в UI)
- Нет middleware, маршрутизатора или коллекторов ассетов
- События: `ConsoleCommandEvent` запускает startup, `ConsoleTerminateEvent` запускает shutdown

## Debug-сервер (UDP-сокет)

CLI-команда `dev` запускает UDP-сокет сервер для вывода логов/дампов в реальном времени в терминале:

```bash
php yii dev -a 0.0.0.0 -p 8890
```

Когда приложение вызывает `dump()` или логирует сообщение, broadcaster:
1. Обнаруживает запущенные серверные сокеты через glob (`/tmp/yii-dev-server-*.sock`)
2. Отправляет данные как base64-кодированный JSON с 8-байтным заголовком длины
3. Сервер отображает сообщение как форматированный блок в терминале

Типы сообщений: `VarDumper`, `Logger` и простой текст.
