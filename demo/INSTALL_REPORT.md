# ADP Yii2 Install Walkthrough — Report

Тестовая установка ADP в чистое приложение на Yii 2, следуя официальной инструкции
(`website/guide/getting-started.md`, `website/guide/adapters/yii2.md`). Пакет
ставился из Packagist (`app-dev-panel/adapter-yii2 ^0.2`).

## Что сделано

1. `composer create-project --prefer-dist yiisoft/yii2-app-basic demo/yii2-demo` —
   стандартный basic-шаблон Yii2.
2. `composer require app-dev-panel/adapter-yii2` — поставил `v0.2` со всеми
   транзитивными зависимостями (kernel, api, cli, mcp-server, guzzle, nyholm/psr7, …).
3. Поднял сервер: `PHP_CLI_SERVER_WORKERS=3 php -S 127.0.0.1:8103 -t web`.
4. Открыл `http://127.0.0.1:8103/` и `http://127.0.0.1:8103/debug`.

## Скриншоты

| Файл | Что показывает |
|------|----------------|
| `screenshots/01-homepage-with-toolbar.png` | Главная Yii2 с тулбар-пиллом ADP в правом нижнем углу (duck + статус + время) |
| `screenshots/02-about-with-toolbar.png` | Страница `/site/about` — тулбар отображается и на других страницах |
| `screenshots/03-debug-panel.png` | Debug-панель по `/debug` — подключены Debug и Inspector API, список из 17 debug-entries за сессию |

## Проблемы, с которыми пришлось столкнуться

### 1. Документация не упоминает конфликт с `yiisoft/yii2-debug` (блокер)

`yii2-app-basic` по умолчанию подключает `yiisoft/yii2-debug` в `dev`-окружении
(bootstrap `debug`, модуль `debug`). После установки ADP получаем **два** модуля
с пересекающимися URL:

- `yii2-debug` регистрирует `debug/*` через свой `debug\Module`.
- ADP добавляет правила urlManager `debug`, `debug/<path:…>`, `debug/api/*`
  маршрутизируемые в `app-dev-panel/adp-api/handle`.

В результате `GET /debug` возвращает стандартный интерфейс yii2-debug
(«Available Debug Data», `<title>Yii Debugger</title>`) вместо SPA ADP, плюс на
каждую страницу инжектится одновременно два тулбара.

Мой фикс (см. `demo/yii2-demo/config/web.php`) — закомментировал
`bootstrap: ['debug']` и модуль `debug`:

```php
// NOTE: yii2-debug disabled — conflicts with ADP's routes at /debug.
// $config['bootstrap'][] = 'debug';
// $config['modules']['debug'] = ['class' => 'yii\debug\Module'];
```

В доках (`website/guide/adapters/yii2.md` или `getting-started.md`) нужно
отдельным предупреждением обозначить: «если ставите в приложение, сделанное из
`yii2-app-basic`/`advanced`, удалите блок yii2-debug — иначе конфликт маршрутов».

### 2. Pretty URLs обязательны, но инструкция об этом молчит (блокер)

ADP регистрирует URL-правила только через `UrlManager` (`addRules([...])`).
Basic-шаблон Yii2 поставляется с закомментированным `urlManager` — то есть
`enablePrettyUrl` по умолчанию `false`. В этом режиме запрос `GET /debug`
идёт через `r=…` и попадает на `site/index` (или на yii2-debug, см. п. 1).
Когда мы пробиваемся по каноничной ссылке
`GET /index.php?r=app-dev-panel/adp-api/handle`, контроллер отвечает
`404 Not Found` c JSON — потому что без URL-правил он не знает, какой
путь под `/debug/*` реально был запрошен.

Исправил руками в `config/web.php`:

```php
'urlManager' => [
    'enablePrettyUrl' => true,
    'showScriptName'  => false,
    'rules'           => [],
],
```

В `getting-started.md` шаг 2 для Yii 2 должен явно включать этот блок.

### 3. Бандлы panel/toolbar не поставляются ни в пакете, ни по CDN (блокер)

Самая болезненная проблема. Адаптер `app-dev-panel/adapter-yii2 v0.2`:

- Содержит пустую директорию `resources/dist/` (в архиве с Packagist
  `bundle.js` нет вообще).
- При отсутствии локальных ассетов `Module.php` откатывается на дефолтный
  `PanelConfig::DEFAULT_STATIC_URL`, который указывает на
  `https://app-dev-panel.github.io/app-dev-panel/`.
- Этот GitHub Pages хост отдаёт сайт VitePress-документации, а не бандлы.
  И `bundle.js`, и `toolbar/bundle.js` на нём отвечают **`HTTP 404`**.

Соответственно сразу после `composer require` — тулбар не рендерится
(`<script src="https://app-dev-panel.github.io/app-dev-panel/toolbar/bundle.js">` → 404),
а SPA по `/debug` отдаётся пустой страницей (только `<div id="root">` без JS).
См. первоначальный вариант скриншотов — были чисто белые.

Что пришлось сделать:

```bash
cd libs/frontend && npm install
make build-panel
cp -R libs/Adapter/Yii2/resources/dist/. \
      demo/yii2-demo/vendor/app-dev-panel/adapter-yii2/resources/dist/
```

После этого `Module::registerApiApplication()` автоматически создал symlink
`web/app-dev-panel → …/vendor/app-dev-panel/adapter-yii2/resources/dist` и
переключил `panelStaticUrl` на `/app-dev-panel`. Только после этого тулбар и
SPA ожили (см. финальные скриншоты).

Что нужно поправить в upstream:

- либо публиковать `resources/dist` **с бандлами** в релизных тегах
  `app-dev-panel/adapter-yii2` (аналогично `yii2-debug`),
- либо чинить `PanelConfig::DEFAULT_STATIC_URL` так, чтобы он указывал на
  реально существующие ассеты (CDN с бандлами, например
  `https://cdn.jsdelivr.net/npm/@app-dev-panel/panel/dist/`),
- либо в инсталлятор-хук composer.json добавить проверку/скачивание
  pre-built бандлов,
- либо явно прописать в документации обязательный шаг
  `make build-panel`/`npm run build` + копирование артефактов и рассказать,
  куда их класть.

### 4. Мелочи

- Документация говорит «open `http://your-app/debug`», но для запуска через
  PHP built-in server нужна рекомендация `PHP_CLI_SERVER_WORKERS>=3` (есть
  в `getting-started.md`, но вне табы Yii 2 — легко пропустить).
- `extra.bootstrap` в `composer.json` адаптера работает через
  `yiisoft/yii2-composer` — он уже стоит в basic-шаблоне, но если кто-то
  выключил plugin, бутстрап не сработает. Стоит указать в доке как
  «требование».
- Если `web/app-dev-panel` существует как файл (не symlink) — адаптер не
  перезапишет и молча оставит старое. Хорошо бы либо логировать, либо
  документировать.

## Итог

Выкатить ADP «как в документации» на свежий Yii 2 нельзя: три блокирующие
проблемы (конфликт с yii2-debug, обязательный pretty URL, отсутствие
фронт-бандлов) — без фиксов `/debug` возвращает 404/пустую страницу, а
тулбар не загружается. После ручных исправлений — панель и тулбар работают
корректно, собирают запросы/логи/события (17 записей за сессию на
скриншоте).
