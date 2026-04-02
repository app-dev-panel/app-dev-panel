---
title: Коллектор пакетов ресурсов
---

# Коллектор пакетов ресурсов

Захватывает зарегистрированные пакеты фронтенд-ресурсов — CSS-файлы, JavaScript-файлы, зависимости и конфигурацию.

## Собираемые данные

| Поле | Описание |
|------|----------|
| `class` | Имя класса пакета ресурсов |
| `sourcePath` | Исходный путь для публикуемых ресурсов |
| `basePath` | Базовый путь публикации |
| `baseUrl` | Базовый URL публикации |
| `css` | Список CSS-файлов |
| `js` | Список JavaScript-файлов |
| `depends` | Зависимости пакета |
| `options` | Опции пакета |

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

// Или собрать все пакеты за один раз
$collector->collectBundles(bundles: $allBundles);
```

::: info
<class>\AppDevPanel\Kernel\Collector\AssetBundleCollector</class> реализует <class>\AppDevPanel\Kernel\Collector\SummaryCollectorInterface</class> и зависит от <class>\AppDevPanel\Kernel\Collector\TimelineCollector</class>. Используется преимущественно с фреймворками Yii.
:::

## Панель отладки

- **Список пакетов** — все зарегистрированные пакеты ресурсов с количеством файлов
- **Файлы ресурсов** — CSS и JS файлы по каждому пакету
- **Дерево зависимостей** — граф зависимостей пакетов
