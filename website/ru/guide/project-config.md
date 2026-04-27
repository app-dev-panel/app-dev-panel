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
| `secrets.json` | **Нет** (gitignored) | Зарезервирован для будущих API-ключей и локальных переопределений |

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

(У Yii 2 путь оказывается под `src/`, потому что в плейграунде `@app` указывает на эту директорию — в реальном приложении alias резолвится по-другому.)

## Что будет дальше

`secrets.json` будет хранить значения, специфичные для машины и **никогда не коммитимые**: API-ключи Anthropic / OpenRouter, OAuth-токены и любые будущие переопределения окружения для ACP. Правило в `.gitignore` уже на месте; класс хранения и эндпоинт API подъедут отдельным релизом.

## Технические детали

- **Backend storage**: <class>AppDevPanel\Kernel\Project\FileProjectConfigStorage</class> — атомарная запись (временный файл + rename), права `0644`, авто-создание директории и `.gitignore`.
- **Backend interface**: <class>AppDevPanel\Kernel\Project\ProjectConfigStorageInterface</class>.
- **Backend value object**: <class>AppDevPanel\Kernel\Project\ProjectConfig</class> — иммутабельный, отбрасывает битые записи в `fromArray()`.
- **HTTP-контроллер**: <class>AppDevPanel\Api\Project\Controller\ProjectController</class>.
- **Frontend-модуль**: `libs/frontend/packages/panel/src/Module/Project/`.
- **Sync-middleware**: `Module/Project/projectSyncMiddleware.ts` — bootstrap, debounce, миграция, подавление feedback-loops.
- **RTK Query API**: `libs/frontend/packages/sdk/src/API/Project/Project.ts`.
