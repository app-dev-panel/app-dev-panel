---
title: Introducing ADP — A Universal PHP Debugging Panel
date: 2026-03-15
author: ADP Team
tags: [announcement, release]
---

<script setup>
import BlogPost from '../.vitepress/theme/components/BlogPost.vue';
</script>

<BlogPost
  title="Introducing ADP — A Universal PHP Debugging Panel"
  date="2026-03-15"
  author="ADP Team"
  :tags="['announcement', 'release']"
  readingTime="5 min"
/>

Every PHP developer has been there: you add `var_dump()` to trace a bug, scatter `dd()` calls through your controllers, or dig through log files trying to piece together what happened during a request. Debugging in PHP has always felt fragmented — each framework offers its own tools, none of them talk to each other, and switching projects means learning a new debugging workflow.

**ADP (Application Development Panel)** was built to change that.

## The Problem

PHP frameworks have matured enormously. Symfony has its Profiler, Laravel has Telescope, Yii has its Debug extension. These are all excellent tools, but they share a fundamental limitation: they are tied to a single framework. If you work across multiple frameworks — or maintain legacy applications alongside modern ones — you end up juggling completely different debugging interfaces.

Beyond the fragmentation, most debugging tools are tightly coupled to their framework's internals. When the framework changes, the debugger breaks. When you need to debug something at the PSR level — a middleware pipeline, an HTTP client call, a cache interaction — framework-specific tools often fall short.

## The Solution: Framework-Agnostic Debugging

ADP takes a different approach. Instead of hooking into framework internals, it intercepts standard **PSR interfaces** — the contracts that all modern PHP frameworks already implement:

- **PSR-3** (Logger) — captures every log entry
- **PSR-7/PSR-17** (HTTP Messages) — records request/response details
- **PSR-14** (Event Dispatcher) — tracks dispatched events and listeners
- **PSR-15** (HTTP Handlers) — profiles middleware execution
- **PSR-16** (Simple Cache) — monitors cache hits, misses, and writes
- **PSR-18** (HTTP Client) — logs outgoing HTTP requests

Because ADP operates at the PSR layer, it works with any framework that implements these standards. One debugging panel, one workflow, across all your projects.

## Architecture at a Glance

ADP follows a layered architecture designed for extensibility:

1. **Kernel** — The core engine that manages the debugger lifecycle, data collectors, and storage. Completely framework-independent.
2. **Adapters** — Thin bridges that wire ADP into a specific framework's dependency injection, events, and middleware. Adapters exist for Yii 3, Symfony, Laravel, and Yii 2.
3. **API** — A REST + SSE layer that serves collected debug data to the frontend.
4. **Frontend** — A React-based SPA that renders debug data with filtering, search, and real-time updates.

The data flow is straightforward: your application runs with an adapter installed. The adapter registers proxy objects that wrap PSR interfaces. These proxies silently collect data and feed it to collectors. When the request completes, all data is flushed to storage. The frontend fetches it via the API.

## What You Can Inspect

Out of the box, ADP provides collectors for:

- **HTTP requests and responses** — headers, body, timing, status codes
- **Log messages** — all PSR-3 levels, with context and timestamps
- **Events** — dispatched events, their listeners, and execution order
- **Database queries** — SQL, bindings, execution time, slow query detection
- **Exceptions** — full stack traces with source code context
- **Middleware pipeline** — execution order and timing per middleware
- **HTTP client calls** — outgoing requests made by your application
- **Service container** — resolved services, build times, and dependency graphs

Each collector is independent and can be enabled or disabled via configuration. You can also write custom collectors for your domain-specific data — more on that in an upcoming post.

## Real-time Updates with SSE

One of ADP's standout features is real-time debugging via Server-Sent Events. As your application handles requests, debug data streams to the panel instantly. No manual refresh needed. This is particularly powerful when debugging async workers, queue jobs, or console commands — scenarios where traditional page-based profilers cannot help.

## Getting Started

ADP supports PHP 8.4+ and can be installed via Composer. Choose the adapter for your framework:

```bash
# Symfony
composer require --dev adp/adapter-symfony

# Laravel
composer require --dev adp/adapter-laravel

# Yii 3
composer require --dev adp/adapter-yii3

# Yii 2
composer require --dev adp/adapter-yii2
```

After installation, the adapter registers itself automatically. Open the debug panel in your browser and start inspecting.

## What's Next

ADP is under active development. Here is what we are working on:

- **MCP Server integration** — connect AI assistants directly to your debug data
- **Custom collector documentation** — guides for building domain-specific collectors
- **Performance profiling** — flame graphs and memory tracking
- **Expanded adapter support** — more frameworks, more proxies

We believe debugging should be a first-class development experience, not an afterthought. ADP is our contribution to making that a reality for the PHP ecosystem.

Follow the blog for tutorials, deep dives, and release announcements. We are just getting started.
