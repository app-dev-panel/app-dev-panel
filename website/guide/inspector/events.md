---
title: Event Listeners Inspector
---

# Event Listeners Inspector

View all registered event listeners across your application — organized by event class.

![Event Listeners Inspector](/images/inspector/events.png)

## What It Shows

| Field | Description |
|-------|-------------|
| Event class | Fully qualified event class name |
| Listeners | List of listener classes/methods subscribed to this event |
| Groups | Common, Web, and Console event dispatchers (tab-separated) |

## Tabs

- **Common** — Event listeners shared across all contexts
- **Web** — Listeners specific to HTTP request handling
- **Console** — Listeners specific to CLI command execution

## API Endpoints

| Method | Path | Description |
|--------|------|-------------|
| GET | `/inspect/api/events` | All event listeners by group |

## How It Works

The inspector reads the application's event dispatcher configuration and enumerates all registered listeners. For Symfony, this includes kernel events (`kernel.request`, `kernel.response`, etc.), Doctrine events, and custom application events.
