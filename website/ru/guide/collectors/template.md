---
title: Коллектор шаблонов
---

# Коллектор шаблонов

Захватывает рендеринг шаблонов/представлений с опциональным таймингом, захватом вывода, параметрами и обнаружением дубликатов. Работает с любым шаблонизатором (Twig, Blade, PHP-шаблоны и т.д.).

## Что собирает

| Поле | Описание |
|------|----------|
| `template` | Путь к файлу шаблона или его имя |
| `renderTime` | Длительность рендеринга в секундах (опционально, 0 если не измеряется) |
| `output` | Отрендеренный HTML-вывод (опционально, пусто если не захвачен) |
| `parameters` | Параметры, переданные в шаблон (опционально) |

## Схема данных

```json
{
    "renders": [
        {
            "template": "home/index.html.twig",
            "renderTime": 0.0045,
            "output": "",
            "parameters": []
        },
        {
            "template": "/app/views/user/profile.php",
            "renderTime": 0,
            "output": "<div class=\"profile\">...</div>",
            "parameters": {"user": "object@App\\Entity\\User#42"}
        }
    ],
    "totalTime": 0.0045,
    "renderCount": 2,
    "duplicates": {
        "groups": [],
        "totalDuplicatedCount": 0
    }
}
```

**Сводка** (отображается в списке отладочных записей):

```json
{
    "template": {
        "renderCount": 2,
        "totalTime": 0.0045,
        "duplicateGroups": 0,
        "totalDuplicatedCount": 0
    }
}
```

## Контракт

Две точки входа в зависимости от доступных данных:

```php
use AppDevPanel\Kernel\Collector\TemplateCollector;

// Шаблонизаторы с таймингом (Twig, Blade)
$collector->logRender(
    template: 'home/index.html.twig',
    renderTime: 0.0045,
);

// Системы представлений с захватом вывода (Yii views, PHP-шаблоны)
$collector->collectRender(
    template: '/app/views/user/profile.php',
    output: '<div class="profile">...</div>',
    parameters: ['user' => $userObject],
    renderTime: 0.003, // опционально
);
```

::: info
<class>\AppDevPanel\Kernel\Collector\TemplateCollector</class> реализует <class>\AppDevPanel\Kernel\Collector\SummaryCollectorInterface</class>, зависит от <class>\AppDevPanel\Kernel\Collector\TimelineCollector</class> и использует <class>\AppDevPanel\Kernel\Collector\DuplicateDetectionTrait</class>.
:::

## Как это работает

Адаптеры фреймворков подключаются к системе рендеринга шаблонов/представлений:
- **Symfony**: расширение профайлера Twig измеряет время рендеринга, вызывает `logRender()`
- **Yii 3**: <class>\AppDevPanel\Adapter\Yii3\Collector\View\ViewEventListener</class> слушает события рендеринга представлений, вызывает `collectRender()`
- **Yii 2**: `View::EVENT_BEFORE_RENDER` + `EVENT_AFTER_RENDER` со стеком таймеров по файлам — захватывает тайминг, вывод и параметры за один вызов

## Панель отладки

- **Карточки сводки** — общее количество отрендеренных шаблонов, суммарное время рендеринга (при наличии тайминга)
- **Список шаблонов** — все отрендеренные шаблоны с путями к файлам и временем рендеринга
- **Предпросмотр вывода** — отрендеренный HTML-вывод (раскрываемый, при наличии захвата)
- **Параметры** — раскрываемые параметры представления (при наличии захвата)
- **Обнаружение дубликатов** — подсветка повторных рендеров одного шаблона (проблемы N+1)
- **Плоский / Сгруппированный вид** — переключение между всеми рендерами и сгруппированными дубликатами
