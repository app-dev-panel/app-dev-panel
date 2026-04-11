# Подлодка PHP — «Как мы делали фреймворконезависимую дебаг-панель»

Доклад про ADP (Application Development Panel). Длительность: **20–25 минут** обычного разговора. Формат: технические трюки + 2 живых демо.

## Цели доклада

1. Показать **инженерные решения** внутри панели, которые делают её интересной (не «ещё один список логов»).
2. Подсветить фичи, которых нет у Symfony Profiler, Laravel Telescope, Clockwork, Yii Debug.
3. Увести людей в GitHub — особенно тех, кто хочет писать адаптеры к своим фреймворкам.

## Структура (~22 минуты)

| Блок | Мин | Слайдов |
|------|----:|--------:|
| 0 Интро | 1.5 | 1 |
| 1 Контекст | 1.5 | 1 |
| 2 Архитектура | 1.5 | 1 |
| 3 PSR-проксирование | 3 | 2–3 |
| 4 Stream wrappers | 2 | 1–2 |
| 5 Inspector + демо 1 | 3 | 2 + live |
| 6 Multi-app + Ingestion | 2 | 1–2 |
| 7 MCP + демо 2 | 3 | 2 + live |
| 8 ACP-демон | 1.5 | 1 |
| 9 Code coverage | 1 | 1 |
| 10 Грабли | 1.5 | 1 |
| 11 Зовём в GitHub | 0.5–1 | 1 |
| **Итого** | **~22 мин** | **~17 слайдов** |

---

## 0. Интро (1–1.5 мин)

- Привет, я — X. Делаю **ADP (Application Development Panel)** — форк-консолидация Yii Debug, но теперь это уже независимый монорепозиторий, работающий с Yii 2/3, Symfony, Laravel и, в теории, с чем угодно.
- Сегодня покажу не «ещё одну дебаг-панель», а **технические трюки**, на которых она стоит, и фичи, которых нет у Symfony Profiler, Telescope, Clockwork и Yii Debug.
- Буду чередовать «смотрите, как мы это сделали» и «а теперь живой показ в браузере».

## 1. Контекст: почему не хватало того, что есть (1.5 мин)

- Быстрое сравнение в одной таблице (из `docs/strategic-analysis.md`):
  - Symfony Profiler / Telescope — прибиты гвоздями к своему фреймворку.
  - Clockwork — частично agnostic, но без live-инспекции.
  - Yii Debug — хорош, но именно yii.
- Что мы хотели: один UI, один протокол, одна логика сбора — а внизу хоть Laravel, хоть Node.js, хоть Python.
- Вывод: надо **отрезать ядро от фреймворка** и уметь подключаться к живому приложению, а не только смотреть в прошлое.

## 2. Архитектура в одном слайде (1.5 мин)

- Слоёная диаграмма: **Kernel → API → (McpServer) → Adapter → Target App → Frontend (React)**.
- Kernel знает только про PSR-3/7/11/14/15/16/17/18 — ни о каком Laravel не слышал.
- Адаптер — это маленький мостик: берёт сервис из DI фреймворка и заворачивает его в наш прокси.
- Frontend общается только с API, ничего не знает о том, какой там бэкенд.
- Файлы для слайда: `libs/Kernel`, `libs/API`, `libs/Adapter/{Yii3,Symfony,Laravel,Yii2}`.

## 3. Трюк №1: Прозрачная подмена PSR-интерфейсов (3 мин) — главная инженерная штука

### Идея

Приложение пишет `$logger->info(...)`, не зная, что `$logger` — уже наш `LoggerInterfaceProxy`, который пишет в `LogCollector` и потом отдаёт управление настоящему логеру.

### Что показать на слайде

Классы из `libs/Kernel/src/Collector/`:

- `LoggerInterfaceProxy` — PSR-3
- `EventDispatcherInterfaceProxy` — PSR-14
- `HttpClientInterfaceProxy` — PSR-18
- `SpanProcessorInterfaceProxy` — OpenTelemetry

Ключевые механизмы:

- Трейт `ProxyDecoratedCalls` делает `__get`/`__set`/`__call` делегирование — ноль бойлерплейта.
- `debug_backtrace()` в прокси **отфильтровывает свои же фреймы**, чтобы показать настоящее место вызова из пользовательского кода. Мини-слайд «как мы возвращаем честный стек».
- Проверки перед сбором — **ноль оверхеда для выключенных коллекторов**.
- Свой дампер `libs/Kernel/src/Dumper.php` + `DumpContext.php`: depth limit 50, детект циклов через `spl_object_id()`, два режима (full dump / object map для превью). Отдельный от `symfony/var-dumper`, потому что нужно было жёстко управлять глубиной и производительностью.

### Как это встаёт во фреймворк

- **Symfony**: `CollectorProxyCompilerPass` на этапе compile заменяет определения сервисов `logger`, `event_dispatcher`, `ClientInterface`, `translator` — всё в `libs/Adapter/Symfony/src/DependencyInjection/CollectorProxyCompilerPass.php`.
- **Laravel**: декорирование в `ServiceProvider::boot()`.
- **Yii 2**: переопределяем компоненты через `Module::bootstrap()`, подсовываем прокси.
- **Yii 3**: DI-плагин через `params.php` + `di.php`.

### Посыл

«Вам не надо вставлять **ни одной строки** в свои контроллеры — мы уже у вас в DI».

## 4. Трюк №2: Перехват на уровне PHP stream wrappers (2 мин) — такого ни у кого нет

- Коллекторы `FilesystemStreamCollector` и `HttpStreamCollector` ловят **любые** `fopen`, `file_get_contents`, `include`, `require`, `https://…` через нативные stream wrappers.
- Код: `libs/Kernel/src/Collector/Stream/FilesystemStreamProxy.php` — `stream_wrapper_unregister('file')` → `stream_wrapper_register('file', self::class)`, и точно так же для `http`/`https`.
- **Осторожный момент**: важно заранее прогреть автозагрузчик (`class_exists(...)` до unregister), иначе при первом `require` панель сама себя подвесит. Показать это место — классический «шрам боя».
- **Per-operation флаги** (`readCollected`, `writeCollected`, `readdirCollected`): иначе одно открытие файла + 10 `stream_read` → 10 записей в коллекторе.
- **Что это даёт**: видно все файловые операции в запросе, даже те, что идут через vendor-библиотеки мимо вашего DI.

## 5. Трюк №3: Inspector Mode — живое состояние, а не только история (3 мин, с демо)

У конкурентов дебаг-панель = «посмотри, что было на прошлом запросе». У нас отдельный слой **Inspector** (`libs/API/src/Inspector/Controller/`), который читает **текущее** состояние приложения:

| Контроллер | Что показывает |
|---|---|
| `RoutingController` | Маршруты + «проверка маршрута» — куда поедет данный URL |
| `DatabaseController` | Схема БД, выборка строк, EXPLAIN и **raw SQL** прямо из UI |
| `GitController` | Branch, log, checkout, pull/fetch — прямо из панели (через `gitonomy/gitlib`, не через `exec git`) |
| `ComposerController` | `composer.json`/`lock`, **установка пакета из UI** |
| `CacheController` / `OpcacheController` | Просмотр/удаление ключей, статус байткода |
| `RedisController` / `ElasticsearchController` | Ping, info, SCAN, raw query |
| `AuthorizationController` | **Живые** guards, role hierarchy, voters/policies |
| `FileController` / `TranslationController` | Файловый браузер, i18n словари с in-place правкой |
| `CodeCoverageController` | Per-request покрытие через pcov/xdebug |

### Демо 1

Открываем `/inspect/...`, редактируем перевод, жмём «explain» на SQL, чекаутим ветку.

### Зачем

Это превращает панель из «ретроспективного просмотрщика» в **IDE-like консоль** к приложению.

## 6. Трюк №4: Многоприложенческий дебаг и Service Registry (2 мин)

### Как это работает

- Ядро регистрирует внешние сервисы в `FileServiceRegistry` (`libs/Kernel/src/Service/`).
- `InspectorProxyMiddleware` смотрит на `?service=<name>` и **проксирует** запрос инспектора во внешнее приложение.
- Heartbeat-трекинг: если сервис не отметился в течение 60 секунд — помечается offline.
- Capabilities: сервис объявляет, что он умеет (`routes`, `database`, …); middleware возвращает 501, если не умеет.

### Следствие

В одной панели можно одновременно дебажить Symfony-монолит + Laravel-админку + Node-воркер. Показать переключалку сервисов.

### Language-agnostic Ingestion

- `POST /debug/api/ingest` — любой язык, свой OpenAPI-контракт (`openapi/ingestion.yaml`), уже есть Python и TypeScript клиенты в `clients/`.
- Бонусом — **OTLP ingestion** (`/debug/api/otlp/v1/traces`), в панели рисуется как обычный таймлайн через `OpenTelemetryCollector`.

## 7. Трюк №5: MCP-сервер для AI-ассистентов (3 мин, с демо) — вау-фича

`libs/McpServer` — полноценный MCP-сервер (spec `2024-11-05`), два транспорта:

- **stdio** (`bin/adp-mcp`) — для Claude Code, Cursor;
- **HTTP** (`POST /inspect/api/mcp`, JSON-RPC 2.0) — прямо встроен в API.

### Инструменты, которые AI может вызывать

**Debug (на сохранённые данные):**

- `list_debug_entries`
- `view_debug_entry`
- `search_logs`
- `analyze_exception`
- `view_database_queries` (с **N+1 детектом**!)
- `view_timeline`

**Inspector (живое приложение):**

- `inspect_config`
- `inspect_routes`
- `inspect_database_schema`

### Слайд с JSON-RPC вызовом

```json
{
  "jsonrpc": "2.0",
  "id": 1,
  "method": "tools/call",
  "params": {
    "name": "search_logs",
    "arguments": {"query": "fatal", "level": "error"}
  }
}
```

### Демо 2

В Claude Code говорю «что сломалось на последнем запросе?» → ассистент сам вызывает `analyze_exception` → стейс + лог + контекст. Перед демо заготовить «сломанный» запрос в `playground/laravel-app`.

### Посыл

Дебаг-панель становится **интерфейсом для агента**, а не только для человека.

## 8. Трюк №6: ACP — панель сама поднимает локальный AI-агент (1.5 мин)

- В панели есть **чат с LLM** и кнопка «проанализируй эту ошибку».
- Четыре провайдера: OpenRouter, Anthropic, OpenAI и **ACP (Agent Client Protocol)** — это когда мы сами спавним локальный `claude-code`/другой агент как subprocess.
- Код: `libs/API/src/Llm/Acp/acp-daemon-runner.php` — **standalone PHP-демон**, без autoloader, общающийся через Unix-socket.
- **Важное уточнение**: демон **не спавнит агента на каждый запрос**; он держит пул живых subprocess-ов и маршрутизирует запросы по session-id. Это сильный аргумент «почему не тормозит».
- **Посыл**: «ваш локальный Claude Code сидит прямо внутри дебаг-панели, видит все её данные, и вы чатитесь с ним без переключения контекста».

## 9. Маленькая, но классная штучка: per-request Code Coverage (1 мин)

- `CodeCoverageCollector` запускает **pcov/xdebug** и собирает покрытие **конкретно этого HTTP-запроса**.
- UI показывает, какие файлы/строки задело именно это обращение.
- Полезно для: «что вообще выполняется, когда пользователь жмёт такую кнопку», и для аудита legacy.

## 10. Чему научились, пока это строили (1.5 мин) — слайд «грабли»

- SSE через polling файла каждую секунду держит PHP-воркер занятым. План: inotify / shared memory.
- `FileStorage` хорош до ~5000 записей, дальше нужен SQLite.
- Stream wrappers и autoloading: один `class_exists` не там, и приложение падает при первом `require`.
- Compiler pass в Symfony — мощный, но легко сломать чужие декораторы; приходилось проверять `logger` отдельно от `LoggerInterface`.
- Yii 2 — 2.0.50+, потому что нужны были нормальные type hints в компонентах.

## 11. Куда идём и зовём к себе (0.5–1 мин)

- Ближайшее: production-safe mode (сэмплинг), SQLite storage, frontend code splitting.
- Стратегически: OpenTelemetry-экспорт, anomaly detection, CI-интеграция («ассертить отсутствие N+1 в тестах»).
- Ссылка, QR, «приходите ломать в GitHub, особенно адаптеры».

---

## Рекомендации по подаче

- **Два живых демо** (Inspector + MCP в Claude Code) — держат зал; остальное — код на слайдах.
- На каждый технический трюк — **один слайд с путём к файлу** (`libs/Kernel/src/Collector/Stream/FilesystemStreamProxy.php`). Слушатели потом сами полезут.
- В блоке 3 (PSR-прокси) показать **один и тот же коллектор в трёх фреймворках** — это самый сильный аргумент в пользу архитектуры.
- Перед MCP-демо заготовить «сломанный» запрос в playground (`playground/laravel-app`), чтобы `analyze_exception` отработал красиво.
- Не уходить в пузырь «мы переписали Yii Debug» — аудитория Подлодки смешанная, Symfony/Laravel больше.

## Файлы-ссылки для слайдов

| Блок | Файлы |
|---|---|
| PSR-прокси | `libs/Kernel/src/Collector/LoggerInterfaceProxy.php`, `EventDispatcherInterfaceProxy.php`, `HttpClientInterfaceProxy.php`, `ProxyDecoratedCalls.php` |
| Symfony compiler pass | `libs/Adapter/Symfony/src/DependencyInjection/CollectorProxyCompilerPass.php` |
| Laravel provider | `libs/Adapter/Laravel/src/AppDevPanelServiceProvider.php` |
| Yii 2 module | `libs/Adapter/Yii2/src/Module.php` |
| Stream wrappers | `libs/Kernel/src/Collector/Stream/FilesystemStreamProxy.php`, `HttpStreamProxy.php`, `libs/Kernel/src/Helper/StreamWrapper/StreamWrapper.php` |
| Dumper | `libs/Kernel/src/Dumper.php`, `DumpContext.php` |
| Inspector | `libs/API/src/Inspector/Controller/*.php` |
| Service Registry | `libs/Kernel/src/Service/FileServiceRegistry.php`, `libs/API/src/Inspector/Middleware/InspectorProxyMiddleware.php` |
| Ingestion | `libs/API/src/Ingestion/Controller/IngestionController.php`, `OtlpController.php` |
| MCP | `libs/McpServer/src/McpServer.php`, `libs/McpServer/src/Tool/Debug/*.php`, `libs/McpServer/src/Tool/Inspector/*.php` |
| ACP | `libs/API/src/Llm/Acp/acp-daemon-runner.php`, `AcpDaemonManager.php` |
| Code coverage | `libs/Kernel/src/Collector/CodeCoverageCollector.php`, `libs/API/src/Inspector/Controller/CodeCoverageController.php` |
