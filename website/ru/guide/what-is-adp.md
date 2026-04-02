---
description: "ADP -- фреймворк-независимая панель отладки для PHP. Один инструмент для Symfony, Laravel, Yii и PSR-приложений."
---

# Что такое ADP?

**ADP (Application Development Panel)** — это фреймворк-независимая панель отладки и разработки для PHP-приложений. Можно представить это как универсальную «панель разработчика», работающую с любым PHP-фреймворком.

## Проблема

У каждого PHP-фреймворка свои инструменты отладки:

- У Symfony есть **Web Profiler**
- У Laravel есть **Telescope**
- У Yii есть **Debug Extension**

Но что если вы работаете с несколькими фреймворками? Или хотите единые инструменты для всех проектов? Тут на помощь приходит ADP.

## Решение

ADP предоставляет **единый, унифицированный опыт отладки**, работающий поверх PSR-стандартов:

- **PSR-3** (Logger) — Перехват сообщений лога
- **PSR-7** (HTTP Messages) — Инспекция запросов и ответов
- **PSR-14** (Event Dispatcher) — Отслеживание событий
- **PSR-15** (HTTP Handlers) — Мониторинг цепочки middleware
- **PSR-11** (Container) — Инспекция внедрения зависимостей

## Ключевые возможности

### Коллекторы

ADP поставляется с коллекторами для типичных сценариев отладки:

| Коллектор | Что собирает |
|-----------|-------------|
| <class>AppDevPanel\Kernel\Collector\LogCollector</class> | Сообщения PSR-3 логгера |
| <class>AppDevPanel\Kernel\Collector\EventCollector</class> | События PSR-14 |
| <class>AppDevPanel\Kernel\Collector\HttpClientCollector</class> | Исходящие HTTP-запросы |
| <class>AppDevPanel\Kernel\Collector\DatabaseCollector</class> | SQL-запросы и их тайминг |
| <class>AppDevPanel\Kernel\Collector\ExceptionCollector</class> | Пойманные и непойманные исключения |
| <class>AppDevPanel\Kernel\Collector\MiddlewareCollector</class> | Выполнение PSR-15 middleware |
| <class>AppDevPanel\Kernel\Collector\ServiceCollector</class> | Разрешение зависимостей из DI-контейнера |
| <class>AppDevPanel\Kernel\Collector\AssetBundleCollector</class> | Фронтенд-ассеты |
| <class>AppDevPanel\Kernel\Collector\RouterCollector</class> | Сопоставление маршрутов и параметры |

### Обновления в реальном времени

Панель использует Server-Sent Events (SSE) для push-обновлений в браузер. Обновлять страницу не нужно.

### AI-интеграция

ADP включает MCP-сервер (Model Context Protocol), который предоставляет данные отладки AI-ассистентам, таким как Claude. Попросите AI проанализировать ошибки, предложить исправления или объяснить сложные потоки запросов.

### Поддержка фреймворков

| Фреймворк | Статус адаптера |
|-----------|----------------|
| Symfony 7 | Стабильный |
| Yii 2 | Стабильный |
| Yii 3 | Стабильный |
| Laravel 12 | Стабильный |

## Философия

- **Фреймворк-независимость** — Работает с любым PSR-совместимым фреймворком
- **Без конфигурации** — Установите адаптер, и всё заработает
- **Ненавязчивость** — Использует прокси, а не патчи. Ваш код остаётся чистым
- **Расширяемость** — Пишите свои коллекторы для вашей предметной области
- **Современный стек** — React 19, TypeScript, Material-UI на фронтенде
