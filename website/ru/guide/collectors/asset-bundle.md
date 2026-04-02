---
title: Коллектор наборов ресурсов
---

# Коллектор наборов ресурсов

Захватывает зарегистрированные наборы фронтенд-ресурсов — CSS-файлы, JavaScript-файлы, зависимости и конфигурацию.

## Что собирает

| Поле | Описание |
|------|----------|
| `class` | Имя класса набора ресурсов |
| `sourcePath` | Путь к исходным файлам для публикуемых ресурсов |
| `basePath` | Базовый путь опубликованных ресурсов |
| `baseUrl` | Базовый URL опубликованных ресурсов |
| `css` | Список CSS-файлов |
| `js` | Список JavaScript-файлов |
| `depends` | Зависимости набора |
| `options` | Опции набора |

## Схема данных

```json
{
    "bundles": {
        "AppAsset": {
            "class": "App\\Assets\\AppAsset",
            "sourcePath": "/app/assets",
            "basePath": "/public/assets/abc123",
            "baseUrl": "/assets/abc123",
            "css": ["css/app.css"],
            "js": ["js/app.js"],
            "depends": ["yii\\web\\JqueryAsset"],
            "options": {}
        }
    },
    "bundleCount": 3
}
```

**Сводка** (отображается в списке отладочных записей):

```json
{
    "assets": {
        "bundleCount": 3
    }
}
```

## Контракт

```php
use AppDevPanel\Kernel\Collector\AssetBundleCollector;

$collector->collectBundle(name: 'AppAsset', bundle: [
    'class' => 'App\\Assets\\AppAsset',
    'css' => ['css/app.css'],
    'js' => ['js/app.js'],
    'depends' => ['yii\\web\\JqueryAsset'],
]);

// Или собрать все наборы сразу
$collector->collectBundles(bundles: $allBundles);
```

::: info
<class>\AppDevPanel\Kernel\Collector\AssetBundleCollector</class> реализует <class>\AppDevPanel\Kernel\Collector\SummaryCollectorInterface</class> и зависит от <class>\AppDevPanel\Kernel\Collector\TimelineCollector</class>. Преимущественно используется с фреймворками Yii.
:::

## Панель отладки

- **Список наборов** — все зарегистрированные наборы ресурсов с количеством файлов
- **Файлы ресурсов** — CSS и JS файлы по каждому набору
- **Дерево зависимостей** — граф зависимостей наборов
