---
title: Коллектор почты
---

# Коллектор почты

Собирает email-сообщения, отправленные во время запроса — получатели, тема, тело и метаданные.

![Панель коллектора почты](/images/collectors/mailer.png)

## Что собирает

| Поле | Описание |
|------|----------|
| `from` | Адреса отправителя |
| `to` | Адреса получателей |
| `cc` | Адреса CC |
| `bcc` | Адреса BCC |
| `replyTo` | Адреса Reply-To |
| `subject` | Тема письма |
| `textBody` | Текстовое тело |
| `htmlBody` | HTML-тело |
| `charset` | Кодировка |
| `date` | Дата отправки |

## Схема данных

```json
{
    "messages": [
        {
            "from": [{"address": "noreply@app.com", "name": "App"}],
            "to": [{"address": "user@example.com", "name": "Test User"}],
            "cc": [],
            "bcc": [],
            "replyTo": [],
            "subject": "Welcome to App",
            "textBody": "Hello, welcome!",
            "htmlBody": "<h1>Welcome</h1>",
            "raw": "...",
            "charset": "utf-8",
            "date": "Tue, 31 Mar 2026 12:00:00 +0000"
        }
    ]
}
```

**Сводка** (отображается в списке отладочных записей):

```json
{
    "mailer": {
        "total": 1
    }
}
```

## Контракт

```php
use AppDevPanel\Kernel\Collector\MailerCollector;

$collector->collectMessage([
    'from' => [['address' => 'noreply@app.com', 'name' => 'App']],
    'to' => [['address' => 'user@example.com', 'name' => 'Test User']],
    'subject' => 'Welcome to App',
    'textBody' => 'Hello, welcome!',
    'htmlBody' => '<h1>Welcome</h1>',
    // ...
]);
```

::: info
<class>\AppDevPanel\Kernel\Collector\MailerCollector</class> реализует <class>\AppDevPanel\Kernel\Collector\SummaryCollectorInterface</class> и зависит от <class>\AppDevPanel\Kernel\Collector\TimelineCollector</class>.
:::

## Как это работает

Адаптеры фреймворков перехватывают отправку email через специфичные для фреймворка хуки:
- **Symfony**: слушатель `MessageEvent` компонента Mailer
- **Laravel**: слушатель события `MessageSending`
- **Yii 3**: прокси-декоратор Mailer

## Панель отладки

- **Список сообщений** — все письма с темой, получателями и датой отправки
- **Раскрываемые детали** — полные заголовки письма, текстовое тело и предпросмотр HTML-тела
- **Количество сообщений** — общее число отображается в бейдже боковой панели
