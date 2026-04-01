---
title: Mailer Collector
---

# Mailer Collector

Captures email messages sent during a request — recipients, subject, body, and metadata.

![Mailer Collector panel](/images/collectors/mailer.png)

## What It Captures

| Field | Description |
|-------|-------------|
| `from` | Sender addresses |
| `to` | Recipient addresses |
| `cc` | CC addresses |
| `bcc` | BCC addresses |
| `replyTo` | Reply-To addresses |
| `subject` | Email subject line |
| `textBody` | Plain text body |
| `htmlBody` | HTML body |
| `charset` | Character set |
| `date` | Send date |

## Data Schema

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

**Summary** (shown in debug entry list):

```json
{
    "mailer": {
        "total": 1
    }
}
```

## Contract

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
<class>\AppDevPanel\Kernel\Collector\MailerCollector</class> implements <class>\AppDevPanel\Kernel\Collector\SummaryCollectorInterface</class> and depends on <class>\AppDevPanel\Kernel\Collector\TimelineCollector</class>.
:::

## How It Works

Framework adapters intercept email sending through framework-specific hooks:
- **Symfony**: `MessageEvent` listener on the Mailer component
- **Laravel**: `MessageSending` event listener
- **Yii 3**: Mailer proxy decorator

## Debug Panel

- **Message list** — all emails with subject, recipients, and send date
- **Expandable details** — full email headers, text body, and HTML body preview
- **Message count** — total shown in sidebar badge
