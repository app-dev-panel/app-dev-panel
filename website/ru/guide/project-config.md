---
title: Конфигурация проекта
description: "Сохранение Frames и OpenAPI-спек в коммитный JSON-файл (config/adp/project.json), чтобы вся команда после git pull получала одинаковую настройку панели."
---

# Конфигурация проекта

Frames (встроенные iframe) и OpenAPI-спеки, добавленные через UI панели, **сохраняются в JSON-файл рядом с исходниками вашего приложения**. Закоммитьте этот файл — и каждый разработчик команды после `git pull` получает идентичный сетап. Никакого «у меня работает».

Внутри панель держит локальный кэш в `localStorage` для оффлайн-UX, но источник истины — файл на бэкенде: на каждой загрузке страницы панель забирает свежую версию, а ваши правки дебаунсятся (500 мс) в один `PUT`.

## Структура файлов

Панель пишет два файла в директорию, специфичную для каждого фреймворка:

```
<your-app>/
└── config/
    └── adp/
        ├── project.json   ← коммитьте — общий с командой
        └── .gitignore     ← создаётся автоматически, игнорирует secrets.json
```

| Файл | Коммитить? | Содержимое |
|------|------------|------------|
| `project.json` | **Да** | `{version, frames, openapi}` — карты «отображаемое имя → URL» |
| `.gitignore` | **Да** | Автогенерируется с `secrets.json` |
| `secrets.json` | **Нет** (gitignored) | API-ключи, OAuth-токены, ACP-окружение. Только локально, права `0600` |

Пример `project.json`:

```json
{
    "version": 1,
    "frames": {
        "Grafana": "https://grafana.example.com/",
        "Logs": "https://kibana.example.com/"
    },
    "openapi": {
        "Main API": "/api/openapi.json",
        "Webhooks": "https://webhooks.example.com/openapi.json"
    }
}
```

Структура нарочно простая: каждая запись — пара «имя → URL». Файл можно править вручную, главное оставаться валидным JSON.

## Путь конфигурации по фреймворкам

Каждый адаптер кладёт директорию в место, идиоматичное для своего фреймворка, и предоставляет рычаг переопределения.

:::tabs key:framework
== Yii 3

**По умолчанию:** `<корень-проекта>/config/adp` — резолвится через alias `@root` из Yiisoft Aliases.

**Переопределение** в `config/params.php`:

```php
'app-dev-panel/yii3' => [
    // ...
    'projectConfigPath' => '@root/config/adp', // по умолчанию
    // 'projectConfigPath' => '@root/.adp',    // например, скрыть в точечной директории
],
```

Принимает любой alias, который понимает Yiisoft `Aliases` (`@root`, `@runtime` и т.д.), либо абсолютный путь.

== Symfony

**По умолчанию:** `%kernel.project_dir%/config/adp`.

**Переопределение** в `config/packages/app_dev_panel.yaml`:

```yaml
app_dev_panel:
    project_config_path: '%kernel.project_dir%/config/adp'  # по умолчанию
    # project_config_path: '%kernel.project_dir%/.adp'      # пример
```

Параметр контейнера называется `app_dev_panel.project_config_path`. `%kernel.project_dir%` резолвится во время компиляции контейнера.

== Laravel

**По умолчанию:** `base_path('config/adp')`.

**Переопределение** в `config/app-dev-panel.php`:

```php
return [
    // ...
    'project_config_path' => base_path('config/adp'),  // по умолчанию
    // 'project_config_path' => base_path('.adp'),     // пример
];
```

Можно прокинуть из `.env`:

```php
'project_config_path' => env('APP_DEV_PANEL_PROJECT_CONFIG', base_path('config/adp')),
```

== Yii 2

**По умолчанию:** `@app/config/adp`.

**Переопределение** на объявлении модуля в конфигурации приложения:

```php
'modules' => [
    'app-dev-panel' => [
        'class' => \AppDevPanel\Adapter\Yii2\Module::class,
        // ...
        'projectConfigPath' => '@app/config/adp',  // по умолчанию
        // 'projectConfigPath' => '@app/.adp',     // пример
    ],
],
```

Путь резолвится через `Yii::getAlias()`, поэтому подходит любой зарегистрированный alias или абсолютный путь.

== Spiral

**По умолчанию:** `<корень>/app/config/adp`. Резолвер сначала смотрит `APP_DEV_PANEL_ROOT_PATH` (его выставляет точка входа приложения вместе с `PathResolver`), и только потом откатывается на `getcwd()`. Это держит файл вне `public/`, даже если `php -S` подменил рабочую директорию на docroot.

**Переопределение** в `app/config/app-dev-panel.php`:

```php
return [
    // ...
    'project_config_path' => directory('app') . 'config/adp',  // по умолчанию
    // 'project_config_path' => directory('root') . '.adp',    // пример
];
```

Либо без файла конфига — через окружение:

```dotenv
APP_DEV_PANEL_PROJECT_CONFIG_PATH=/srv/app/app/config/adp
```

`AdpConfig::projectConfigPath()` проверяет источники по приоритету: явный config → `APP_DEV_PANEL_PROJECT_CONFIG_PATH` → `APP_DEV_PANEL_ROOT_PATH/app/config/adp` → `getcwd()/app/config/adp`.
:::

## API-эндпоинт

Фронтенд общается с бэкендом через два эндпоинта под `/debug/api/project`:

| Метод | Путь | Назначение |
|-------|------|------------|
| `GET` | `/debug/api/project/config` | Возвращает `{config: {version, frames, openapi}, configDir}`. `configDir` — абсолютный путь, который нужно `git add` |
| `PUT` | `/debug/api/project/config` | Принимает «голый» документ `{frames, openapi}` или обёртку как у GET. Битые записи (нестроковые ключи/значения, пустые строки) тихо отбрасываются |

Можно проверить установку через `curl`:

```bash
curl http://127.0.0.1:8101/debug/api/project/config | jq
# {
#   "data": {
#     "config": {"version": 1, "frames": {}, "openapi": {}},
#     "configDir": "/home/you/app/config/adp"
#   }
# }
```

```bash
curl -X PUT \
  -H 'Content-Type: application/json' \
  -d '{"frames":{"Grafana":"https://grafana.example/"},"openapi":{}}' \
  http://127.0.0.1:8101/debug/api/project/config
```

После первого `PUT` директория `config/adp/` и оба файла появляются на диске.

## Как фронтенд синхронизирует данные

Панель работает по dual-store схеме, чтобы оставаться рабочей при недоступном бэкенде:

1. **При загрузке** панель отправляет `getProjectConfig`. Документ с сервера перезаписывает локальные slices Frames/OpenAPI в Redux.
2. **При правке** (добавление/удаление/переименование Frame или OpenAPI-спеки) изменение сначала применяется локально (мгновенный UI), затем дебаунсится на 500 мс в один `PUT`.
3. **Миграция при первом запуске:** если бэкенд вернул пустой конфиг, а в `localStorage` уже есть записи (типичная ситуация при апгрейде с более ранней версии ADP), панель **один раз** выгружает их на сервер — никто не теряет свой сетап.
4. **Бэкенд недоступен:** диалог настроек показывает явное предупреждение. Правки остаются в `localStorage`; на следующей удачной загрузке они синхронизируются.

Диалог настроек также показывает путь к `configDir`, чтобы вы знали какой файл коммитить:

```
┌────────────────────────────────────────────┐
│  Frames                                    │
│  …                                         │
│                                            │
│  ⓘ Synced to /your-app/config/adp/        │
│    project.json. Commit it to share with   │
│    your team.                              │
└────────────────────────────────────────────┘
```

## Проверка по плейграундам

Каждый плейграунд пишет конфиг в место, естественное для своего фреймворка. Запустите серверы (`make serve`) и опросите их `curl`-ом:

```bash
for p in 8101 8102 8103 8104; do
  curl -s "http://127.0.0.1:$p/debug/api/project/config" | jq -r '.data.configDir'
done
```

| Плейграунд | Порт | `configDir` |
|------------|-----:|-------------|
| Yii 3 | 8101 | `playground/yii3-app/config/adp` |
| Symfony | 8102 | `playground/symfony-app/config/adp` |
| Yii 2 | 8103 | `playground/yii2-basic-app/src/config/adp` |
| Laravel | 8104 | `playground/laravel-app/config/adp` |
| Spiral | 8105 | `playground/spiral-app/app/config/adp` |

(У Yii 2 путь оказывается под `src/`, потому что в плейграунде `@app` указывает на эту директорию — в реальном приложении alias резолвится по-другому.)

## Файл секретов (`secrets.json`)

Локальный сосед `project.json` для значений, которые ни в коем случае не должны попасть в VCS: API-ключи, OAuth-токены, ACP-окружение. Правило в `.gitignore` создаётся автоматически при первой записи `project.json`, поэтому свежий чекаут безопасен по умолчанию.

**Структура (v1):**

```json
{
    "version": 1,
    "llm": {
        "apiKey": "sk-ant-...",
        "provider": "openrouter",
        "model": "anthropic/claude-opus-4-7",
        "timeout": 30,
        "customPrompt": "...",
        "acpCommand": "claude",
        "acpArgs": [],
        "acpEnv": {}
    }
}
```

`llm`-namespace в точности повторяет историчный `runtime/.llm-settings.json` — это позволяет в будущем дописывать другие категории секретов (DB credentials, OAuth-токены других провайдеров, …) без миграции схемы. Права файла — `0600` (чтение/запись только владельцу).

### Миграция со старого `runtime/.llm-settings.json`

При первом чтении LLM-настроек после апгрейда панель автоматически мигрирует старый файл: содержимое уезжает в `secrets.json`, оригинал переименовывается в `.llm-settings.json.migrated` (остаётся как бэкап, **не** удаляется), в `stderr` выводится одна строка-уведомление. Идемпотентно — второй запуск ничего не делает.

### API-эндпоинты

| Метод | Путь | Назначение |
|-------|------|------------|
| `GET` | `/debug/api/project/secrets` | **Маскированный** снимок. `apiKey` показывает только последние 4 символа (`"...wxyz"`); значения `acpEnv` и элементы `acpArgs` тоже маскируются. Булевы `hasApiKey` / `hasAcpArgs` позволяют UI рендерить «настроено / не настроено», ни разу не загрузив реальный секрет в SPA. |
| `PATCH` | `/debug/api/project/secrets` | **Merge**-обновление. Тело: `{llm: {<поле>: <значение-или-null>}}`. `null` удаляет ключ, отсутствующие ключи остаются нетронутыми. `PUT` нет — маскированный GET-ответ намеренно нероундтриппабелен. |

Быстрая проверка из терминала:

```bash
# Сохранить ключ.
curl -X PATCH \
  -H 'Content-Type: application/json' \
  -d '{"llm":{"apiKey":"sk-ant-XXXXXXXX","provider":"anthropic"}}' \
  http://127.0.0.1:8101/debug/api/project/secrets

# Прочитать обратно — маскированный.
curl http://127.0.0.1:8101/debug/api/project/secrets | jq '.data.secrets'
# {
#   "apiKey": "...XXXX",
#   "hasApiKey": true,
#   "provider": "anthropic",
#   ...
# }

# Удалить ключ явно.
curl -X PATCH \
  -H 'Content-Type: application/json' \
  -d '{"llm":{"apiKey":null}}' \
  http://127.0.0.1:8101/debug/api/project/secrets
```

Существующие LLM-эндпоинты (`/debug/api/llm/connect`, `/debug/api/llm/oauth/exchange`, `/debug/api/llm/disconnect`, `/debug/api/llm/model`, …) не изменились — внутри они теперь пишут через `secrets.json`, а не `runtime/.llm-settings.json`.

## Real-time синхронизация (SSE)

Панель слушает `GET /debug/api/project/event-stream` для push-уведомлений при изменении `project.json` или `secrets.json`. Эндпоинт делает `stat()` обоих файлов раз в секунду и эмитит:

```
data: {"type":"project-config-stream-ready"}    # отправляется при подключении

data: {"type":"project-config-changed"}         # отправляется на каждое изменение
```

Это ловит **три** разных источника:

1. Сама панель сохранила что-то через `PUT`/`PATCH`.
2. Другой таб браузера редактирует тот же бэкенд.
3. `git pull` переписал файл извне процесса.

`projectSyncMiddleware` на фронте реагирует force-refetch'ем `getProjectConfig` и `getSecrets`. Существующий обработчик `getProjectConfig.fulfilled` ре-гидрирует OpenAPI/Frames slices — тот же путь, что и при начальном бутстрапе.

Соединение автоматически закрывается через 30 секунд; браузерный `EventSource` сам переподключается. Это не даёт PHP-built-in-server-воркеру висеть вечно и переживает кратковременные сбои сети.

::: warning Однопоточные dev-серверы
По умолчанию `php -S` — однопоточный, и одно открытое SSE-соединение блокирует все остальные запросы на том же порту. Плейграунды решают это через `PHP_CLI_SERVER_WORKERS=3`, который ставит `bin/serve.sh`. В своём dev-окружении используйте многопроцессный SAPI (PHP-FPM, FrankenPHP, RoadRunner) или выставьте `PHP_CLI_SERVER_WORKERS` перед `php -S`.
:::

## Технические детали

- **Project storage**: <class>AppDevPanel\Kernel\Project\FileProjectConfigStorage</class> — атомарная запись (временный файл + rename), права `0644`, авто-создание директории и `.gitignore`.
- **Project interface**: <class>AppDevPanel\Kernel\Project\ProjectConfigStorageInterface</class>.
- **Project VO**: <class>AppDevPanel\Kernel\Project\ProjectConfig</class> — иммутабельный, отбрасывает битые записи в `fromArray()`.
- **Secrets storage**: <class>AppDevPanel\Kernel\Project\FileSecretsStorage</class> — атомарная запись с правами `0600`.
- **Secrets interface**: <class>AppDevPanel\Kernel\Project\SecretsStorageInterface</class>.
- **Secrets VO**: <class>AppDevPanel\Kernel\Project\SecretsConfig</class> — иммутабельный, поддерживает `withLlm()` (заменить) и `withLlmPatch()` (merge с `null` = удалить).
- **HTTP-контроллеры**: <class>AppDevPanel\Api\Project\Controller\ProjectController</class> (`/config` + `/event-stream`), <class>AppDevPanel\Api\Project\Controller\SecretsController</class> (`/secrets`).
- **LLM-фасад**: <class>AppDevPanel\Api\Llm\FileLlmSettings</class> — читает/пишет через `SecretsStorage`, автоматически мигрирует legacy `runtime/.llm-settings.json` при первом чтении.
- **Frontend-модуль**: `libs/frontend/packages/panel/src/Module/Project/`.
- **Sync-middleware**: `Module/Project/projectSyncMiddleware.ts` — bootstrap, debounce, миграция, SSE-переподключение, подавление feedback-loops.
- **RTK Query API**: `libs/frontend/packages/sdk/src/API/Project/Project.ts` (`getProjectConfig`, `updateProjectConfig`, `getSecrets`, `patchSecrets`).
