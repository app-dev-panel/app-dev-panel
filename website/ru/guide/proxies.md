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
| <class>AppDevPanel\Kernel\Collector\LoggerInterfaceProxy</class> | PSR-3 <class>Psr\Log\LoggerInterface</class> | <class>AppDevPanel\Kernel\Collector\LogCollector</class> |
| <class>AppDevPanel\Kernel\Collector\EventDispatcherInterfaceProxy</class> | PSR-14 <class>Psr\EventDispatcher\EventDispatcherInterface</class> | <class>AppDevPanel\Kernel\Collector\EventCollector</class> |
| <class>AppDevPanel\Kernel\Collector\HttpClientInterfaceProxy</class> | PSR-18 <class>Psr\Http\Client\ClientInterface</class> | <class>AppDevPanel\Kernel\Collector\HttpClientCollector</class> |

### Прокси фреймворков

Адаптеры фреймворков предоставляют дополнительные прокси для интерфейсов, не стандартизированных PSR:

| Прокси | Фреймворк | Интерфейс | Парный коллектор |
|--------|-----------|-----------|-----------------|
| <class>AppDevPanel\Adapter\Symfony\Proxy\SymfonyTranslatorProxy</class> | Symfony | <class>Symfony\Contracts\Translation\TranslatorInterface</class> | <class>AppDevPanel\Kernel\Collector\TranslatorCollector</class> |
| <class>AppDevPanel\Adapter\Symfony\Proxy\SymfonyEventDispatcherProxy</class> | Symfony | <class>Symfony\Contracts\EventDispatcher\EventDispatcherInterface</class> | <class>AppDevPanel\Kernel\Collector\EventCollector</class> |
| <class>AppDevPanel\Adapter\Laravel\Proxy\LaravelTranslatorProxy</class> | Laravel | <class>Illuminate\Contracts\Translation\Translator</class> | <class>AppDevPanel\Kernel\Collector\TranslatorCollector</class> |
| <class>AppDevPanel\Adapter\Laravel\Proxy\LaravelEventDispatcherProxy</class> | Laravel | `Dispatcher` | <class>AppDevPanel\Kernel\Collector\EventCollector</class> |
| <class>AppDevPanel\Adapter\Yiisoft\Collector\Translator\TranslatorInterfaceProxy</class> | Yiisoft | `TranslatorInterface` | <class>AppDevPanel\Kernel\Collector\TranslatorCollector</class> |
| <class>AppDevPanel\Adapter\Yiisoft\Collector\Validator\ValidatorInterfaceProxy</class> | Yiisoft | `ValidatorInterface` | <class>AppDevPanel\Kernel\Collector\ValidatorCollector</class> |
| <class>AppDevPanel\Adapter\Yiisoft\Proxy\ContainerInterfaceProxy</class> | Yiisoft | PSR-11 <class>Psr\Container\ContainerInterface</class> | <class>AppDevPanel\Kernel\Collector\ServiceCollector</class> |
| <class>AppDevPanel\Adapter\Yii2\Proxy\I18NProxy</class> | Yii 2 | `yii\i18n\I18N` | <class>AppDevPanel\Kernel\Collector\TranslatorCollector</class> |

### Прокси переводчика

У каждого фреймворка свой интерфейс переводчика. ADP предоставляет выделенный прокси для каждого, все они передают данные в один <class>AppDevPanel\Kernel\Collector\TranslatorCollector</class>. Подробности на странице [Переводчик](/ru/guide/translator).

**Symfony** -- <class>AppDevPanel\Adapter\Symfony\Proxy\SymfonyTranslatorProxy</class> декорирует <class>Symfony\Contracts\Translation\TranslatorInterface</class> через `setDecoratedService()` в compiler pass. Перехватывает вызовы `trans()`.

**Laravel** -- <class>AppDevPanel\Adapter\Laravel\Proxy\LaravelTranslatorProxy</class> декорирует <class>Illuminate\Contracts\Translation\Translator</class> через `$app->extend('translator')`. Перехватывает вызовы `get()` и `choice()`. Разбирает точечную нотацию ключей Laravel (`group.key`) на категорию и сообщение.

**Yiisoft** -- <class>AppDevPanel\Adapter\Yiisoft\Collector\Translator\TranslatorInterfaceProxy</class> регистрируется в `trackedServices` наряду с <class>AppDevPanel\Adapter\Yiisoft\Collector\Validator\ValidatorInterfaceProxy</class>. Перехватывает вызовы `translate()`. Поддерживает иммутабельные методы `withDefaultCategory()` и `withLocale()`.

**Yii 2** -- <class>AppDevPanel\Adapter\Yii2\Proxy\I18NProxy</class> наследует `yii\i18n\I18N` и переопределяет `translate()`. Заменяет компонент приложения `i18n` при загрузке модуля.

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
