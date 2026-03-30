---
title: Система прокси
---

# Система прокси

ADP использует паттерн прокси для прозрачного перехвата вызовов PSR-интерфейсов. Прокси оборачивают стандартные PSR-реализации и передают перехваченные данные коллекторам без изменения кода приложения.

## Как это работает

Прокси реализует тот же PSR-интерфейс, что и оригинальный сервис. Он делегирует все вызовы реальной реализации, одновременно записывая данные для парного коллектора.

```
Код приложения
       │
       ▼
┌─────────────────┐
│  PSR-интерфейс   │  ← Код вызывает как обычно
│  (напр. Logger)  │
└────────┬────────┘
         │
┌────────▼────────┐
│     Прокси       │  ← Перехватывает + записывает
│ (LoggerProxy)    │
└────────┬────────┘
         │
┌────────▼────────┐
│ Реальный сервис  │  ← Оригинальная реализация
│ (Monolog и т.д.) │
└────────┬────────┘
         │
┌────────▼────────┐
│   Коллектор      │  ← Получает записанные данные
│ (LogCollector)   │
└─────────────────┘
```

## Встроенные прокси

Ядро предоставляет фреймворко-независимые PSR-прокси:

| Прокси | PSR-интерфейс | Парный коллектор |
|--------|---------------|-----------------|
| `LoggerInterfaceProxy` | PSR-3 `LoggerInterface` | `LogCollector` |
| `EventDispatcherInterfaceProxy` | PSR-14 `EventDispatcherInterface` | `EventCollector` |
| `HttpClientInterfaceProxy` | PSR-18 `ClientInterface` | `HttpClientCollector` |

### Прокси фреймворков

Адаптеры фреймворков предоставляют дополнительные прокси для интерфейсов, не стандартизированных PSR:

| Прокси | Фреймворк | Интерфейс | Парный коллектор |
|--------|-----------|-----------|-----------------|
| `SymfonyTranslatorProxy` | Symfony | `TranslatorInterface` | `TranslatorCollector` |
| `SymfonyEventDispatcherProxy` | Symfony | `EventDispatcherInterface` | `EventCollector` |
| `LaravelTranslatorProxy` | Laravel | `Translator` | `TranslatorCollector` |
| `LaravelEventDispatcherProxy` | Laravel | `Dispatcher` | `EventCollector` |
| `TranslatorInterfaceProxy` | Yiisoft | `TranslatorInterface` | `TranslatorCollector` |
| `ValidatorInterfaceProxy` | Yiisoft | `ValidatorInterface` | `ValidatorCollector` |
| `ContainerInterfaceProxy` | Yiisoft | PSR-11 `ContainerInterface` | `ServiceCollector` |
| `I18NProxy` | Yii 2 | `yii\i18n\I18N` | `TranslatorCollector` |

### Прокси переводчика

У каждого фреймворка свой интерфейс переводчика. ADP предоставляет выделенный прокси для каждого, все они передают данные в один `TranslatorCollector`. Подробности на странице [Переводчик](/ru/guide/translator).

**Symfony** -- декорирует `Symfony\Contracts\Translation\TranslatorInterface` через `setDecoratedService()` в compiler pass. Перехватывает вызовы `trans()`.

**Laravel** -- декорирует `Illuminate\Contracts\Translation\Translator` через `$app->extend('translator')`. Перехватывает вызовы `get()` и `choice()`. Разбирает точечную нотацию ключей Laravel (`group.key`) на категорию и сообщение.

**Yiisoft** -- регистрируется в `trackedServices` наряду с `ValidatorInterfaceProxy`. Перехватывает вызовы `translate()`. Поддерживает иммутабельные методы `withDefaultCategory()` и `withLocale()`.

**Yii 2** -- наследует `yii\i18n\I18N` и переопределяет `translate()`. Заменяет компонент приложения `i18n` при загрузке модуля.

::: tip Обнаружение отсутствующих переводов
Все прокси определяют отсутствие перевода, сравнивая возвращённое значение переводчика с исходным идентификатором сообщения. Если они совпадают, перевод помечается как `missing`.
:::

## Трейт ProxyDecoratedCalls

Трейт `ProxyDecoratedCalls` предоставляет магические методы `__call`, `__get` и `__set`, которые делегируют вызовы обёрнутому сервису. Прокси используют этот трейт для прозрачной переадресации неперехватываемых вызовов.

## Регистрация прокси

Прокси регистрируются адаптерами фреймворков через конфигурацию DI-контейнера. Адаптер заменяет оригинальную привязку PSR-сервиса на прокси:

```php
// Упрощённая DI-конфигурация адаптера
LoggerInterface::class => function (ContainerInterface $c) {
    return new LoggerInterfaceProxy(
        $c->get(LogCollector::class),
        $c->get('original.logger'),
    );
},
```

## Ключевые принципы

- **Прозрачность** -- код приложения не знает о перехвате
- **Нулевые затраты при отключении** -- прокси регистрируются только при активном ADP
- **PSR-совместимость** -- прокси реализуют тот же интерфейс, что и оригинальный сервис
- **Колокация** -- каждый прокси находится рядом с парным коллектором в `src/Collector/`
